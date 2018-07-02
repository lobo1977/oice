<?php
namespace app\api\model;

use app\api\model\Base;
use app\api\model\User;

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

  /**
   * 用户登录
   */
  public static function login($platform, $token) {
    $oauth = self::where('platform', $platform)
      ->where('openid', $token['openid'])->find();

    if ($oauth == null) {
      $oauth = new Oauth();
      $oauth->platform = $platform;
      $oauth->openid = $token['openid'];
    }

    $oauth->token = $token['access_token'];
    $oauth->expired_time = $token['expires_in'];
    $oauth->refresh_token = $token['refresh_token'];

    if ($oauth->save()) {
      session('oauth', $platform);
      session('oauth_openid', $token['openid']);
      if ($oauth->user_id) {
        $user = User::loginSuccess($oauth->user_id, $token['openid']);
        if ($user != null) {
          if ($oauth->nickname && empty($user->title)) {
            $user->title = $oauth->nickname;
          }
          if ($oauth->avatar && empty($user->avatar)) {
            $user->avatar = $oauth->avatar;
          }
          return $user;
        }
      }
      return true;
    } else {
      return false;
    }
  }

  /**
   * 绑定手机号码
   */
  public static function mobile($mobile, $verify_code) {
    $platform = session('oauth');
    $openid = session('oauth_openid');
    $user = User::loginByVerifyCode($mobile, $verify_code);
    if ($platform && $openid && $user) {
      $oauth = self::where('platform', $platform)
        ->where('openid', $openid)->find();
      if ($oauth) {
        $oauth->user_id = $user->id;
        if ($oauth->save()) {
          session('oauth', null);
          session('oauth_openid', null);
        }
        if ($oauth->nickname && empty($user->title)) {
          $user->title = $oauth->nickname;
        }
        if ($oauth->avatar && empty($user->avatar)) {
          $user->avatar = $oauth->avatar;
        }
      }
      return $user;
    } else {
      return false;
    }
  }
}