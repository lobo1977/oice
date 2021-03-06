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
    $wechat = new Wechat();
    $openUserInfo = $wechat->getUserInfo($token['openid']);
    if (!$openUserInfo || !isset($openUserInfo['unionid'])) {
      self::exception('请先关注“商办云信息”微信公众号');
    }

    $oauth = self::where('platform', $platform)
      ->where('unionid', $openUserInfo['unionid'])->find();
    if ($oauth == null) {
      $oauth = new Oauth();
      $oauth->platform = $platform;
      $oauth->user_id = 0;
      $oauth->unionid = $openUserInfo['unionid'];
      $oauth->openid = $token['openid'];
    }
    $oauth->nickname = Utils::emojiToChar($openUserInfo['nickname']);
    $oauth->avatar = $openUserInfo['headimgurl'];
    $oauth->sex = $openUserInfo['sex'];
    $oauth->token = $token['access_token'];
    $oauth->expired_time = $token['expires_in'];
    $oauth->refresh_token = $token['refresh_token'];

    if ($oauth->save()) {
      session('oauth', $platform);
      session('unionid', $openUserInfo['unionid']);
      session('oauth_openid', $token['openid']);
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

  /**
   * 微信小程序用户登录
   */
  public static function miniLogin($token) {
    if (!$token || !isset($token['openid'])) {
      self::exception('缺少 token 或 openid');
    }

    $user = null;
    $isReg = false;
    $platform = 'wechat';
    $oauth = self::where('platform', $platform);
    if (isset($token['unionid'])) {
      $oauth->where('unionid', $token['unionid']);
    } else {
      $oauth->where('openid', $token['openid']);
    }
    $oauth = $oauth->find();
   
    if ($oauth == null) {
      $oauth = new Oauth();
      $oauth->user_id = 0;
      $oauth->platform = $platform;
      if (isset($token['unionid'])) {
        $oauth->unionid = $token['unionid'];
      } else {
        $oauth->openid = $token['openid'];
      }
      $oauth->nickname = '';
      $oauth->avatar = '';
    }

    if (isset($token['nickname'])) {
      $oauth->nickname = $token['nickname'];
    }
    if (isset($token['avatar'])) {
      $oauth->avatar = $token['avatar'];
    }
    $oauth->session_key = $token['session_key'];
    
    if ($oauth->user_id > 0) {
      $user = User::getById($oauth->user_id);
      if ($user) {
        if ($oauth->nickname && empty($user->title)) {
          $user->title = $oauth->nickname;
        }
        if ($oauth->avatar && empty($user->avatar)) {
          $user->avatar = $oauth->avatar;
        }
      }
    } else {
      $isReg = true;
      $user = new User();
      $user->type = 0;
      $user->salt = substr(md5(strval(time())), 0, 5);
      if ($oauth->nickname) {
        $user->title = $oauth->nickname;
      }
      if ($oauth->avatar) {
        $user->avatar = $oauth->avatar;
      }
      if ($user->save()) {
        $oauth->user_id = $user->id;
        $user = User::getById($user->id);
      } else {
        $user = null;
      }
    }

    $result = $oauth->save();

    if ($user) {
      return User::loginSuccess($user, isset($token['unionid']) ? $token['unionid'] : $token['openid'], $isReg);
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