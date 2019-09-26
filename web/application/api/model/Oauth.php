<?php
namespace app\api\model;

use app\common\Utils;
use app\common\Wechat;
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
    }
    $data['nickname'] = Utils::emojiToChar($data['nickname']);
    return $user->save($data);
  }

  /**
   * 微信公众号登录
   */
  public static function login($token) {
    $platform = 'wechat';
    $oauth = self::where('platform', $platform)
      ->where('openid', $token['openid'])->find();

    if ($oauth == null) {
      $oauth = new Oauth();
      $oauth->platform = $platform;
      $oauth->openid = $token['openid'];
    }

    $wechat = new Wechat();
    $openUserInfo = $wechat->getUserInfo($token['openid']);

    if ($openUserInfo) {
      $oauth->nickname = Utils::emojiToChar($openUserInfo['nickname']);
      $oauth->unionid = isset($openUserInfo['unionid']) ? $openUserInfo['unionid'] : '';
      $oauth->avatar = $openUserInfo['headimgurl'];
      $oauth->sex = $openUserInfo['sex'];
    }

    $oauth->token = $token['access_token'];
    $oauth->expired_time = $token['expires_in'];
    $oauth->refresh_token = $token['refresh_token'];

    if ($oauth->save()) {
      session('oauth', $platform);
      session('oauth_openid', $token['openid']);
      if ($openUserInfo && isset($openUserInfo['unionid'])) {
        session('unionid', $openUserInfo['unionid']);
      }
      if (isset($oauth['user_id']) && $oauth->user_id > 0) {
        $user = User::getById($oauth->user_id);
        if ($user != null) {
          User::loginSuccess($user, $token['openid']);
          if ($oauth->nickname && empty($user->title)) {
            $user->title = $oauth->nickname;
          }
          if ($oauth->avatar && empty($user->avatar)) {
            $user->avatar = $oauth->avatar;
          }
          if ($openUserInfo) {
            $user->subscribe = 1;
          } else {
            $user->subscribe = 0;
          }
          return $user;
        }
      }
      return true;
    } else {
      return false;
    }
  }

  public static function miniLogin($token) {
    if (!$token || !isset($token['unionid'])) {
      return false;
    }

    $platform = 'wechat';
    $oauth = self::where('platform', $platform)
      ->where('unionid', $token['unionid'])->find();
   
    if ($oauth == null) {
      $oauth = new Oauth();
      $oauth->platform = $platform;
      $oauth->unionid = $token['unionid'];
    }

    $oauth->session_key = $token['session_key'];
    $result = $oauth->save();

    if (!$result) {
      return false;
    }

    if (isset($oauth['user_id']) && $oauth->user_id > 0) {
      $user = User::getById($oauth->user_id);
      if ($user != null) {
        User::loginSuccess($user, $token['openid']);
        if ($oauth->nickname && empty($user->title)) {
          $user->title = $oauth->nickname;
        }
        if ($oauth->avatar && empty($user->avatar)) {
          $user->avatar = $oauth->avatar;
        }
        return $user;
      }
    } else {
      // TODO: 自动注册小程序用户
    }
  }

  /**
   * 绑定手机号码
   */
  public static function mobile($mobile, $verify_code) {
    $platform = session('oauth');
    $openid = session('oauth_openid');
    if (empty($platform) || empty($openid)) {
      return false;
    }

    $oauth = self::where('platform', $platform)
      ->where('openid', $openid)->find();
    
    if ($oauth == null) {
      return false;
    }

    $user = User::loginByVerifyCode($mobile, $verify_code, $oauth);
    if ($user && $user->id) {
      $oldOauth = self::getInfo($user->id, $platform);
      if ($oldOauth) {
        self::exception('已有其他账号绑定此手机号，请更换手机号码绑定。');
      }
    }

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
    return $user;
  }

  /**
   * 根据用户ID 平台类型获取信息
   */
  public static function getInfo($user_id, $platform) {
    return self::where('user_id', $user_id)
      ->where('platform', $platform)->find();
  }
}