<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use think\facade\Validate;
use app\common\Excel;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Linkman;
use app\api\model\Filter;
use app\api\model\User;
use app\api\model\Recommend;
use app\api\model\Confirm;
use app\api\model\Company;
use app\api\model\File;

class Customer extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $status = ['潜在','跟进','看房','洽谈','成交','失败','名录'];
  public static $share = ['私有','共享'];
  public static $IGNORE_WORDS = '/北京|上海|深圳|广州|中国|美国|日本|德国|英国|法国|（|）|\(|\)/';
  
  /**
   * 格式化列表数据
   */
  protected static function formatList($list) {
    foreach($list as $key=>$customer) {
      $customer->title = '【' . self::$status[$customer->status] . '】' . $customer->customer_name;
      //if ($customer->clash) {
      //  $customer->title = $customer->title . '<span style="color:red">（撞单）</span>';
      //}
      $customer->desc = (empty($customer->lease_buy) ? '' : $customer->lease_buy) . 
        (empty($customer->demand) ? '' : $customer->demand . ' ');

      if ($customer->min_acreage && $customer->max_acreage) {
        $customer->desc = $customer->desc . ' ' . $customer->min_acreage . ' 至 ' . $customer->max_acreage . ' 平米';
      } else if ($customer->min_acreage) {
        $customer->desc = $customer->desc . ' ' . $customer->min_acreage . ' 平米以上';
      } else if ($customer->max_acreage) {
        $customer->desc = $customer->desc . ' ' . $customer->max_acreage . ' 平米以内';
      }

      if ($customer->budget) {
        $customer->desc = $customer->desc . ' ' . $customer->budget;
      }

      if ($customer->username) {
        $customer->desc = $customer->desc . ' 客户经理：' . $customer->username;
      }

      $customer->url = '/customer/view/' . $customer->id;
    }
    return $list;
  }
  
  /**
   * 权限检查
   */
  public static function allow($user, $customer, $operate) {
    if ($user == null) {
      return false;
    } else if ($customer == null && $operate != 'new') {
      return false;
    }

    $superior_id = -1;

    if (null != $customer) {
      $superior_id = Company::getSuperior($customer->company_id, $customer->user_id);
    }

    if ($operate == 'view') {
      return ($user->isCompanyAdmin && $customer->company_id == $user->company_id) || 
        $customer->share_level !== null || $customer->user_id == $user->id || 
        ($customer->share && $customer->company_id > 0 && $customer->company_id == $user->company_id) || 
        ($user->id == $superior_id && $customer->company_id > 0 && $customer->company_id == $user->company_id);
    } else if ($operate == 'new') {
      return true;
    } else if ($operate == 'turn') {
      return $customer->company_id == $user->company_id &&
        ($customer->user_id == $user->id || $user->isCompanyAdmin);
    } else if ($operate == 'edit') {
      return (($user->isCompanyAdmin && $customer->company_id == $user->company_id) || 
        $customer->user_id == $user->id || $customer->share_level > 0); 
        //&& (!$customer->clash || $customer->parallel);
    } else if ($operate == 'follow') {    // 跟进
      return (
        ($user->isCompanyAdmin && $customer->company_id == $user->company_id) || 
        $customer->share_level !== null || $customer->user_id == $user->id || 
        ($customer->share && $customer->company_id > 0 && $customer->company_id == $user->company_id) || 
        ($user->id == $superior_id && $customer->company_id > 0 && $customer->company_id == $user->company_id)
      ) && (!$customer->clash || $customer->parallel);
    } else if ($operate == 'confirm') {   // 确认
      return $customer->user_id == $user->id &&
        $customer->company_id > 0 && $customer->company_id == $user->company_id && 
        (!$customer->clash || $customer->parallel);
    } else if ($operate == 'clash') {     // 撞单处理
      return $user->isCompanyAdmin && $customer->clash &&
        $customer->company_id == $user->company_id;
    } else if ($operate == 'delete') {    // 删除
      return ($customer->user_id == $user->id &&
        $customer->company_id > 0 && $customer->company_id == $user->company_id) ||
        ($user->isCompanyAdmin && $customer->clash &&
        $customer->company_id > 0 && $customer->company_id == $user->company_id) ||
        $customer->share_level > 1;
    } else {
      return false;
    }
  }
  
  /**
   * 检索客户信息
   */
  public static function search($user, $filter) {
    if (!isset($filter['page'])) {
      $filter['page'] = 1;
    }

    if (!isset($filter['page_size'])) {
      $filter['page_size'] = 10;
    }

    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }
    
    $list = self::alias('a')
      ->leftJoin('user_company b', 'a.user_id = b.user_id AND a.company_id = b.company_id AND b.status = 1')
      ->leftJoin('user u', 'a.user_id = u.id')
      ->leftJoin('share s', "s.type = 'customer' AND a.id = s.object_id AND s.user_id = " . $user_id)
      ->where('(a.user_id = ' . $user_id . '
         OR (a.share = 1 OR b.superior_id = ' . $user_id . ')
         OR s.object_id IS NOT NULL) AND a.company_id > 0 AND a.company_id = ' . $company_id);

    if (isset($filter['keyword']) && $filter['keyword'] != '') {
      $list->where('a.customer_name', 'like', '%' . $filter['keyword'] . '%');
    } else if (isset($filter['status']) && $filter['status'] != '') {
      $list->where('a.status', 'in', $filter['status']);
    } else if (isset($filter['type'])) {
      if ($filter['type'] == 'potential') {
        $list->where('a.status', '0');
      } else if ($filter['type'] == 'talk') {
        $list->where('a.status', '3');
      } else if ($filter['type'] == 'success') {
        $list->where('a.status', '4');
      } else if ($filter['type'] == 'fail') {
        $list->where('a.status', 'in', '5,6');
      } else if ($filter['type'] == 'pool') {
        $list->where('a.status', 'in', '4,5,6');
      } else {
        $list->where('a.status', 'in', '1,2');
      }
    }

    if (isset($filter['clash'])) {
      if ($filter['clash']) {
        $list->where('a.clash', '>', 0);
      } else {
        $list->where('(a.clash IS NULL OR a.clash = 0)');
      }
    }

    if (isset($filter['endDate'])) {
      $list->where('a.end_date', 'not null')
        ->where('a.remind', '>', 0)
        ->where('date_sub(a.end_date, interval a.remind month) < now()');
    }

    $result = $list->field('a.id,a.customer_name,a.area,a.address,a.demand,
      a.lease_buy,a.min_acreage,a.max_acreage,a.budget,a.status,a.clash,u.title as username,
      s.create_time as share_create_time,s.level as share_level')
      ->page($filter['page'], $filter['page_size'])
      ->order('a.clash', 'desc')
      ->order('a.update_time', 'desc')
      ->order('a.id', 'desc')
      ->select();

    return self::formatList($result);
  }

  /**
   * 通过ID获取客户信息
   */
  public static function getById($user, $id) {
    $user_id = 0;

    if ($user) {
      $user_id = $user->id;
    }

    $data = self::alias('a')
      ->leftJoin('user b','b.id = a.user_id')
      ->leftJoin('company c','c.id = a.company_id')
      ->leftJoin('share s', "s.type = 'customer' and a.id = s.object_id and s.user_id <> a.user_id and s.user_id = " . $user_id)
      ->where('a.id', $id)
      ->field('a.*,
        b.title as manager,b.avatar,b.mobile as manager_mobile,c.title as company,
        s.create_time as share_create_time,s.level as share_level')
      ->find();

    return $data;
  }

  /**
   * 获取客户详细信息
   */
  public static function detail($user, $id, $key = '', $operate = 'view') {
    $user_id = 0;
    // $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      // $company_id = $user->company_id;
    }

    // 通过分享链接查看自动加入共享列表
    if ($user_id > 0 && !empty($key) && $key == md5('customer' . $id . config('wechat.app_secret'))) {
      $share = db('share')
        ->where('type', 'customer')
        ->where('user_id', $user_id)
        ->where('object_id', $id)
        ->find();

      if (null == $share) {
        db('share')->insert([
          'type' => 'customer',
          'user_id' => $user_id,
          'object_id' => $id
        ]);
      }
    }

    $data = self::alias('a')
      ->leftJoin('user b','b.id = a.user_id')
      ->leftJoin('company c','c.id = a.company_id')
      ->leftJoin('share s', "s.type = 'customer' and a.id = s.object_id and s.user_id <> a.user_id and s.user_id = " . $user_id)
      ->where('a.id', $id)
      ->field('a.id,a.customer_name,a.tel,a.area,a.address,a.demand,a.lease_buy,
        a.district,a.min_acreage,a.max_acreage,a.budget,a.settle_date,a.current_area,
        a.end_date,a.remind,a.rem,a.status,a.clash,a.parallel,a.share,a.user_id,a.company_id,
        b.title as manager,b.avatar,b.mobile as manager_mobile,c.title as company,
        s.create_time as share_create_time,s.level as share_level')
      ->find();

    if ($data == null) {
      self::exception('客户不存在。');
    }
    
    if (!self::allow($user, $data, $operate)) {
      self::exception('您没有权限' . ($operate == 'view' ? '查看' : '修改') . '此客户。');
    }

    $data->attach = File::getList($user, 'customer', $id);

    if ($operate == 'view') {
      if ($data->min_acreage && $data->max_acreage) {
        $data->acreage = $data->min_acreage . ' 至 ' . $data->max_acreage . ' 平米';
      } else if ($data->min_acreage) {
        $data->acreage = $data->min_acreage . ' 平米以上';
      } else if ($data->max_acreage) {
        $data->acreage = $data->max_acreage . ' 平米以内';
      } else {
        $data->acreage = '';
      }

      if (isset($data->avatar) && $data->avatar) {
        $find = strpos($data->avatar, 'http');
        if ($find === false || $find > 0) {
          $data->avatar = '/upload/user/images/60/' . $data->avatar;
        }
      } else {
        $data->avatar = '/static/img/avatar.png';
      }

      $data->key = md5('customer' . $data->id . config('wechat.app_secret'));
      $data->isShare = $data->share_level !== null;
      $data->allowEdit = self::allow($user, $data, 'edit');
      $data->allowTurn = self::allow($user, $data, 'turn');
      $data->allowFollow = self::allow($user, $data, 'follow');
      $data->allowConfirm = self::allow($user, $data, 'confirm');
      $data->allowClash = self::allow($user, $data, 'clash');
      $data->allowDelete = self::allow($user, $data, 'delete');
      $data->linkman = Linkman::getByOwnerId($user, 'customer', $id, true, $data->allowEdit ? -1 : 0);
      $data->log = Log::getList($user, 'customer', $id);
      $data->filter = Filter::query($user, $id);
      $data->recommend = Recommend::query($user, $id);
      $data->confirm = Confirm::query($user, $id, 0);
      $data->shareList = User::shareList('Customer', $id, $data->user_id);

      if ($data->clash && $data->allowClash) {
        $data->clashCustomer = self::alias('a')
          ->leftJoin('user b','b.id = a.user_id')
          ->where('a.id', $data->clash)
          ->field('a.id,a.customer_name as name,a.update_time,b.title as manager')->find();
      }
    }
    return $data;
  }

  /**
   * 撞单检查
   */
  public static function clashCheck($id, $name, $tel, $company_id) {
    if(!$company_id) {
      return false;
    }

    $keyword = mb_substr(preg_replace(self::$IGNORE_WORDS, "", $name), 0, 3, 'utf-8');

    $clashData = self::alias('a')
      ->join('user u', "a.user_id = u.id")
      //->leftJoin('linkman b',"b.type = 'customer' AND b.owner_id = a.id")
      ->where('a.id', '<>', $id)
      ->where('a.company_id', $company_id)
      ->where('(a.clash IS NULL OR a.clash = 0)')
      ->where('(a.parallel IS NULL OR a.parallel = 0)')
      ->where(function ($query) use($keyword, $tel) {
        if ($tel) {
          $query->where("(a.tel = '" .$tel . "' OR a.customer_name like '%" . $keyword . "%')");
        } else {
          $query->where('a.customer_name', 'like', '%' . $keyword . '%');
        }
      })->field('a.id,a.customer_name,a.tel,a.status,a.user_id,u.title as user')
      ->find();

    return $clashData;
  }

  /**
   * 添加/修改客户信息
   */
  public static function addUp($user, $id, $data) {
    $oldData = null;
    $user_id = 0;
    $company_id = 0;
    $checkClash = true;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    if ($id) {
      $oldData = self::getById($user, $id);
      if ($oldData == null) {
        self::exception('客户不存在。');
      } else if (!self::allow($user, $oldData, 'edit')) {
        self::exception('您没有权限修改此客户。');
      }
      if ((isset($data['clash']) && $data['clash'] > 0) || 
        $oldData->clash || $oldData->parallel) {
        $checkClash = false;
      }
    } else if (!self::allow($user, null, 'new')) {
      self::exception('您没有权限添加客户。');
    } else if (isset($data['clash']) && $data['clash'] > 0) {
      $checkClash = false;
    }

    $tel = isset($data['tel']) ? $data['tel'] : '';

    if ((!isset($data['company_id']) || !$data['company_id']) && $company_id) {
      $data['company_id'] = $company_id;
    }

    // 撞单检查
    if ($checkClash && isset($data['company_id']) && $data['company_id'] > 0) {
      $clash = self::clashCheck($id, $data['customer_name'], $tel, $data['company_id']);
      
      if ($clash) {
        $message = '';
        $resultData = [
          'confirm' => false,
          'clash' => $clash->id
        ];

        if ($clash->user_id == $user_id) {
          $message = '客户资料和您的' . self::$status[$clash->status] . '客户：【' .
            $clash->customer_name . '】信息重复，请检查。';
        } else {
          Log::add($user, [
            "table" => "customer",
            "owner_id" => $clash->id,
            "title" => '客户撞单',
            "summary" => $data['customer_name'] . ' ' . $tel
          ]);

          if ($clash->status == 5) {
            self::transfer($user, $clash->id, $user_id, $data);
            $message = '客户资料和 【' . $clash->user . '】 的' . self::$status[$clash->status] . '客户：【' .
              $clash->customer_name . '】 发生撞单，旧客户已自动转交给您并转为' . 
                self::$status[$data['status']] . '客户，请及时跟进。';
          } else {
            $message = '客户资料和 【' . $clash->user . '】 的' . self::$status[$clash->status] . '客户：【' .
              $clash->customer_name . '】 发生撞单，您可以选择【放弃登记】或【申请转交或并行】。' .
              '由管理员按照【核查撞单及覆盖原则】处理。点击【确定】申请转交或并行。';
            $resultData['confirm'] = true;
          }
        }

        return ['message' => $message, 'data' => $resultData];
      }
    }

    if (isset($data['clash']) && $data['clash'] > 0) {
      $checkClash = false;
      // 撞单客户强制公开
      $data['share'] = 1;
    }

    if (isset($data['min_acreage']) && (empty($data['min_acreage']) || $data['min_acreage'] == 'null')) {
      unset($data['min_acreage']);
    }

    if (isset($data['max_acreage']) && (empty($data['max_acreage']) || $data['max_acreage'] == 'null')) {
      unset($data['max_acreage']);
    }

    if (isset($data['current_area']) && (empty($data['current_area']) || $data['current_area'] == 'null')) {
      unset($data['current_area']);
    }

    if (isset($data['settle_date']) && empty($data['settle_date'])) {
      unset($data['settle_date']);
    }

    if (isset($data['end_date']) && empty($data['end_date'])) {
      unset($data['end_date']);
    }

    if ($oldData != null) {
      $summary = '';

      if ($data['customer_name'] != $oldData->customer_name) {
        if ($oldData->customer_name) {
          $summary = '客户名称：' . $oldData->customer_name . ' -> ' . $data['customer_name'] . '\n';
        } else {
          $summary = '客户名称：' . $data['customer_name'] . '\n';
        }
      }

      if ($data['tel'] != $oldData->tel) {
        if ($oldData->tel) {
          $summary = '直线电话：' . $oldData->tel . ' -> ' . $data['tel'] . '\n';
        } else {
          $summary = '直线电话：' . $data['tel'] . '\n';
        }
      }

      if ($data['area'] != $oldData->area) {
        if ($oldData->area) {
          $summary = $summary . '城区：' . $oldData->area . ' -> ' . $data['area'] . '\n';
        } else {
          $summary = $summary . '城区：' . $data['area'] . '\n';
        }
      }

      if ($data['address'] != $oldData->address) {
        if ($oldData->address) {
          $summary = $summary . '详细地址：' . $oldData->address . ' -> ' . $data['address'] . '\n';
        } else {
          $summary = $summary . '详细地址：' . $data['address'] . '\n';
        }
      }

      if ($data['demand'] != $oldData->demand) {
        if ($oldData->demand) {
          $summary = $summary . '需求项目：' . $oldData->demand . ' -> ' . $data['demand'] . '\n';
        } else {
          $summary = $summary . '需求项目：' . $data['demand'] . '\n';
        }
      }

      if ($data['lease_buy'] != $oldData->lease_buy) {
        if ($oldData->lease_buy) {
          $summary = $summary . '租购：' . $oldData->lease_buy . ' -> ' . $data['lease_buy'] . '\n';
        } else {
          $summary = $summary . '租购：' . $data['lease_buy'] . '\n';
        }
      }

      if ($data['district'] != $oldData->district) {
        if ($oldData->district) {
          $summary = $summary . '意向区域：' . $oldData->district . ' -> ' . $data['district'] . '\n';
        } else {
          $summary = $summary . '意向区域：' . $data['district'] . '\n';
        }
      }

      if (isset($data['min_acreage']) && $data['min_acreage'] != $oldData->min_acreage) {
        if ($oldData->min_acreage) {
          $summary = $summary . '最小面积：' . $oldData->min_acreage . '平米 -> ' . $data['min_acreage'] . '平米\n';
        } else {
          $summary = $summary . '最小面积：' . $data['min_acreage'] . '平米\n';
        }
      }

      if (isset($data['max_acreage']) && $data['max_acreage'] != $oldData->max_acreage) {
        if ($oldData->max_acreage) {
          $summary = $summary . '最大面积：' . $oldData->max_acreage . '平米 -> ' . $data['max_acreage'] . '平米\n';
        } else {
          $summary = $summary . '最大面积：' . $data['max_acreage'] . '平米\n';
        }
      }

      if ($data['budget'] != $oldData->budget) {
        if ($oldData->budget) {
          $summary = $summary . '预算：' . $oldData->budget . ' -> ' . $data['budget'] . '\n';
        } else {
          $summary = $summary . '预算：' . $data['budget'] . '\n';
        }
      }

      if (isset($data['settle_date'])) {
        if ($oldData->settle_date) {
          $oldData->settle_date = date_format(date_create($oldData->settle_date), 'Y-m-d');
        }
        if ($data['settle_date'] != $oldData->settle_date) {
          if ($oldData->settle_date) {
            $summary = $summary . '入驻日期：' . $oldData->settle_date . ' -> ' . $data['settle_date'] . '\n';
          } else {
            $summary = $summary . '入驻日期：' . $data['settle_date'] . '\n';
          }
        }
      }

      if (isset($data['current_area']) && $data['current_area'] != $oldData->current_area) {
        if ($oldData->current_area) {
          $summary = $summary . '在驻面积：' . $oldData->current_area . '平米 -> ' . $data['current_area'] . '平米\n';
        } else {
          $summary = $summary . '在驻面积：' . $data['current_area'] . '平米\n';
        }
      }

      if (isset($data['end_date'])) {
        if ($oldData->end_date) {
          $oldData->end_date = date_format(date_create($oldData->end_date), 'Y-m-d');
        }
        if ($data['end_date'] != $oldData->end_date) {
          if ($oldData->end_date) {
            $summary = $summary . '到期日：' . $oldData->end_date . ' -> ' . $data['end_date'] . '\n';
          } else {
            $summary = $summary . '到期日：' . $data['end_date'] . '\n';
          }
        }
      }

      if ($data['rem'] != $oldData->rem) {
        if ($oldData->rem) {
          $summary = $summary . '备注：' . $oldData->rem . ' -> ' . $data['rem'] . '\n';
        } else {
          $summary = $summary . '备注：' . $data['rem'] . '\n';
        }
      }

      if ($data['status'] != $oldData->status) {
        $summary = $summary . '状态：' . self::$status[$oldData->status] . 
          ' -> ' . self::$status[$data['status']] . '\n';
      }

      if (isset($data['company_id']) && $data['company_id'] != $oldData->company_id) {
        $oldCompany = null;
        $newCompany = null;
        if ($oldData->company_id) {
          $oldCompany = Company::get($oldData->company_id);
        }
        if ($data['company_id']) {
          $newCompany = Company::get($data['company_id']);
        }
        if ($oldCompany && $newCompany) {
          $summary = $summary . '所属企业：' . $oldCompany->full_name . '->' . $newCompany->full_name . '\n';
        } else if ($oldCompany) {
          $summary = $summary . '所属企业：' . $oldCompany->full_name . '->\n';
        } else {
          $summary = $summary . '所属企业：' . $newCompany->full_name . '\n';
        }
      }

      if ($data['share'] != $oldData->share) {
        $summary = $summary . '共享：' . self::$share[$oldData->share] . 
          ' -> ' . self::$share[$data['share']] . '\n';
      }

      $result =  $oldData->save($data);
      if ($result && $summary) {
        Log::add($user, [
          "table" => "customer",
          "owner_id" => $id,
          "title" => '修改客户信息',
          "summary" => $summary
        ]);

        // 发送撞单通知
        if (isset($data['clash']) && $data['clash'] > 0 && !$oldData->parallel) {
          $company = Company::get($data['company_id']);
          if ($company) {
            $admin_id = $company->user_id;
            $message = $user->title . '登记的客户“' . $data['customer_name'] . '”发生撞单，已申请并行或强行转交，请及时处理。';
            $url = 'https://' . config('app_host') . '/app/customer/view/'. $id;
            User::pushMessage($admin_id, $message, $url);
          }
        }
      }

      return $id;
    } else {
      $data['city'] = self::$city;
      $data['user_id'] = $user_id;

      $linkman = null;
      if (isset($data['linkman'])) {
        if ($data['linkman']) {
          $linkman['title'] = $data['linkman'];
        }
        unset($data['linkman']);
      }

      // if (isset($data['mobile'])) {
      //   if ($data['mobile']) {
      //     $linkman['mobile'] = $data['mobile'];
      //   }
      //   unset($data['mobile']);
      // }

      $newData = new Customer($data);
      $result = $newData->save();

      if ($result) {
        Log::add($user, [
          "table" => "customer",
          "owner_id" => $newData->id,
          "title" => '登记客户',
          "summary" => $newData->customer_name
        ]);

        if ($linkman) {
          $linkman['type'] = 'customer';
          $linkman['owner_id'] = $newData->id;
          Linkman::addUp($user, 0, $linkman);
        }

        // 发送撞单通知
        if (isset($data['clash']) && $data['clash'] > 0) {
          $company = Company::get($data['company_id']);
          if ($company) {
            $admin_id = $company->user_id;
            $message = $user->title . '登记的客户“' . $data['customer_name'] . '”发生撞单，已申请并行或强行转交，请及时处理。';
            $url = 'https://' . config('app_host') . '/app/customer/view/'. $newData->id;
            User::pushMessage($admin_id, $message, $url);
          }
        }

        return $newData->id;
      } else {
        return false;
      }
    }
  }

  /**
   * 变更客户状态
   */
  public static function changeStatus($user, $id, $status) {
    $customer = self::get($id);
    if ($customer == null) {
      return true;
    } else if (!self::allow($user, $customer, 'edit')) {
      self::exception('您没有权限。');
    } else if ($customer->status == $status) {
      return true;
    }

    $log = [
      "table" => 'customer',
      "owner_id" => $customer->id,
      "title" => '修改客户状态',
      "summary" => self::$status[$customer->status] . ' -> ' . self::$status[$status]
    ];

    $customer->status = $status;
    $result = $customer->save();
    if ($result) {
      Log::add($user, $log);
    }

    return $result;
  }

  // 转交客户
  public static function transfer($user, $id, $to_user, $data = null, $checkRight = false) {
    $customer = self::alias('a')
      ->join('user u', "a.user_id = u.id")
      ->field('a.id,a.user_id,a.company_id,a.clash,a.parallel,a.status,u.title')
      ->where('a.id', $id)
      ->find();

    if ($customer == null) {
      self::exception('客户不存在。');
    } else if ($checkRight && !self::allow($user, $customer, 'turn')) {
      self::exception('您没有权限转交这个客户。');
    } else if ($customer->user_id == $to_user) {
      return true;
    }

    $newUser = User::get($to_user);
    if ($newUser == null) {
      self::exception('用户不存在。');
    }

    $summary = $customer->title . ' -> ' . $newUser->title;

    $customer->user_id = $to_user;
    if ($data != null && isset($data['status']) && $customer->status != $data['status']) {
      $summary = $summary . '\n客户状态：' . self::$status[$customer->status] . ' -> ' . self::$status[$data['status']];
      $customer->status = $data['status'];
    }
    if ($data != null && isset($data['company_id']) && !empty($data['company_id'])) {
      $customer->company_id = $data['company_id'];
    }
    
    $result = $customer->save();
    if ($result) {
      Log::add($user, [
        "table" => 'customer',
        "owner_id" => $customer->id,
        "title" => '转交客户',
        "summary" => $summary
      ]);
    }

    return $result;
  }

  // 移除共享
  public static function removeShare($user, $id, $user_id) {
    $customer = self::getById($user, $id);

    if ($customer == null) {
      self::exception('客户不存在。');
    } else if (!self::allow($user, $customer, 'edit') && $user->id != $user_id) {
      self::exception('您没有权限修改客户共享。');
    }

    $sUser = User::get($user_id);
    if ($sUser == null) {
      self::exception('用户不存在。');
    }

    $result = db('share')
      ->where('type', 'customer')
      ->where('object_id', $id)
      ->where('user_id', $user_id)
      ->delete();
    
    if ($result) {
      Log::add($user, [
        "table" => 'customer',
        "owner_id" => $customer->id,
        "title" => '移除共享',
        "summary" => $sUser->title
      ]);
    }

    return $result;
  }

  /**
   * 撞单处理
   */
  public static function clashPass($user, $id, $operate) {
    $customer = self::get($id);

    if ($customer == null) {
      self::exception('客户不存在。');
    } else if (!$customer->clash) {
      self::exception('非撞单客户无需处理。');
    } else if (!self::allow($user, $customer, 'clash')) {
      self::exception('您没有权限处理这个撞单客户。');
    }

    $result = false;
    $clashCustomer = self::get($customer->clash);
    $message = '';
    $url = '';

    if ($clashCustomer == null) {
      self::exception('被撞单客户不存在。');
    } else if ($operate == 0) {   // 强行转交
      $summary = '撞单客户强行转交';
      $result = self::transfer($user, $clashCustomer->id, $customer->user_id, ['status' => 1]);
      if ($result) {
        self::remove($user, $id);
      }
      $message = '您登记的撞单客户已由管理员转交给您，请及时跟进。';
      $url = 'https://' . config('app_host') . '/app/customer/view/' . $clashCustomer->id;
    } else if ($operate == 1) {   // 并行处理
      $summary = '撞单客户并行处理';
      $customer->parallel = $clashCustomer->id;
      $customer->clash = 0;
      $result = $customer->save();
      $message = '您登记的撞单客户已由管理员并行处理，请及时跟进。';
      $url = 'https://' . config('app_host') . '/app/customer/view/' . $customer->id;
    } else if ($operate == 2) {   // 驳回
      $summary = '撞单客户驳回';
      $result = self::remove($user, $id);
      $message = '您登记的撞单客户已被管理员驳回，由其他客户经理在跟进中。';
    }

    if ($result) {
      Log::add($user, [
        "table" => 'customer',
        "owner_id" => $customer->id,
        "title" => '转交客户',
        "summary" => $summary
      ]);

      User::pushMessage($customer->user_id, $message, $url);
    }

    return $result;
  }

  /**
   * 删除客户
   */
  public static function remove($user, $id) {
    $customer = self::get($id);
    if ($customer == null) {
      return true;
    } else if (!self::allow($user, $customer, 'delete')) {
      self::exception('您没有权限删除此客户。');
    }
    $log = [
      "table" => 'customer',
      "owner_id" => $customer->id,
      "title" => '删除客户',
      "summary" => $customer->customer_name
    ];
    $result = $customer->delete();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 批量导入客户
   */
  public static function import($user, $data) {
    if (!self::allow($user, null, 'new')) {
      self::exception('您没有权限添加客户。');
    }

    $excel = new Excel();
    $rowData = $excel->getData($data);

    if (!$rowData) {
      self::exception($excel->getError());
    }

    if (count($rowData) < 1 || count($rowData[0]) < 15) {
      self::exception('导入数据不完整，请使用导入模板。');
    }

    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    $succCount = 0;
    $clashCount = 0;
    $failCount = 0;

    foreach ($rowData as $row) {
      $customer = [
        'customer_name' => $row[0],
        'linkman' => $row[1],
        'tel' => $row[2],
        'area' => $row[3],
        'address' => $row[4] . $row[5],
        'demand' => $row[6],
        'lease_buy' => $row[7],
        'min_acreage' => $row[8],
        'max_acreage' => $row[9],
        'budget' => $row[10],
        'settle_date' => $row[11],
        'current_area' => $row[12],
        'end_date' => $row[13],
        'rem' => $row[14] . $row[15]
      ];

      if ($customer['customer_name']) {
        foreach($customer as $k=>$v) {
          if ($v == '' || $v == null || $v == 'null' || $v == 'NULL') {
            unset($customer[$k]);
          }
        }

        if ($company_id) {
          $tel = '';
          if (isset($customer['tel'])) {
            $tel = $customer['tel'];
          }
          $clash = self::clashCheck(0, $customer['customer_name'], $tel, $company_id);
          if ($clash) {
            $clashCount++;
            $customer['clash'] = $clash->id;
            $customer['share'] = 1;
            //continue;
          }
        }

        $customer['city'] = self::$city;
        $customer['remind'] = 8;
        $customer['user_id'] = $user_id;
        $customer['company_id'] = $company_id;
        $customer['status'] = 6; // 名录
        $customer['share'] = 0;

        if (isset($customer['settle_date'])) {
          $n = intval(($customer['settle_date'] - 25569) * 3600 * 24);
          $customer['settle_date'] = gmdate('Y-m-d', $n);
        }

        if (isset($customer['end_date'])) {
          $n = intval(($customer['end_date'] - 25569) * 3600 * 24);
          $customer['end_date'] = gmdate('Y-m-d', $n);
        }

        $linkman = null;

        if (isset($customer['linkman'])) {
          $linkman['type'] = 'customer';
          $linkman['title'] = $customer['linkman'];
          // if (Validate::isMobile($customer['tel'])) {
          //  $linkman['mobile'] = $customer['tel'];
          // } else {
          //   $linkman['tel'] = $customer['tel'];
          // }
          unset($customer['linkman']);
          //unset($customer['tel']);
        }

        $newData = new Customer($customer);
        $result = $newData->save();

        if ($result) {
          Log::add($user, [
            "table" => "customer",
            "owner_id" => $newData->id,
            "title" => '导入客户',
            "summary" => $newData->customer_name
          ]);
          if ($linkman) {
            $linkman['owner_id'] = $newData->id;
            Linkman::addUp($user, 0, $linkman);
          }
          $succCount++;
        } else {
          $failCount++;
        }
      } else {
        $failCount++;
      }
    }

    return [
      'success' => $succCount,
      'clash' => $clashCount,
      'fail' => $failCount
    ];
  }

  /**
   * 导出客户
   */
  public static function export($user, $type) {
    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    $title = '客户';

    $list = db('customer')->alias('a')
      ->leftJoin('user_company b', 'a.user_id = b.user_id and a.company_id = b.company_id and b.status = 1')
      //->leftJoin('linkman c', 'c.type = "customer" AND c.owner_id = a.id')
      ->where('a.user_id = ' . $user_id .
        ' OR (a.share = 1 AND a.company_id > 0 AND a.company_id = ' . $company_id . ')' .
        ' OR (a.company_id > 0 AND a.company_id = ' . $company_id . ' AND b.superior_id = ' . $user_id . ')');

    if ($type == 'potential') {
      $title = '潜在客户';
      $list->where('a.status', '0');
    } else if ($type == 'pool') {
      $title = '客户池';
      $list->where('a.status', 'in', '4,5,6');
    } else if ($type == 'follow') {
      $title = '跟进客户';
      $list->where('a.status', 'in', '1,2,3');
    }

    $result = $list->field('a.id,a.customer_name,a.tel,a.area,a.address,a.demand,' .
      'a.lease_buy,a.min_acreage,a.max_acreage,a.budget,a.settle_date,a.current_area,a.end_date,a.rem')
      ->order('a.id', 'asc')
      ->select();

    $excel = new Excel();
    $excel->export($title, [
      '客户名称',
      '直线电话',
      '城区',
      '地址',
      '需求项目',
      '租购',
      '最小面积(平方米)',
      '最大面积(平方米)',
      '预算',
      '入驻日期',
      '在驻面积(平方米)',
      '到期日',
      '备注'
    ], $result, 'id');
  }
}