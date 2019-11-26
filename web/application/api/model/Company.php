<?php
namespace app\api\model;

use think\facade\Validate;
use think\model\concern\SoftDelete;
use app\common\Sms;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\User;

class Company extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $joinWay = ['开放加入','需管理员审核加入', '需通过邀请加入'];
  public static $status = ['公开','隐藏'];

  /**
   * 获取加入成员统计
   */
  protected static function setAddinCount($company) {
    $company->wait = 0;
    $company->addin = 0;

    $count = db('user_company')
      ->alias('a')
      ->join('user b', 'a.user_id = b.id')
      ->where('a.company_id', $company->id)
      ->field('a.status,count(a.user_id) as count')
      ->group('a.status')
      ->select();

    foreach($count as $c) {
      if ($c['status'] == 0) {
        $company->wait = $c['count'];
      } else if ($c['status'] == 1) {
        $company->addin = $c['count'];
      }
    }
  }

  /**
   * 格式化列表信息
   */
  protected static function formatList($list, $count = true) {
    foreach($list as $company) {
      if ($company->logo) {
        $company->logo = '/upload/company/images/60/' . $company->logo;
      } else {
        $company->logo = '/static/img/null.png';
      }
      if ($count) {
        self::setAddinCount($company);
      }
    }
    return $list;
  }

  /**
   * 权限检查
   */
  public static function allow($user, $company, $operate) {
    if ($user == null) {
      return false;
    } else if ($company == null && $operate != 'new') {
      return false;
    }
    if ($operate == 'view' || $operate == 'new') {
      return true;
    } else if ($operate == 'edit' || $operate == 'invite' || $operate == 'pass') {
      return $company->user_id == $user->id;
    } else if ($operate == 'delete') {
      return false;
    } else {
      return false;
    }
  }

  /**
   * 检索公开企业
   */
  public static function search($user, $keyword, $page = 1) {
    $list = self::where('status', 1);

    if ($keyword) {
      $list = $list->where('title|full_name', 'like', '%' . $keyword . '%');
    }

    $list = $list ->field('id,title,logo,area,address,create_time')
      ->page($page, 10)
      ->order('id', 'asc')
      ->select();

    return self::formatList($list, false);
  }
  
  /**
   * 已加入的企业
   */
  public static function my($user, $status = 1) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }
    $list = self::alias('a')
      ->join('user_company b', 'a.id = b.company_id and b.status = ' . $status)
      ->where('b.user_id', $user_id)
      ->field('a.id,a.title,a.logo,a.area,a.address,a.create_time,b.active')
      ->order('a.id', 'asc')
      ->select();

    return self::formatList($list, false);
  }

  /**
   * 邀请待加入的企业
   */
  public static function inviteMe($user) {
    if (!$user) {
      return null;
    }

    $list = self::alias('a')
      ->join('invite b', 'a.id = b.company_id and b.status = 0')
      ->where('b.mobile', $user->mobile)
      ->where('a.id', 'NOT IN', function ($query) use($user) {
        $query->table('tbl_user_company')->where('user_id', $user->id)->field('company_id');
      })
      ->field('a.id,a.title,a.logo,a.area,a.address,a.create_time')
      ->order('a.id', 'asc')
      ->select();

    return self::formatList($list, false);
  }

  /**
   * 我创建的企业
   */
  public static function myCreate($user) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }
    $list = self::where('user_id', $user_id)
      ->field('id,title,logo,area,address,status,create_time')
      ->order('id', 'asc')
      ->select();
    
    return self::formatList($list);
  }

  /**
   * 获取企业详细信息
   */
  public static function detail($user, $id, $operate = 'view') {
    $data = self::where('id', $id)
      ->field('id,title,full_name,logo,stamp,enable_stamp,area,address,rem,' .
        'user_id,join_way,status')
      ->find();

    if ($data == null) {
      self::exception('企业不存在。');
    } else if (!self::allow($user, $data, $operate)) {
      self::exception('您没有权限' . ($operate == 'view' ? '查看' : '修改') . '该企业。');
    }
    
    if ($data->logo) {
      $data->logo = '/upload/company/images/200/' . $data->logo;
    }
    if ($data->stamp) {
      $data->stamp = '/upload/company/images/60/' . $data->stamp;
    }

    if ($operate == 'view') {
      $data->allowEdit = self::allow($user, $data, 'edit');
      $data->allowInvite = self::allow($user, $data, 'invite');
      $data->allowPass = self::allow($user, $data, 'pass');
      $data->allowDelete = self::allow($user, $data, 'delete');
      $data->isAddin = false;
      $data->isInvtie = false;
      if ($user) {
        $joinStatus = self::getJoinStatus($user, $id);
        if ($joinStatus) {
          $data->isAddin = $joinStatus['status'];
        }
        if ($data->allowPass) {
          $data->waitUser = User::companyMember($user, $id);
        }
        if (db('invite')
          ->where('mobile', $user->mobile)
          ->where('company_id', $id)
          ->where('status', 0)
          ->find()) {
          $data->isInvtie = true;  
        }
      }
      self::setAddinCount($data);
    }    
    return $data;
  }

  /**
   * 添加/修改企业信息
   */
  public static function addUp($user, $id, $data, $logo, $stamp) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('企业不存在。');
      }else if (!self::allow($user, $oldData, 'edit')) {
        self::exception('您没有权限修改此企业。');
      }

      $summary = '';

      if ($data['title'] != $oldData->title) {
        if ($oldData->title) {
          $summary = '企业简称：' . $oldData->title . ' -> ' . $data['title'] . '\n';
        } else {
          $summary = '企业简称：' . $data['title'] . '\n';
        }
      }

      if ($data['full_name'] != $oldData->full_name) {
        if ($oldData->title) {
          $summary = '企业全称：' . $oldData->full_name . ' -> ' . $data['full_name'] . '\n';
        } else {
          $summary = '企业全称：' . $data['full_name'] . '\n';
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

      if ($data['rem'] != $oldData->rem) {
        if ($oldData->rem) {
          $summary = $summary . '企业介绍：' . $oldData->rem . ' -> ' . $data['rem'] . '\n';
        } else {
          $summary = $summary . '企业介绍：' . $data['rem'] . '\n';
        }
      }

      if ($data['join_way'] != $oldData->join_way) {
        $summary = $summary . '加入方式：' . self::$joinWay[$oldData->join_way] . 
          ' -> ' . self::$joinWay[$data['join_way']] . '\n';
      }

      if ($data['status'] != $oldData->status) {
        $summary = $summary . '是否公开：' . self::$status[$oldData->status] . 
          ' -> ' . self::$status[$data['status']] . '\n';
      }

      if ($logo) {
        $logoPath = self::uploadLogo($logo);
        if ($logoPath) {
          $data['logo'] = $logoPath;
          $summary = $summary . 'Logo：' . $oldData->logo . ' -> ' . $data['logo'] . '\n';
        }
      } else if (isset($data['logo'])) {
        unset($data['logo']);
      }

      if ($stamp) {
        $stampPath = self::uploadStamp($stamp);
        if ($stampPath) {
          $data['stamp'] = $stampPath;
          $summary = $summary . '公章：' . $oldData->stamp . ' -> ' . $data['stamp'] . '\n';
        }
      } else if (isset($data['stamp'])) {
        unset($data['stamp']);
      }

      $result = $oldData->save($data);

      if ($result && $summary) {
        Log::add($user, [
          "table" => "company",
          "owner_id" => $id,
          "title" => '修改企业信息',
          "summary" => $summary
        ]);
      }
      return $id;
    } else if (!self::allow($user, null, 'new')) {
      self::exception('您没有权限添加企业。');
    } else {
      $data['city'] = self::$city;
      $data['user_id'] = $user_id;

      if ($logo) {
        $logoPath = self::uploadLogo($logo);
        if ($logoPath) {
          $data['logo'] = $logoPath;
        }
      } else if (isset($data['logo'])) {
        unset($data['logo']);
      }

      if ($stamp) {
        $stampPath = self::uploadStamp($stamp);
        if ($stampPath) {
          $data['stamp'] = $stampPath;
        }
      } else if (isset($data['stamp'])) {
        unset($data['stamp']);
      }

      $newData = new Company($data);
      $result = $newData->save();

      if ($result) {
        Log::add($user, [
          "table" => "company",
          "owner_id" => $newData->id,
          "title" => '添加企业',
          "summary" => $newData->title
        ]);

        // 自动加入企业
        self::addin($user, $newData->id);
        
        return $newData->id;
      } else {
        return false;
      }
    }
  }

  /**
   * 更换企业管理员
   */
  public static function turn($manager, $id, $user_id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    }
    if (!self::allow($manager, $company, 'edit')) {
      self::exception('您没有权限。');
    }

    $user = User::get($user_id);
    if ($user == null) {
      self::exception('用户不存在。');
    }

    $joinStatus = self::getJoinStatus($user, $id);
    if ($joinStatus == null || $joinStatus['status'] == 0) {
      self::exception('该用户没有加入企业。');
    }

    $result = $company->save(['user_id' => $user_id]);

    if ($result) {
      Log::add($manager, [
        "table" => "company",
        "owner_id" => $company->id,
        "title" => '更换管理员',
        "summary" => $user->title
      ]);

      $message = $manager->title . '已将企业“' . $company->title . '”管理员权限转交给你。';
      $url = 'https://' . config('app_host') . '/app/company/view/' . $company->id;
      User::pushMessage($user->id, $message, $url);
    }

    return $result;
  }

  /**
   * 删除企业
   */
  public static function remove($user, $id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    }
    if (!self::allow($user, $company, 'delete')) {
      self::exception('您没有权限删除此企业。');
    }
    
    $joinStatus = self::getJoinStatus($user, $id);
    
    if ($joinStatus) {
      self::exception('已加入的企业不能删除。');
    }
    $log = [
      "table" => 'company',
      "owner_id" => $company->id,
      "title" => '删除企业',
      "summary" => $company->title
    ];
    $result = $company->delete();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 发出邀请
   */
  public static function invite($user, $id, $mobile) {
    if (!Validate::checkRule($mobile, 'mobile')) {
      self::exception('手机号码无效。');
    }

    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if (!self::allow($user, $company, 'invite')) {
      self::exception('您没有权限发出邀请。');
    }

    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $inviteUser = User::where('mobile', $mobile)->find();
    if ($inviteUser != null) {
      $joinStatus = self::getJoinStatus($inviteUser,$id);
      if ($joinStatus != null) {
        self::exception('对方已加入企业，无需邀请。');
      }
    }

    $data = [
      'mobile' => $mobile, 
      'company_id' => $id, 
      'user_id' => $user_id,
      'status' => 0,
      'create_time' => date("Y-m-d H:i:s",time())
    ];
    $result = db('invite')->insert($data);

    if ($result) {
      $log = [
        "table" => 'company',
        "owner_id" => $id,
        "title" => '邀请用户加入',
        "summary" => $mobile
      ];
      Log::add($user, $log);

      // 发送短信
      $sender = new Sms();
      $smsResult = $sender->sendInvite($mobile, $user->title, $company->title);

      return $result;
    } else {
      return false;
    }
  }

  /**
   * 切换企业
   */
  public static function setActive($user, $id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if ($user == null) {
      self::exception('用户无效。');
    }
    
    $user_id = $user->id;
    $joinStatus = self::getJoinStatus($user, $id);
    if ($joinStatus) {
      if ($joinStatus['active'] == 1) return 1;

      db('user_company')
        ->where('user_id', $user_id)
        ->where('active', 1)
        ->update(['active' => 0]);

      $result = db('user_company')
        ->where('user_id', $user_id)
        ->where('company_id', $id)
        ->update(['active' => 1]);

      if ($result) {
        $log = [
          "table" => 'company',
          "owner_id" => $id,
          "title" => '切换企业',
          "summary" => $company->title
        ];
        Log::add($user, $log);
      }
      return $result;
    } else {
      return false;
    }
  }

  /**
   * 加入企业
   */
  public static function addin($user, $id, $confirm = false) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if ($user == null) {
      self::exception('用户无效。');
    }

    $user_id = $user->id;
    $joinStatus = self::getJoinStatus($user, $id);
    if ($joinStatus != null) {
      return $joinStatus['status'];
    }

    $active = 0;
    $status = 1;
    $invite = db('invite')->where('company_id', $id)
      ->where('mobile', $user->mobile)
      ->where('status', 0)
      ->find();
    
    if ($company->user_id != $user_id) {
      if ($company->join_way > 1 && $invite == null && $confirm == false) {
        self::exception('该企业需要通过邀请加入。');
      } else if ($company->join_way == 1 && $invite == null) {
        $status = 0;
      }
    }

    $hasActive = db('user_company')
      ->where('user_id', $user_id)
      ->where('active', 1)->find();

    if ($hasActive == null) {
      $active = 1;
    }

    $joinData = [ 
      'user_id' => $user_id, 
      'company_id' => $id, 
      'active' => $active,
      'status' => $status,
      'create_time' => date("Y-m-d H:i:s",time())
    ];
    $result = db('user_company')->insert($joinData);

    if ($result) {
      // 更改邀请状态
      if ($invite != null) {
        db('invite')->where('id', $invite['id'])
          ->update(['status' => 1]);
      }

      $log = [
        "table" => 'company',
        "owner_id" => $id,
        "title" => '加入企业',
        "summary" => $company->title
      ];
      Log::add($user, $log);

      if ($status == 0) {
        $message = $user->title . '申请加入“' . $company->title . '”，请及时审核。';
        $url = 'https://' . config('app_host') . '/app/company/view/' . $company->id;
        User::pushMessage($company->user_id, $message, $url);
      }

      return $status;
    } else {
      return false;
    }
  }

  /**
   * 退出企业
   */
  public static function quit($user, $id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if ($user == null) {
      self::exception('用户无效。');
    } else if ($company->user_id == $user->id) {
      self::exception('管理员不能退出企业。');
    }

    $user_id = $user->id;
    $result = db('user_company')
      ->where('user_id', $user_id)
      ->where('company_id', $id)->delete();

    if ($result) {
      $log = [
        "table" => 'company',
        "owner_id" => $id,
        "title" => '退出企业',
        "summary" => $company->title
      ];
      Log::add($user, $log);

      $active = db('user_company')
        ->where('user_id', $user_id)
        ->where('active', 1)->find();
      
      if ($active == null) {
        $active = db('user_company')
        ->where('user_id', $user_id)
        ->order('create_time', 'desc')
        ->find();

        if ($active != null) {
          db('user_company')
            ->where('user_id', $active['user_id'])
            ->where('company_id', $active['company_id'])
            ->update(['active' => 1]);
        }
      }
    }
    return $result;
  }

  /**
   * 获取加入企业状态
   */
  public static function getJoinStatus($user, $id) {
    return db('user_company')
      ->where('user_id', $user->id)
      ->where('company_id', $id)->find();
  }

  /**
   * 加入企业审核通过
   */
  public static function passAddin($manager, $id, $user_id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if (!self::allow($manager, $company, 'pass')) {
      self::exception('您没有权限审核。');
    }

    $user = User::get($user_id);
    if ($user == null) {
      self::exception('用户不存在。');
    }

    $joinStatus = self::getJoinStatus($user, $id);
    if ($joinStatus == null) {
      self::exception('该用户没有加入申请。');
    }

    if ($joinStatus['status'] == 1) {
      return true;
    }

    $result = db('user_company')
      ->where('user_id', $user_id)
      ->where('company_id', $id)
      ->update(['status' => 1]);
    if ($result) {
      $log = [
        "table" => 'company',
        "owner_id" => $id,
        "title" => '加入企业审核通过',
        "summary" => $user->title . ' ' . $user->mobile
      ];
      Log::add($manager, $log);

      $message = '管理员已批准您加入企业“' . $company->title . '”。';
      $url = 'https://' . config('app_host') . '/app/company/view/' . $company->id;
      User::pushMessage($user_id, $message, $url);
    }
    return $result;
  }

  /**
   * 驳回加入企业/移除企业成员
   */
  public static function rejectAddin($manager, $id, $user_id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if (!self::allow($manager, $company, 'pass')) {
      self::exception('您没有权限。');
    }

    $user = User::get($user_id);
    if ($user == null) {
      self::exception('用户不存在。');
    }

    $joinStatus = self::getJoinStatus($user, $id);
    if ($joinStatus == null) {
      return true;
    }

    $operate = '驳回加入企业';
    $message = '管理员已拒绝您加入企业“' . $company->title . '”的申请。';
    if ($joinStatus['status'] == 1) {
      $operate = '移除企业成员';
      $message = '管理员已将您从企业“' . $company->title . '”中移除。';
    }

    $result = db('user_company')
      ->where('user_id', $user_id)
      ->where('company_id', $id)->delete();

    if ($result) {
      $log = [
        "table" => 'company',
        "owner_id" => $id,
        "title" => $operate,
        "summary" => $user->title
      ];
      Log::add($manager, $log);

      User::pushMessage($user_id, $message);
    }
    return $result;
  }

  /**
   * 获取用户上级
   */
  public static function getSuperior($company_id, $user_id) {
    $joinStatus = db('user_company')
      ->where('company_id', $company_id)
      ->where('user_id', $user_id)
      ->find();

    if ($joinStatus) {
      return $joinStatus['superior_id'];
    } else {
      return 0;
    }
  }

  /**
   * 设置上级
   */
  public static function setSuperior($user, $user_id) {
    $company = self::get($user->company_id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if ($user == null) {
      self::exception('用户无效。');
    }

    $superior = User::get($user_id);
    if ($superior == null) {
      self::exception('用户不存在。');
    }

    $joinStatus = self::getJoinStatus($user, $user->company_id);
    if ($joinStatus != null) {
      if ($joinStatus['superior_id'] == $user_id) {
        return true;
      } else {
        $result = db('user_company')
          ->where('user_id', $user->id)
          ->where('company_id', $user->company_id)
          ->update(['superior_id' => $user_id]);
        
        if ($result) {
          $log = [
            "table" => 'user',
            "owner_id" => $user->id,
            "title" => '设置上级',
            "summary" => $superior->title
          ];
          Log::add($user, $log);
        }
        return $result;
      }
    } else {
      self::exception('您还没有加入企业，不能设置上级。');
    }
  }

  /**
   * 上传Logo
   */
  private static function uploadLogo($logo) {
    $uploadPath = '../public/upload/company/images';
    $info = $logo->validate(['size'=>2097152,'ext'=>'jpg,jpeg,png,gif'])
      ->rule('uniqid')->move($uploadPath . '/original');

    if ($info) {
      File::thumbImage($info, [60,200], $uploadPath);
      return $info->getFilename();
    } else {
      self::exception($logo->getError());
    }
  }

  /**
   * 上传印章
   */
  private static function uploadStamp($stamp) {
    $uploadPath = '../public/upload/company/images';
    $info = $stamp->validate(['size'=>2097152,'ext'=>'png,gif'])
      ->rule('uniqid')->move($uploadPath . '/original');

    if ($info) {
      File::thumbImage($info, [60,200], $uploadPath);
      return $info->getFilename();
    } else {
      self::exception($stamp->getError());
    }
  }
}