<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Customer;

class Log extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  /**
   * 权限检查
   */
  public static function allow($user, $log, $operate, $customer = null) {
    if ($log == null || $user == null) {
      return false;
    } else if ($operate == 'new') {
      if ($log->table == 'customer') {
        if ($customer == null) {
          $customer = Customer::get($log->owner_id);
        }
        return Customer::allow($user, $customer, 'follow');
      } else {
        return true;
      }
    } else {
      return $log->getAttr('type') > 0 && $log->user_id == $user->id;
    }
  }
  
  /**
   * 记录系统日志
   */
  public static function add($user, $data) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $log = new Log();
    $data['type'] = 0;
    $data['user_id'] = $user_id;
    return $log->save($data);
  }

  /**
   * 编辑跟进纪要
   */
  public static function edit($user, $id) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }
    
    $log = self::get($id);
    if ($log == null) {
      self::exception('记录不存在。');
    } else if (!self::allow($user, $log, 'edit')) {
      self::exception('您没有权限修改此记录。');
    }
    return $log;
  }

  /**
   * 添加修改跟进纪要
   */
  public static function addUp($user, $id, $data) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $log = null;

    if ($id > 0) {
      $log = self::edit($user, $id);
      $log->title = $data['title'];
      $log->summary = $data['summary'];
      $log->create_time = $data['create_time'];
    } else {
      $data['user_id'] = $user_id;
      $data['type'] = 1;
      $log = new Log($data);
      if (!self::allow($user, $log, 'new')) {
        self::exception('您没有权限跟进此客户。');
      }
    }
    return $log->save();
  }

  /**
   * 查询日志
   */
  public static function getList($user, $table, $owner_id) {
    $list = self::alias('a')
      ->leftJoin('user b ','a.user_id = b.id')
      ->where('a.table', $table)
      ->where('a.owner_id', $owner_id)
      ->field('a.id,a.type,a.title,a.summary,a.create_time,a.user_id,b.title as user,b.mobile,b.avatar')
      ->order('a.create_time', 'desc')->order('a.id', 'desc')
      ->select();

    $customer = null;
    if ($table == 'customer') {
      $customer = Customer::get($owner_id);
    }

    foreach($list as $key => $log) {
      $log->summary = str_replace('\n', '<br/>', $log->summary);
      $log->allowEdit = self::allow($user, $log, 'edit', $customer);
      $log->allowDelete = self::allow($user, $log, 'delete', $customer);
    }

    return $list;
  }

  /**
   * 删除
   */
  public static function remove($user, $id) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }
    
    $log = self::get($id);
    if ($log == null) {
      return true;
    } else if (!self::allow($user, $log, 'delete')) {
      self::exception('您没有权限删除此记录。');
    } else {
      $result = $log->delete();
      return $result;
    }
  }
}