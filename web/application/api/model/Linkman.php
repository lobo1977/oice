<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;

class Linkman extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  /**
   * 通过所有者ID获取联系人列表
   */
  public static function getByOwnerId($id, $type, $user_id = 0) {
    $list = self::where('type', $type)
      ->where('owner_id', $id)
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
  public static function detail($id, $user_id = 0, $company_id = 0) {
    $linkman = self::get($id);

    if ($linkman == null) {
      self::exception('联系人不存在。');
    } else if ($linkman->getAttr('type') == 'customer') {
      $customer = db('customer')->where('id', $linkman->owner_id)->find();
      if ($customer != null) {
        if ($customer['user_id'] != $user_id) {
          if (!($customer['share'] == 1 && $customer['company_id'] == $company_id)) {
            self::exception('您没有权限查看此联系人。');
          }
        }
      }
    } else if ($linkman->getAttr('type') == 'building') {
      $building = db('building')->where('id', $linkman->owner_id)->find();
      if ($building != null) {
        if ($building['share'] == 0 && $building['user_id'] > 0 &&
          $building['user_id'] != $user_id &&
          $building['company_id'] != $company_id) {
          self::exception('您没有权限查看此联系人。');
        }
      }
    } else if ($linkman->getAttr('type') == 'unit') {
      $unit = db('unit')->where('id', $linkman->owner_id)->find();
      if ($unit != null) {
        if ($unit['share'] == 0 && $unit['user_id'] > 0 &&
          $unit['user_id'] != $user_id &&
          $unit['company_id'] != $company_id) {
          self::exception('您没有权限查看此联系人。');
        }
      }
    }

    return $linkman;
  }

  /**
   * 添加/修改联系人信息
   */
  public static function addUp($id, $data, $user_id) {
    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('联系人不存在。');
      } else {
        if ($oldData->user_id > 0 && $oldData->user_id != $user_id) {
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

        if ($data['tel'] != $oldData->tel) {
          if ($oldData->tel) {
            $summary = $summary . '办公电话：' . $oldData->tel . ' -> ' . $data['tel'] . '\n';
          } else {
            $summary = $summary . '办公电话：' . $data['tel'] . '\n';
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
          "summary" => $summary,
          "user_id" => $user_id
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
          Log::add($log);
        }
        return $id;
      }
    } else {
      $data['user_id'] = $user_id;

      $newData = new Linkman($data);
      $result = $newData->save();

      if ($result) {
        Log::add([
          "table" => $data['type'],
          "owner_id" => $data['owner_id'],
          "title" => '添加联系人',
          "summary" => $newData->title,
          "user_id" => $user_id
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
  public static function remove($id, $user_id) {
    $linkman = self::get($id);
    if ($linkman == null) {
      return true;
    } else if ($linkman->user_id > 0 && $linkman->user_id != $user_id) {
      self::exception('您没有权限删除此联系人。');
    }
    $log = [
        "table" => $linkman->getAttr('type'),
        "owner_id" => $linkman->owner_id,
        "title" => '删除联系人',
        "summary" => $linkman->title,
        "user_id" => $user_id
    ];
    $result = $linkman->delete();
    if ($result) {
      Log::add($log);
    }
    return $result;
  }
}