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
    } else if (isset($log->system) && $log->system) {
      return false;
    }
    if ($log->table == 'customer' && 
      ($operate == 'new' || $operate == 'edit' || $operate == 'delete')) {
      if ($customer == null) {
        $customer = Customer::get($log->owner_id);
      }
      return Customer::allow($user, $customer, 'follow') &&
        $log->user_id == $user->id;
    } else {
      return true;
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
    $data['system'] = 1;
    $data['user_id'] = $user_id;
    return $log->save($data);
  }

  /**
   * 编辑日志
   */
  public static function edit($user, $id) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }
    
    $log = self::get($id);
    if ($log == null) {
      self::exception('日志不存在。');
    } else if ($log->system == 1) {
      self::exception('系统日志不可修改。');
    } else if (!self::allow($user, $log, 'edit')) {
      self::exception('您没有权限修改此跟进纪要。');
    }
    return $log;
  }

  /**
   * 添加修改日志
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
      ->field('a.id,a.title,a.summary,a.create_time,a.system,a.user_id,b.title as user,b.mobile,b.avatar')
      ->order('a.create_time', 'desc')->order('a.id', 'desc')
      ->select();

    $customer = null;
    if ($table == 'customer') {
      $customer = Customer::get($owner_id);
    }

    foreach($list as $key => $log) {
      $log->summary = str_replace('\n', '<br/>', $log->summary);
      $log->allowEdit = self::allow($user, $log, 'edit', $customer);
      $log->allowDelete = self::allow($user, $log, 'edit', $customer);
    }

    return $list;
  }

  /**
   * 删除日志
   */
  public static function remove($user, $id) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }
    
    $log = self::get($id);
    if ($log == null) {
      return true;
    } else if ($log->system == 1) {
      self::exception('系统日志不可删除。');
    } else if (!self::allow($user, $log, 'delete')) {
      self::exception('您没有权限删除此日志。');
    } else {
      $result = $log->delete();
      return $result;
    }
  }
}