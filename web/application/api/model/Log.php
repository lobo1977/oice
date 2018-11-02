<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Customer;
use app\api\model\Company;

class Log extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $level = ['一般','重要','非常重要','成功','预警','失败'];

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
    } else if ($operate == 'view') {
      if ($log->table == 'customer') {
        if ($customer == null) {
          $customer = Customer::get($log->owner_id);
        }
        return Customer::allow($user, $customer, 'view');
      } else {
        $superior_id = Company::getSuperior($log->company_id, $log->user_id);
        return $log->user_id == $user->id || 
          ($log->company_id == $user->company_id && $user->id == $superior_id);
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
    $company_id = 0;
    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    $log = new Log();
    $data['type'] = 0;
    $data['user_id'] = $user_id;
    $data['company_id'] = $company_id;
    $data['start_time'] = now();
    return $log->save($data);
  }

  /**
   * 编辑跟进纪要
   */
  public static function getById($user, $id, $operate = 'view') {
    $log = self::get($id);
    if ($log == null) {
      self::exception('记录不存在。');
    } else if (!self::allow($user, $log, $operate)) {
      if ($operate == 'view') {
        self::exception('您没有权限查看此记录。');
      } else {
        self::exception('您没有权限修改此记录。');
      }
    }
    return $log;
  }

  /**
   * 添加修改日志
   */
  public static function addUp($user, $id, $data) {
    $log = null;

    if ($id > 0) {
      $log = self::getById($user, $id, 'edit');
      if(isset($data['level'])) {
        $log->level = $data['level'];
      }
      $log->title = $data['title'];
      $log->summary = $data['summary'];
      if(isset($data['start_time'])) {
        $log->start_time = $data['start_time'];
      } else {
        $log->start_time = now();
      }
      if(isset($data['end_time'])) {
        $log->end_time = $data['end_time'];
      }
    } else {
      $data['type'] = 1;
      if ($user) {
        $data['user_id'] = $user->id;
        $data['company_id'] = $user->company_id;
      }
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
      ->field('a.id,a.type,a.level,a.title,a.summary,a.start_time,a.end_time,a.company_id,a.user_id,b.title as user,b.mobile,b.avatar')
      ->order('a.start_time', 'desc')->order('a.id', 'desc')
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
   * 查询工作日报
   */
  public static function getDaily($user, $filter) {
    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    if (!isset($filter['page'])) {
      $filter['page'] = 1;
    }

    if (!isset($filter['page_size'])) {
      $filter['page_size'] = 10;
    }
    
    $list = self::alias('a')
      ->leftJoin('user b ','a.user_id = b.id')
      ->leftJoin('user_company c', 'a.user_id = c.user_id and a.company_id = c.company_id and c.status = 1')
      ->where('a.user_id = ' . $user_id .
        ' OR (a.company_id > 0 AND a.company_id = ' . $company_id . 
        ' AND c.superior_id = ' . $user_id . ')')
      ->where(function ($query) {
        $query->whereOr('a.type', '>', 0)
          ->whereOr('a.table', '=', 'customer');
      });

    if ($filter['user_id']) {
      $list->where('a.user_id', $filter['user_id']);
    }

    if ($filter['$date']) {
      $list->where('a.start_time', 'between time', [$filter['$date'], strtotime($filter['$date'] + ' +1 day')]);
    } else if ($filter['$startDate'] && $filter['$endDate']) {
      $list->where('a.start_time', 'between time', [$filter['$startDate'], strtotime($filter['$endDate'] + ' +1 day')]);
    } else if (!$filter['user_id']) {
      $list->where('a.start_time', '> time', strtotime('-1 day'));
    } else {
      $list->where('a.start_time', '> time', strtotime('-1 week'));
    }

    $result = $list->field('a.id,a.type,a.level,a.title,a.summary,a.start_time,a.end_time,a.company_id,a.user_id,b.title as user,b.mobile,b.avatar')
      ->page($filter['page'], $filter['page_size'])  
      ->order('a.start_time', 'desc')->order('a.id', 'desc')
      ->select();

    foreach($result as $key => $log) {
      $log->summary = str_replace('\n', '<br/>', $log->summary);
      // $log->allowEdit = self::allow($user, $log, 'edit');
      // $log->allowDelete = self::allow($user, $log, 'delete');
    }

    return $result;
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