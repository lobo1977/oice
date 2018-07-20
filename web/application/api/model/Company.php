<?php
namespace app\api\model;

use think\facade\Validate;
use think\model\concern\SoftDelete;
use app\common\Sms;
use app\api\model\Base;
use app\api\model\User;
use app\api\model\Log;

class Company extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $joinWay = ['开放加入','需管理员审核加入', '需通过邀请加入'];
  public static $status = ['公开','隐藏'];

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
   * 获取加入成员统计
   */
  protected static function setAddinCount($company) {
    $company->wait = 0;
    $company->addin = 0;

    $count = db('user_company')
      ->where('company_id', $company->id)
      ->field('status,count(user_id) as count')
      ->group('status')
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
   * 检索公开企业
   */
  public static function search($keyword, $page = 1) {
    $list = self::where('status', 1);

    if ($keyword) {
      $list = $list->where('title|full_name', 'like', $keyword . '%');
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
  public static function my($user_id = 0, $status = 1) {
    $list = self::alias('a')
      ->join('user_company b', 'a.id = b.company_id and b.status = ' . $status)
      ->where('b.user_id', $user_id)
      ->field('a.id,a.title,a.logo,a.area,a.address,a.create_time,b.default')
      ->order('a.id', 'asc')
      ->select();

    return self::formatList($list, false);
  }

  /**
   * 邀请待加入的企业
   */
  public static function inviteMe($mobile) {
    $list = self::alias('a')
      ->join('invite b', 'a.id = b.company_id and b.status = 0')
      ->where('b.mobile', $mobile)
      ->field('a.id,a.title,a.logo,a.area,a.address,a.create_time')
      ->order('a.id', 'asc')
      ->select();

    return self::formatList($list, false);
  }

  /**
   * 我创建的企业
   */
  public static function myCreate($user_id = 0) {
    $list = self::where('user_id', $user_id)
      ->field('id,title,logo,area,address,status,create_time')
      ->order('id', 'asc')
      ->select();
    
    return self::formatList($list);
  }

  /**
   * 获取企业成员
   */
  public static function Member($id, $status = 0, $user_id = 0, $page = 0) {
    $list = User::alias('a')
      ->join('user_company b', 'a.id = b.user_id and b.status = ' . $status)
      ->where('b.company_id', $id)
      ->field('a.id,a.title,a.avatar,a.mobile')
      ->order('b.create_time', 'asc');

    if ($page > 0) {
      $list = $list->page($page, 10);
    }

    $list = $list->select();

    foreach($list as $user) {
      User::formatUserInfo($user);
    }
    return $list;
  }

  /**
   * 获取企业详细信息
   */
  public static function detail($id, $user_id = 0) {
    $data = self::get($id);
    if ($data == null) {
      self::exception('企业不存在。');
    }
    $data->isAddin = false;
    if ($user_id) {
      $joinStatus = self::getJoinStatus($id, $user_id);
      if ($joinStatus) {
        $data->isAddin = $joinStatus['status'];
      }
      if ($user_id == $data->user_id) {
        $data->waitUser = self::Member($id);
      }
    }
    if ($data->logo) {
      $data->logo = '/upload/company/images/200/' . $data->logo;
    }
    if ($data->stamp) {
      $data->stamp = '/upload/company/images/60/' . $data->stamp;
    }
    self::setAddinCount($data);
    return $data;
  }

  /**
   * 添加/修改企业信息
   */
  public static function addUp($id, $data, $logo, $stamp, $user_id) {
    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('企业不存在。');
      }else if ($oldData->user_id != $user_id) {
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
      }

      if ($stamp) {
        $stampPath = self::uploadStamp($stamp);
        if ($stampPath) {
          $data['stamp'] = $stampPath;
          $summary = $summary . '公章：' . $oldData->stamp . ' -> ' . $data['stamp'] . '\n';
        }
      }

      $result =  $oldData->save($data);

      if ($result && $summary) {
        Log::add([
          "table" => "company",
          "owner_id" => $id,
          "title" => '修改企业信息',
          "summary" => $summary,
          "user_id" => $user_id
        ]);
      }
      return $id;
    } else {
      $data['city'] = self::$city;
      $data['user_id'] = $user_id;

      if ($logo) {
        $logoPath = self::uploadLogo($logo);
        if ($logoPath) {
          $data['logo'] = $logoPath;
        }
      }

      if ($stamp) {
        $stampPath = self::uploadStamp($stamp);
        if ($stampPath) {
          $data['stamp'] = $stampPath;
        }
      }

      $newData = new Company($data);
      $result = $newData->save();

      if ($result) {
        Log::add([
          "table" => "company",
          "owner_id" => $newData->id,
          "title" => '添加企业',
          "summary" => $newData->title,
          "user_id" => $user_id
        ]);

        // 自动加入企业
        self::addin($newData->id, $user_id);
        
        return $newData->id;
      } else {
        return false;
      }
    }
  }

  /**
   * 删除企业
   */
  public static function remove($id, $user_id) {
    $company = self::get($id);
    $joinStatus = self::getJoinStatus($id, $user_id);
    if ($company != null) {
      if ($company->user_id != $user_id) {
        self::exception('您没有权限删除此企业。');
      } else if ($joinStatus) {
        self::exception('已加入的企业不能删除。');
      }
      $log = [
        "table" => 'company',
        "owner_id" => $company->id,
        "title" => '删除企业',
        "summary" => $company->title,
        "user_id" => $user_id
      ];
      $result = $company->delete();
      if ($result) {
        Log::add($log);
      }
      return $result;
    }
    return false;
  }

  /**
   * 发出邀请
   */
  public static function invite($id, $mobile, $user_id) {
    if (!Validate::checkRule($mobile, 'mobile')) {
      self::exception('手机号码无效。');
    }

    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if ($company->user_id != $user_id) {
      self::exception('您没有权限发出邀请。');
    }

    $user = User::get($user_id);
    if ($user == null) {
      self::exception('邀请人不存在。');
    }

    $inviteUser = User::where('mobile', $mobile)->find();
    if ($inviteUser != null) {
      $joinStatus = self::getJoinStatus($id, $inviteUser->id);
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
      // 发送短信
      $sender = new Sms();
      $smsResult = $sender->sendInvite($mobile, $user->title, $company->title);

      $log = [
        "table" => 'company',
        "owner_id" => $id,
        "title" => '邀请用户加入企业',
        "summary" => $company->title . ' 用户：' . $mobile,
        "user_id" => $user_id
      ];
      Log::add($log);
      return $result;
    } else {
      return false;
    }
  }

  /**
   * 切换企业
   */
  public static function setDefault($id, $user_id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    }

    $joinStatus = self::getJoinStatus($id, $user_id);
    if ($joinStatus) {
      if ($joinStatus['default'] == 1) return 1;

      db('user_company')
        ->where('user_id', $user_id)
        ->where('default', 1)
        ->update(['default' => 0]);

      $result = db('user_company')
        ->where('user_id', $user_id)
        ->where('company_id', $id)
        ->update(['default' => 1]);

      if ($result) {
        $log = [
          "table" => 'company',
          "owner_id" => $id,
          "title" => '切换企业',
          "summary" => $company->title,
          "user_id" => $user_id
        ];
        Log::add($log);
      }
      return $result;
    } else {
      return false;
    }
  }

  /**
   * 加入企业
   */
  public static function addin($id, $user_id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    }

    $user = User::get($user_id);
    if ($user == null) {
      self::exception('用户不存在。');
    }

    $joinStatus = self::getJoinStatus($id, $user_id);
    if ($joinStatus != null) {
      return $joinStatus['status'];
    }

    $default = 0;
    $status = 1;
    $invite = db('invite')->where('company_id', $id)
      ->where('mobile', $user->mobile)
      ->find();
    
    if ($company->user_id != $user_id) {
      if ($company->join_way > 1 && $invite == null) {
        self::exception('企业需要通过邀请加入。');
      } else if ($company->join_way == 1) {
        $status = 0;
      }
    }

    $hasDefault = db('user_company')
      ->where('user_id', $user_id)
      ->where('default', 1)->find();

    if ($hasDefault == null) {
      $default = 1;
    }

    $joinData = [ 
      'user_id' => $user_id, 
      'company_id' => $id, 
      'default' => $default,
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
        "summary" => $company->title,
        "user_id" => $user_id
      ];
      Log::add($log);
      return $status;
    } else {
      return false;
    }
  }

  /**
   * 退出企业
   */
  public static function quit($id, $user_id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if ($company->user_id == $user_id) {
      self::exception('不能退出由您创建的企业。');
    }

    $result = db('user_company')
      ->where('user_id', $user_id)
      ->where('company_id', $id)->delete();

    if ($result) {
      $log = [
        "table" => 'company',
        "owner_id" => $id,
        "title" => '退出企业',
        "summary" => $company->title,
        "user_id" => $user_id
      ];
      Log::add($log);

      $default = db('user_company')
        ->where('user_id', $user_id)
        ->where('default', 1)->find();
      
      if ($default == null) {
        $default = db('user_company')
        ->where('user_id', $user_id)
        ->order('create_time', 'desc')
        ->find();

        if ($default != null) {
          db('user_company')
            ->where('user_id', $default['user_id'])
            ->where('company_id', $default['company_id'])
            ->update(['default' => 1]);
        }
      }
    }
    return $result;
  }

  /**
   * 获取加入企业状态
   */
  public static function getJoinStatus($id, $user_id) {
    return db('user_company')
      ->where('user_id', $user_id)
      ->where('company_id', $id)->find();
  }

  /**
   * 加入企业审核通过
   */
  public static function passAddin($id, $user_id, $manager_id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if ($company->user_id != $manager_id) {
      self::exception('您没有权限审核。');
    }

    $user = User::get($user_id);
    if ($user == null) {
      self::exception('用户不存在。');
    }

    $joinStatus = self::getJoinStatus($id, $user_id);
    if ($joinStatus == null) {
      self::exception('加入申请不存在。');
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
        "summary" => $user->title . ' ' . $user->mobile,
        "user_id" => $manager_id
      ];
      Log::add($log);
    }
    return $result;
  }

  /**
   * 驳回加入企业/移除企业成员
   */
  public static function rejectAddin($id, $user_id, $manager_id) {
    $company = self::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    } else if ($company->user_id != $manager_id) {
      self::exception('您没有权限。');
    }

    $user = User::get($user_id);
    if ($user == null) {
      self::exception('用户不存在。');
    }

    $joinStatus = self::getJoinStatus($id, $user_id);
    if ($joinStatus == null) {
      self::exception('加入申请不存在。');
    }

    $operate = '驳回加入企业';
    if ($joinStatus['status'] == 1) {
      $operate = '移除企业成员';
    }

    $result = db('user_company')
      ->where('user_id', $user_id)
      ->where('company_id', $id)->delete();

    if ($result) {
      $log = [
        "table" => 'company',
        "owner_id" => $id,
        "title" => $operate,
        "summary" => $user->title . ' ' . $user->mobile,
        "user_id" => $manager_id
      ];
      Log::add($log);
    }
    return $result;
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
      return false;
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
      return false;
    }
  }
}