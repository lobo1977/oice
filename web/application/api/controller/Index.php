<?php

namespace app\api\controller;

use think\captcha\Captcha;
use app\common\Utils;
use app\common\Wechat;
use app\api\controller\Base;
use app\api\model\User;
use app\api\model\Oauth;
use app\api\model\Verify;
use app\api\model\Building;
use app\api\model\Unit;
use app\api\model\Article;

class Index extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['only' => 'getUserInfo,updateToken']
  ];

  protected function initialize()
  {
  }

  // 首页
  public function index($city = '')
  {
    $params = [
      'city' => $city,
      'page_size' => 3,
      'banner' => 1
    ];
    $banner = Building::search($this->user, $params);

    $unit = Unit::search($this->user, ['city' => $city, 'page_size' => 5]);

    return $this->succeed([
      'banner' => $banner,
      'unit' => $unit
    ]);
  }

  public function index2($city = '')
  {
    $banner = Article::search($this->user, ['page_size' => 3, 'banner' => 1, 'status' => 1]);
    $article = Article::search($this->user, ['page_size' => 10, 'status' => 1]);
    $unit = Unit::search($this->user, ['city' => $city, 'page_size' => 5]);
    return $this->succeed([
      'banner' => $banner,
      'article' => $article,
      'unit' => $unit
    ]);
  }

  public function article($type = 0) {
    $list = Article::search($this->user, ['page_size' => 5, 'type' => $type, 'status' => 1]);
    return $this->succeed($list);
  }

  /**
   * 图片验证码
   */
  public function verify()
  {
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
  public function login()
  {
    if (input('?post.mobile')) {
      $mobile = input('post.mobile');
      $password = input('post.password');
      $vcode = input('post.vcode', '');
      $verify_code = input('post.verifyCode');

      $user = null;

      if ($verify_code) {
        $user = User::loginByVerifyCode($mobile, $verify_code);
      } else {
        $user = User::loginByPassword($mobile, $password, $vcode);
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
   * 第三方登录绑定手机号码
   */
  public function mobile()
  {
    if (input('?post.mobile')) {
      $mobile = input('post.mobile');
      $verify_code = input('post.verifyCode');

      $user = Oauth::mobile($mobile, $verify_code);

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
  public function sendVerifyCode()
  {
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
  public function getUserInfo()
  {
    return $this->succeed($this->user);
  }

  /**
   * 更新 Token
   */
  public function updateToken()
  {
    $newToken = User::updateToken($this->user);
    if ($newToken) {
      return $this->succeed($newToken);
    } else {
      return $this->fail();
    }
  }

  /**
   * 退出登录
   */
  public function logout()
  {
    if ($this->getUser() != null) {
      User::logout($this->user);
    }
    return $this->succeed();
  }

  /**
   * 表单令牌
   */
  public function token()
  {
    return $this->succeed($this->formToken());
  }

  public function test()
  {
    $video = "d:\\5e86c65196cba.mp4";
    $path = "d:\\5e86c65196cba.jpg";
    Utils::getVideoCover($video, $path);
    echo 'ok';
  }

  public function push() {
    $wechat = new Wechat();
    $page = "/pages/building/view/view?id=4515";
    $data = array(
      "character_string1" => [
        "value" => '12345678'
      ],
      "thing13" => [
        "value" => '项目名称'
      ],
      "date3" => [
        "value" => date("Y年m月d日 H:i", time()),
      ],
      "thing2" => [
        "value" => '审核通过'
      ],
      "thing6" => [
        "value" => '备注信息'
      ]
    );
    $result = $wechat->sendTemplateMsg('oMffm5SH8PEaW8NQlvNPYZRqpa8o', 
      config('wechat.mini_template_building_audit'), $page, $data);
    
    return $result;
  }
}
