<?php
namespace app\api\model;

use app\api\model\Base;
use app\api\model\User;

class Robot extends Base
{
  protected $pk = 'id';

  /**
	 * 机器人签到
	 * @param string $name
	 * @param string $openid
	 * @return boolean
	 */
	public static function signIn($name, $openid, $avatar) {
    $find = self::where('name', $name)
      ->where('openid', 'null')
      ->where('status', 1)
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

    return self::where('status', 1)
      ->where('openid', 'in', $openid)
      ->order('login', 'desc')->select();
  }
  
  /**
	 * 获取机器人的群列表
	 */
	public static function groups($user) {
    if (empty($user) || empty($user->openid)) {
      return null;
    } else {
      $openid = [$user->openid];
    }

    $list = self::alias('r')
      ->join('robot_group g', 'r.uid = g.uid')
      ->field('r.id as robot_id,r.name as robot_name,r.avatar as robot_avatar,
        g.gid as group_id,g.name as group_name,g.avatar as group_avatar')
      ->where('r.status', 1)
      ->where('r.openid', 'in', $openid)
      ->order("r.id,g.id")
      ->select();

		return $list;
	}
}