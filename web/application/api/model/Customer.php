<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Linkman;
use app\api\model\Filter;
use app\api\model\User;
use app\api\model\Recommend;
use app\api\model\Confirm;

class Customer extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $status = ['潜在','跟进','看房','确认','成交','失败'];
  public static $share = ['私有','共享'];
  private static $IGNORE_WORDS = '/北京|上海|深圳|广州|中国|美国|日本|德国|英国|法国|（|）|\(|\)/';
  
  /**
   * 检索客户信息
   */
  public static function search($filter, $user_id = 0, $company_id = 0) {
    if (!isset($filter['page'])) {
      $filter['page'] = 1;
    }

    if (!isset($filter['page_size'])) {
      $filter['page_size'] = 10;
    }

    if (!$user_id) {
      $user_id = 0;
    }

    if (!$company_id) {
      $company_id = 0;
    }
    
    $list = self::where('city', self::$city)
      ->where('user_id = ' . $user_id . ' OR (share = 1 AND company_id > 0 AND company_id = ' . $company_id . ')');

    if (isset($filter['keyword']) && $filter['keyword'] != '') {
      $list->where('customer_name', 'like', '%' . $filter['keyword'] . '%');
    }

    if (isset($filter['status']) && $filter['status'] != '') {
      $list->where('status', 'in', $filter['status']);
    }

    if (isset($filter['clash'])) {
      if ($filter['clash']) {
        $list->where('clash', '>', 0);
      } else {
        $list->where('clash', ['exp', 'IS NULL'], ['=', 0], 'or');
      }
    }

    $result = $list->field('id,customer_name,logo,area,address,demand,lease_buy,min_acreage,max_acreage,budget,status,clash')
      ->page($filter['page'], $filter['page_size'])
      ->order('id', 'desc')
      ->select();

    return $result;
  }

  /**
   * 获取客户详细信息
   */
  public static function detail($id, $user_id = 0, $company_id = 0) {
    $data = self::alias('a')
      ->leftJoin('user b','b.id = a.user_id')
      ->leftJoin('company c','c.id = a.company_id')
      ->where('a.id', $id)
      ->field('a.*,b.title as manager,b.avatar,b.mobile,c.title as company')
      ->find();

    if ($data == null) {
      self::exception('客户不存在。');
    }

    if ($data->user_id != $user_id) {
      if (!($data->share == 1 && $data->company_id == $company_id)) {
        self::exception('您没有权限查看此客户。');
      }
    }

    if ($data->min_acreage && $data->max_acreage) {
      $data->acreage = $data->min_acreage . ' 至 ' . $data->max_acreage . ' 平米';
    } else if ($data->min_acreage) {
      $data->acreage = $data->min_acreage . ' 平米以上';
    } else if ($data->max_acreage) {
      $data->acreage = $data->max_acreage . ' 平米以内';
    } else {
      $data->acreage = '';
    }

    User::formatUserInfo($data);
    $data->linkman = Linkman::getByOwnerId($id, 'customer', $user_id);
    $data->log = Log::getList('customer', $id, $user_id);
    $data->filter = Filter::query($id, $user_id);
    $data->recommend = Recommend::query($id, $user_id);
    $data->confirm = Confirm::query($id, 0, 0);

    return $data;
  }

  /**
   * 撞单检查
   */
  private static function clashCheck($id, $name, $mobile, $company_id) {
    if(!$company_id) {
      return false;
    }

    $keyword = mb_substr(preg_replace(self::$IGNORE_WORDS, "", $name), 0, 3, 'utf-8');

    $clashData = self::alias('a')
      ->join('user u', "a.user_id = u.id")
      ->leftJoin('linkman b',"b.type = 'customer' AND b.owner_id = a.id")
      ->where('a.id', '<>', $id)
      ->where('a.company_id', $company_id)
      ->where('a.clash', ['exp', 'IS NULL'], ['=', 0], 'or')
      ->where(function ($query) use($keyword, $mobile) {
          $query->where('a.customer_name', 'like', '%' . $keyword . '%');
          if ($mobile) {
            $query->whereOr('b.mobile', '=', $mobile);
          } 
      })->field('a.id,a.customer_name,a.status,a.user_id,u.title as user,b.title as linkman,b.mobile')
      ->find();
    
    return $clashData;
  }

  /**
   * 添加/修改客户信息
   */
  public static function addUp($id, $data, $user_id, $company_id = 0) {
    $oldData = null;

    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('客户不存在。');
      } else if ($oldData->user_id != $user_id) {
        self::exception('您没有权限修改此客户。');
      } else if ($oldData->clash > 0) {
        return $this->exception('撞单客户不可修改，请等待管理员处理。');
      }
    }

    $mobile = isset($data['mobile']) ? $data['mobile'] : '';

    // 撞单检查
    if (!isset($data['clash']) || $data['clash'] == 0) {
      $clash = self::clashCheck($id, $data['customer_name'], $mobile, $company_id);
      
      if ($clash) {
        $message = '';
        $resultData = [
          'confirm' => false,
          'clash' => $clash->id
        ];

        if ($clash->user_id == $user_id) {
          $message = '客户资料和您的' . self::$status[$clash->status] . '客户：<strong>' .
            $clash->customer_name . '</strong> 信息重复，请检查。';
        } else {
          Log::add([
            "table" => "customer",
            "owner_id" => $clash->id,
            "title" => '客户撞单',
            "summary" => $data['customer_name'] . ' ' . $mobile,
            "user_id" => $user_id
          ]);

          if ($clash->status == 5) {
            self::transfer($clash->id, $user_id, $user_id, $data);
            $message = '客户资料和<strong>' . $clash->user . '</strong>的' . self::$status[$clash->status] . '客户：<strong>' .
              $clash->customer_name . '</strong> 发生撞单，旧客户已自动转交给您并转为' . self::$status[$data['status']] . '客户，请及时跟进。';
          } else {
            $message = '客户资料和<strong>' . $clash->user . '</strong>的' . self::$status[$clash->status] . '客户：<strong>' .
              $clash->customer_name . '</strong> 发生撞单，您可以选择<strong>放弃登记</strong>或<strong>申请转交或并行</strong>，由管理员按照<strong>核查撞单及覆盖原则</strong>处理。点击<strong>确定</strong>申请转交或并行。';
            $resultData['confirm'] = true;
          }
        }

        return ['message' => $message, 'data' => $resultData];
      }
    } else {
      // 撞单客户强制公开
      $data['share'] = 1;
    }

    if (empty($data['settle_date'])) {
      unset($data['settle_date']);
    }

    if (empty($data['end_date'])) {
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

      if ($data['min_acreage'] != $oldData->min_acreage) {
        if ($oldData->min_acreage) {
          $summary = $summary . '最小面积：' . $oldData->min_acreage . '平米 -> ' . $data['min_acreage'] . '平米\n';
        } else {
          $summary = $summary . '最小面积：' . $data['min_acreage'] . '平米\n';
        }
      }

      if ($data['max_acreage'] != $oldData->max_acreage) {
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

      if ($data['current_area'] != $oldData->current_area) {
        if ($oldData->current_area) {
          $summary = $summary . '在驻面积：' . $oldData->current_area . '平米 -> ' . $data['current_area'] . '平米\n';
        } else {
          $summary = $summary . '在驻面积：' . $data['current_area'] . '平米\n';
        }
      }

      if ($oldData->end_date) {
        $oldData->end_date = substr($oldData->end_date, 0, 10);
      }

      if (empty($data['end_date'])) {
        if ($oldData->end_date) {
          $summary = $summary . '到期日：' . $oldData->end_date . ' ->\n';
        }
      } else if ($data['end_date'] != $oldData->end_date) {
        if ($oldData->end_date) {
          $summary = $summary . '到期日：' . $oldData->end_date . ' -> ' . $data['end_datesettle_date'] . '\n';
        } else {
          $summary = $summary . '到期日：' . $data['end_date'] . '\n';
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

      if ($data['share'] != $oldData->share) {
        $summary = $summary . '共享：' . self::$share[$oldData->share] . 
          ' -> ' . self::$share[$data['share']] . '\n';
      }

      $result =  $oldData->save($data);
      if ($result && $summary) {
        Log::add([
          "table" => "customer",
          "owner_id" => $id,
          "title" => '修改客户信息',
          "summary" => $summary,
          "user_id" => $user_id
        ]);
      }
      return $id;
    } else {
      $data['city'] = self::$city;
      $data['user_id'] = $user_id;
      if (!isset($data['company_id']) && $company_id) {
        $data['company_id'] = $company_id;
      } else if (!$data['company_id'] && $company_id) {
        $data['company_id'] = $company_id;
      }

      $linkman = null;
      if (isset($data['linkman'])) {
        if ($data['linkman']) {
          $linkman['title'] = $data['linkman'];
        }
        unset($data['linkman']);
      }

      if (isset($data['mobile'])) {
        if ($data['mobile']) {
          $linkman['mobile'] = $data['mobile'];
        }
        unset($data['mobile']);
      }

      $newData = new Customer($data);
      $result = $newData->save();

      if ($result) {
        Log::add([
          "table" => "customer",
          "owner_id" => $newData->id,
          "title" => '登记客户',
          "summary" => $newData->customer_name,
          "user_id" => $user_id
        ]);

        if ($linkman) {
          $linkman['type'] = 'customer';
          $linkman['owner_id'] = $newData->id;
          Linkman::addUp(0, $linkman, $user_id);
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
  public static function changeStatus($id, $status, $user_id) {
    $customer = self::get($id);
    if ($customer == null) {
      return true;
    } else if ($customer->user_id != $user_id) {
      self::exception('您没有权限。');
    } else if ($customer->status == $status) {
      return true;
    }

    $log = [
      "table" => 'customer',
      "owner_id" => $customer->id,
      "title" => '修改客户状态',
      "summary" => self::$status[$customer->status] . ' -> ' . self::$status[$status],
      "user_id" => $user_id
    ];

    $customer->status = $status;
    $result = $customer->save();
    if ($result) {
      Log::add($log);
    }

    return $result;
  }

  // 转交客户
  public static function transfer($id, $to_user, $user_id, $data = null) {
    $customer = self::alias('a')
      ->join('user u', "a.user_id = u.id")
      ->field('a.id,a.user_id,a.status,u.title')->find();

    if ($customer == null) {
      self::exception('客户不存在。');
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
    $result = $customer->save();
    if ($result) {
      Log::add([
        "table" => 'customer',
        "owner_id" => $customer->id,
        "title" => '转交客户',
        "summary" => $summary,
        "user_id" => $user_id
      ]);
    }

    return $result;
  }

  /**
   * 删除客户
   */
  public static function remove($id, $user_id) {
    $customer = self::get($id);
    if ($customer == null) {
      return true;
    } else if ($customer->user_id != $user_id) {
      self::exception('您没有权限删除此客户。');
    }
    $log = [
      "table" => 'customer',
      "owner_id" => $customer->id,
      "title" => '删除客户',
      "summary" => $customer->customer_name,
      "user_id" => $user_id
    ];
    $result = $customer->delete();
    if ($result) {
      Log::add($log);
    }
    return $result;
  }
}