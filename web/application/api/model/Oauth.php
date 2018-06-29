<?php
namespace app\api\model;

use app\api\model\Base;

class Oauth extends Base
{
  protected $pk = 'id';

  /**
   * 添加用户
   */
  public static function add($data) {
    $user = self::where('platform', $data['platform'])
      ->where('openid', $data['openid'])->find();

    if ($user == null) {
      $user = new Oauth();
      return $user->save($data);
    } else {
      return true;
    }
  }
}