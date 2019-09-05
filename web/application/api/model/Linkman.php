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
      $customer = Customer::getById($user, $id);
      return Customer::allow($user, $customer, $operate);
    } else if ($type == 'building') {
      $building = Building::get($id);
      return Building::allow($user, $building, $operate);
    } else if ($type == 'unit') {
      $unit = Unit::get($id);
      return Unit::allow($user, $unit, $operate);
    } else {
      return true;
    }
  }

  /**
   * 通过所有者ID获取联系人列表
   */
  public static function getByOwnerId($user, $type, $id, $allow = false) {
    if (!$allow && !self::allow($user, $type, $id, 'view')) {
      self::exception('您没有权限查看联系人。');
    }
    
    $list = self::where('type', $type)
      ->where('owner_id', $id)
      ->field('id,title,department,job,mobile,email,weixin,qq,status')
      ->order('id', 'asc')
      ->select();

    foreach($list as $key=>$linkman) {
      $linkman->desc = $linkman->department . $linkman->job . ' ' . $linkman->mobile;
      if ($linkman->status == 1) {
        $linkman->title = $linkman->title . '(已离职)';
      }
    }
    return $list;
  }

  /**
   * 根据ID 获取联系人信息
   */
  public static function detail($user, $id, $operate = 'view') {
    $linkman = self::where('id', $id)
      ->field('id,type,owner_id,title,department,job,mobile,' .
        'email,weixin,qq,rem,status,user_id')
      ->find();
    
    if ($linkman == null) {
      self::exception('联系人不存在。');
    } else if (!self::allow($user, $linkman->getAttr('type'), $linkman->owner_id, $operate)) {
      self::exception('您没有权限' . ($operate == 'view' ? '查看' : '修改') . '此联系人。');
    }
    if ($operate == 'view') {
      $linkman->allowEdit = self::allow($user, $linkman->getAttr('type'), $linkman->owner_id, 'edit');
      $linkman->allowDelete = $linkman->allowEdit;
    }
    return $linkman;
  }

  /**
   * 添加/修改联系人信息
   */
  public static function addUp($user, $id, $data) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('联系人不存在。');
      } else if (!self::allow($user, $oldData->getAttr('type'), $oldData->owner_id, 'edit')) {
        self::exception('您没有权限修改此联系人。');
      }

      $summary = $oldData->title . ' ';

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

      // if ($data['tel'] != $oldData->tel) {
      //   if ($oldData->tel) {
      //     $summary = $summary . '办公电话：' . $oldData->tel . ' -> ' . $data['tel'] . '\n';
      //   } else {
      //     $summary = $summary . '办公电话：' . $data['tel'] . '\n';
      //   }
      // }

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
    } else if (!self::allow($user, $linkman->getAttr('type'), $linkman->owner_id, 'edit')) {
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