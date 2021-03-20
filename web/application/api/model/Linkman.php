<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Customer;
use app\api\model\Building;
use app\api\model\Unit;

class Linkman extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  /**
   * 权限检查
   */
  public static function allow($user, $type, $id, $operate) {
    if ($type == 'customer') {
      $operate = 'new';
      $customer = Customer::getById($user, $id);
      return Customer::allow($user, $customer, $operate);
    } else if ($type == 'building') {
      $building = Building::getById($user, $id);
      return Building::allow($user, $building, $operate);
    } else if ($type == 'unit') {
      $unit = Unit::getById($user, $id);
      return Unit::allow($user, $unit, $operate);
    } else {
      return true;
    }
  }

  /**
   * 上传头像
   */
  private static function uploadAvatar($avatar) {
    $uploadPath = '../public/upload/user/images';
    $info = $avatar->validate(['size'=>2097152,'ext'=>'jpg,jpeg,png,gif'])
      ->rule('uniqid')->move($uploadPath . '/original');

    if ($info) {
      File::thumbImage($info, [60,200], $uploadPath);
      return $info->getFilename();
    } else {
      self::exception($avatar->getError());
    }
  }

  /**
   * 通过所有者ID获取联系人列表
   */
  public static function getByOwnerId($user, $type, $id, $allow = false, $status = -1) {
    if (!$allow && !self::allow($user, $type, $id, 'view')) {
      self::exception('您没有权限查看联系人。');
    }
    
    $list = self::where('type', $type)
      ->where('owner_id', $id);

    if ($status >= 0) {
      $list->where('status', $status);
    }
    $list = $list->field('id,title,avatar,department,job,mobile,tel,email,weixin,qq,status')
      ->order(['status' => 'asc', 'id' => 'asc'])
      ->select();

    foreach($list as $key=>$linkman) {
      $linkman->desc = $linkman->department . $linkman->job . ' ' . $linkman->mobile . ' ' . $linkman->tel;
      if ($linkman->status == 1) {
        $linkman->title = $linkman->title . '(已离职)';
      }
      if (isset($linkman->avatar) && $linkman->avatar) {
        $find = strpos($linkman->avatar, 'http');
        if ($find === false || $find > 0) {
          $linkman->avatar = '/upload/user/images/60/' . $linkman->avatar;
        }
      } else {
        $linkman->avatar = '/static/img/avatar.png';
      }
    }
    return $list;
  }

  /**
   * 根据ID 获取联系人信息
   */
  public static function detail($user, $id, $operate = 'view') {
    $linkman = self::where('id', $id)
      ->field('id,type,owner_id,title,avatar,department,job,mobile,tel,' .
        'email,weixin,qq,rem,status,user_id')
      ->find();
    
    if ($linkman == null) {
      self::exception('联系人不存在。');
    } else if (
      $linkman->user_id != $user->id &&
      !self::allow($user, $linkman->getAttr('type'), $linkman->owner_id, $operate)) {
      self::exception('您没有权限' . ($operate == 'view' ? '查看' : '修改') . '此联系人。');
    }
    
    if (isset($linkman->avatar) && $linkman->avatar) {
      $find = strpos($linkman->avatar, 'http');
      if ($find === false || $find > 0) {
        $linkman->avatar = '/upload/user/images/60/' . $linkman->avatar;
      }
    } else {
      $linkman->avatar = '/static/img/avatar.png';
    }

    if ($operate == 'view') {
      $linkman->allowEdit = $linkman->user_id == $user->id ||
        self::allow($user, $linkman->getAttr('type'), $linkman->owner_id, 'edit');
      $linkman->allowDelete = $linkman->allowEdit;
    }
    return $linkman;
  }

  /**
   * 添加/修改联系人信息
   */
  public static function addUp($user, $id, $data, $avatar = null) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('联系人不存在。');
      } else if ($oldData->user_id != $user_id && 
        !self::allow($user, $oldData->getAttr('type'), $oldData->owner_id, 'edit')) {
        self::exception('您没有权限修改此联系人。');
      }

      $summary = $oldData->title . ' ';

      if ($avatar) {
        $path = self::uploadAvatar($avatar);
        if ($path) {
          $data['avatar'] = $path;
        }
      } else if (isset($data['avatar'])) {
        unset($data['avatar']);
      }

      if ($data['title'] != $oldData->title) {
        if ($oldData->title) {
          $summary = '姓名：' . $oldData->title . ' -> ' . $data['title'] . '\n';
        } else {
          $summary = '姓名：' . $data['title'] . '\n';
        }
      }

      if ($data['department'] != $oldData->department) {
        if ($oldData->department) {
          $summary = $summary . '部门：' . $oldData->department . ' -> ' . $data['department'] . '\n';
        } else {
          $summary = $summary . '部门：' . $data['department'] . '\n';
        }
      }

      if ($data['job'] != $oldData->job) {
        if ($oldData->job) {
          $summary = $summary . '职务：' . $oldData->job . ' -> ' . $data['job'] . '\n';
        } else {
          $summary = $summary . '职务：' . $data['job'] . '\n';
        }
      }

      if ($data['mobile'] != $oldData->mobile) {
        if ($oldData->mobile) {
          $summary = $summary . '手机号码：' . $oldData->mobile . ' -> ' . $data['mobile'] . '\n';
        } else {
          $summary = $summary . '手机号码：' . $data['mobile'] . '\n';
        }
      }

      if ($data['tel'] != $oldData->tel) {
        if ($oldData->tel) {
          $summary = $summary . '直线电话：' . $oldData->tel . ' -> ' . $data['tel'] . '\n';
        } else {
          $summary = $summary . '直线电话：' . $data['tel'] . '\n';
        }
      }

      if ($data['email'] != $oldData->email) {
        if ($oldData->email) {
          $summary = $summary . '电子邮箱：' . $oldData->email . ' -> ' . $data['email'] . '\n';
        } else {
          $summary = $summary . '电子邮箱：' . $data['email'] . '\n';
        }
      }

      if ($data['weixin'] != $oldData->weixin) {
        if ($oldData->weixin) {
          $summary = $summary . '微信：' . $oldData->weixin . ' -> ' . $data['weixin'] . '\n';
        } else {
          $summary = $summary . '微信：' . $data['weixin'] . '\n';
        }
      }

      if ($data['qq'] != $oldData->qq) {
        if ($oldData->weixin) {
          $summary = $summary . 'QQ：' . $oldData->qq . ' -> ' . $data['qq'] . '\n';
        } else {
          $summary = $summary . 'QQ：' . $data['qq'] . '\n';
        }
      }


      if ($data['rem'] != $oldData->rem) {
        if ($oldData->rem) {
          $summary = $summary . '备注：' . $oldData->rem . ' -> ' . $data['rem'] . '\n';
        } else {
          $summary = $summary . '备注：' . $data['rem'] . '\n';
        }
      }

      $log = [
        "table" => $oldData->getAttr('type'),
        "owner_id" => $oldData->owner_id,
        "title" => '修改联系人',
        "summary" => $summary
      ];

      if (isset($data['type'])) {
        unset($data['type']);
      }

      if (isset($data['owner_id'])) {
        unset($data['owner_id']);
      }

      if ($oldData->user_id == 0) {
        $data['user_id'] = $user_id;
      } else if (isset($data['user_id'])) {
        unset($data['user_id']);
      }

      $result =  $oldData->save($data);
      if ($result) {
        Log::add($user, $log);
      }
      return $id;
    } else if (!self::allow($user, $data['type'], $data['owner_id'], 'new')) {
        self::exception('您没有权限添加联系人。');
    } else {
      $data['user_id'] = $user_id;

      if ($avatar) {
        $path = self::uploadAvatar($avatar);
        if ($path) {
          $data['avatar'] = $path;
        }
      } else if (isset($data['avatar'])) {
        unset($data['avatar']);
      }

      $newData = new Linkman($data);
      $result = $newData->save();

      if ($result) {
        Log::add($user, [
          "table" => $data['type'],
          "owner_id" => $data['owner_id'],
          "title" => '添加联系人',
          "summary" => $newData->title
        ]);
        return $newData->id;
      } else {
        return false;
      }
    }
  }

  /**
   * 删除联系人
   */
  public static function remove($user, $id) {
    $linkman = self::get($id);
    if ($linkman == null) {
      return true;
    } else if (
      $linkman->user_id != $user->id &&
        !self::allow($user, $linkman->getAttr('type'), $linkman->owner_id, 'edit')) {
      self::exception('您没有权限修改此联系人。');
    }

    $log = [
      "table" => $linkman->getAttr('type'),
      "owner_id" => $linkman->owner_id,
      "title" => '删除联系人',
      "summary" => $linkman->title
    ];
    $result = $linkman->delete();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }
}