<?php
namespace app\api\model;

use app\api\model\Base;
use app\api\model\User;

class Robot extends Base
{
  protected $pk = 'id';
  protected static $action = [ 'OFFLINE', 'WEAKUP', 'SLEEP'];

  /**
	 * 机器人签到
	 * @param string $name
	 * @param string $openid
	 * @return boolean
	 */
	public static function signIn($robot_id, $openid, $avatar) {
    $find = self::where('uid', $robot_id)
      ->where('status', '>', 0)
      ->find();
    if ($find) {
      $find->openid = $openid;
      $find->avatar = $avatar;
      return $find->save();
    } else {
      return false;
    }
	}
	
	/**
	 * 获取在线机器人
	 */
	public static function online($user) {
    if (empty($user) || empty($user->openid)) {
      return null;
    } else {
      $openid = [$user->openid];
    }

    return self::alias('r')
      ->leftJoin('robot_task t', 'r.uid = t.uid and t.status = 0')
      ->field('r.id,r.name,r.avatar,r.status,count(t.id) as task')
      ->where('r.status', '>', 0)
      ->where('r.openid', 'in', $openid)
      ->order('r.login', 'desc')
      ->group('r.id,r.name,r.avatar,r.status')
      ->select();
  }
  
  /**
	 * 获取机器人的联系人/群列表
	 */
	public static function contact($user, $id = 0) {
    if (empty($user) || empty($user->openid)) {
      return null;
    } else {
      $openid = [$user->openid];
    }

    $list = self::alias('r')
      ->join('robot_contact c', 'r.uid = c.uid')
      ->field('r.id as robot_id,r.name as robot_name,r.avatar as robot_avatar,
        c.id,c.type,c.name as contact_name,c.avatar as contact_avatar')
      ->where('r.status', '>', 0)
      ->where('r.openid', 'in', $openid);

    if ($id) {
      $list->where('r.id', $id);
    }
    $list = $list->order("r.id,c.type,c.id")->select();

    if ($list)  {
      foreach ($list as &$item) {
        $item['checked'] = false;
      }
    }

		return $list;
  }
  
  /**
   * 推送分享
   */
  public static function push($user, $type, $contact, $content, $url = '', $cycle = 0, $start = null, $end = null) {
    if (empty($user) || empty($user->openid)) {
      return false;
    } else {
      $openid = [$user->openid];
    }

    if ($url) {
      $content .= '
【项目详情】' . $url;
    }

    $content .= '
推广支持 -【' . config('app_name') . '】';

    $list = self::alias('r')
      ->join('robot_contact c', 'r.uid = c.uid')
      ->field('c.uid,c.cid')
      ->where('r.status', '>', 0)
      ->where('r.openid', 'in', $openid);

    if ($type == '0' || $type == '1') {
      $list->where('c.type', $type);
    }
    
    if ($contact) {
      $list->where('c.id', 'in', $contact);
    }

    $list = $list->select();
    $now = date("Y-m-d H:i:s",time());

    if ($list)  {
      foreach ($list as $item) {
        $data['uid'] = $item['uid'];
        $data['task'] = 'TURN|' . $item['cid'] . '|' . $content;
        $data['cycle_hour'] = $cycle;
        $data['start_hour'] = $start;
        $data['end_hour'] = $end;
        $data['task_time'] = $now;
        $data['status'] = 0;
        db("robot_task")->insert($data);
      }
    }

    return true;
  }

  /**
   * 发送指令
   */
  public static function sendAction($user, $id, $actionKey) {
    if (empty($user) || empty($user->openid)) {
      return false;
    } else {
      $openid = [$user->openid];
    }

    $find = self::where('status', '>', 0)
      ->where('id', $id)
      ->where('openid', 'in', $openid)
      ->find();

    if ($find) {
      $data['uid'] = $find['uid'];
      $data['task'] = self::$action[$actionKey];
      $data['level'] = 1;
      $data['status'] = 0;
      db("robot_task")->insert($data);
      return true;
    }

    return false;
  }

  /**
   * 清除所有任务
   */
  public static function clearTask($user, $id) {
    if (empty($user) || empty($user->openid)) {
      return false;
    } else {
      $openid = [$user->openid];
    }

    $find = self::where('status', '>', 0)
      ->where('id', $id)
      ->where('openid', 'in', $openid)
      ->find();

    if ($find) {
      db("robot_task")->where('uid', $find['uid'])->delete();
      return true;
    }

    return false;
  }
}