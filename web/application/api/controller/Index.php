<?php
namespace app\api\controller;

use think\captcha\Captcha;
use app\api\controller\Base;
use app\api\model\User;
use app\api\model\Verify;

class Index extends Base
{
  protected $beforeActionList = [
    'checkAuth' => ['only'=>'getUserInfo,updateToken']
  ];

  protected function initialize() {
  }

  public function index() {
    return;
  }

  /**
   * 图片验证码
   */
  public function verify() {
    ob_clean();
    $captcha = new Captcha();
    $captcha->expire = 300;
    $captcha->fontSize = 20;
    $captcha->length   = 4;
    $captcha->imageH = 44;
    return $captcha->entry();
  }
  
  /**
   * 用户登录
   */
  public function login() {
    if (input('?post.mobile')) {
      $mobile = input('post.mobile');
      $password = input('post.password');
      $vcode = input('post.vcode','');
      $verify_code = input('post.verifyCode');

      $user = null;

      if ($password) {
        $user = User::loginByPassword($mobile, $password, $vcode);
      } else if ($verify_code) {
        $user = User::loginByVerifyCode($mobile, $verify_code);
      } else {
        return;
      }

      if ($user) {
        return $this->succeed($user);
      } else {
        return $this->fail();
      }
    }
    return;
  }

  /**
   * 发送手机验证码
   */
  public function sendVerifyCode() {
    if (input('?post.mobile')) {
      if (Verify::send(input('post.mobile'))) {
        return $this->succeed();
      } else {
        return $this->fail();
      }
    }
    return;
  }

  /**
   * 获取当前登录用户信息
   */
  public function getUserInfo() {
    return $this->succeed($this->user);
  }


  /**
   * 更新 Token
   */
  public function updateToken() {
    $newToken = User::updateToken($this->user_id, $this->user['token']);
    if ($newToken) {
      return $this->succeed($newToken);
    } else {
      return $this->fail();
    }
  }

  /**
   * 退出登录
   */
  public function logout() {
    $token = request()->header('User-Token');
    if ($token) {
      User::logout($token);
    }
    return $this->succeed();
  }
}
