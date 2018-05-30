<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;

class Log extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';
  
  /**
   * 记录系统日志
   */
  public static function add($data) {
    $log = new Log();
    $data['system'] = 1;
    return $log->save($data);
  }

  /**
   * 添加修改日志
   */
  public static function addUp($id, $data, $user_id) {
    if ($id > 0) {
      $log = self::get($id);
      if ($log == null) {
        self::exception('日志不存在。');
      } else if ($log->system == 1) {
        self::exception('系统日志不可修改。');
      } else if ($log->user_id != $user_id) {
        self::exception('您没有权限修改此日志。');
      }
    } else {
      $log = new Log();
      $data['user_id'] = $user_id;
    }
    return $log->save($data);
  }

  /**
   * 查询日志
   */
  public static function getList($table, $owner_id, $user_id = 0) {
    $list = self::alias('a')
      ->leftJoin('user b ','a.user_id = b.id')
      ->where('a.table', $table)
      ->where('a.owner_id', $owner_id)
      ->field('a.id,a.title,a.summary,a.create_time,a.system,a.user_id,b.title as user,b.mobile,b.avatar')
      ->order('a.create_time', 'desc')->order('a.id', 'desc')
      ->select();

    foreach($list as $key => $log) {
      $log->summary = str_replace('\n', '<br/>', $log->summary);
    }

    return $list;
  }

  /**
   * 删除日志
   */
  public static function remove($id, $user_id) {
    $log = self::get($id);
    if ($log == null) {
      return true;
    } else if ($log->system == 1) {
      self::exception('系统日志不可删除。');
    } else if ($log->user_id != $user_id) {
      self::exception('您没有权限删除此日志。');
    } else {
      $result = $log->delete();
      return $result;
    }
  }
}