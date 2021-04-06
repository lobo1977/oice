<?php
namespace app\api\controller;

use think\facade\Log;
use app\common\Utils;
use app\common\Wechat as WechatApi;
use app\api\model\User;
use app\api\model\Oauth;
use app\api\controller\Base;

class Wechat extends Base
{
  protected $wechat;

  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['only'=>'switchUser']
  ];

  protected function initialize() {
    $this->wechat = new WechatApi();
  }

  /**
   * 公众号入口
   */
  public function index() {
    ob_clean();
    if (isset($_GET['echostr'])) {
			if ($this->wechat->checkSign()) {
        echo $_GET['echostr'];
      } else {
        echo '签名无效';
      }
		} else {
      $this->response();
		}
  }

  /**
   * 用户交互
   */
  public function response() {
    $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
    if (!empty($postStr)) {
      $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
      $user = $this->wechat->getUserInfo($postObj->FromUserName);
      if ($user != null) {
        $data = [
          'platform' => 'wechat',
          'openid' => $user['openid'],
          'unionid' => isset($user['unionid']) ? $user['unionid'] : '',
          'nickname' => $user['nickname'],
          'sex' => $user['sex'],
          'avatar' => $user['headimgurl']
        ];
        Oauth::add($data);
      }

      echo $this->wechat->response($postObj);
    }
  }

  /**
   * 自定义菜单
   */
  public function menu() {
    $app_root = 'https://' . config('app_host');

    $menu = array(
      'button' => array(
        array(
          'name'=>urlencode("项目"),
          'sub_button'=> array(
            array(
              'name'=> urlencode("项目浏览"),
              'type'=> 'miniprogram',
              'appid' => 'wxa797b3aba4faaa75',
              'pagepath' => 'pages/building/index/index',
              'url'=> $app_root . '/app/building'
            ),
            array(
              'name'=> urlencode("发布项目"),
              'type'=> 'miniprogram',
              'appid' => 'wxa797b3aba4faaa75',
              'pagepath' => 'pages/building/edit/edit',
              'url'=> $app_root . '/app/building/edit'
            ),
            array(
              'name'=> urlencode("联系客服"),
              'type'=> 'media_id',
              'media_id' => 'DtsusJONINVD_9nXOilQF-ZQVs7up3O6DqNv2lk2kSY'
            )
            // array(
            //   'name'=> urlencode("商铺独楼"),
            //   'type'=> 'view',
            //   'url'=> $app_root . '/app/building?type=2'
            // ),
            // array(
            //   'name'=> urlencode("商务中心"),
            //   'type'=> 'view',
            //   'url'=> $app_root . '/app/building?type=3'
            // ),
            // array(
            //   'name'=> urlencode("商住公寓"),
            //   'type'=> 'view',
            //   'url'=> $app_root . '/app/building?type=4'
            // ),
            // array(
            //   'name'=> urlencode("产业园"),
            //   'type'=> 'view',
            //   'url'=> $app_root . '/app/building?type=5'
            // )
          )
        ),
        array(
          'name'=> urlencode("客户"),
          'type'=> 'miniprogram',
          'appid' => 'wxa797b3aba4faaa75',
          'pagepath' => 'pages/customer/index/index',
          'url'=> $app_root . '/app/customer',
          'sub_button'=> array()
        ),
        array(
          'name'=> urlencode("我的"),
          'type'=> 'view',
          //'appid' => 'wxa797b3aba4faaa75',
          //'pagepath' => 'pages/my/index/index',
          'url'=> $app_root . '/app/my',
          'sub_button'=> array()
        )
      )
    );

    if ($this->wechat->menuCreate($menu)) {
      return $this->succeed();
    } else {
      return $this->fail($this->wechat->getMessage());
    }
  }

  /**
   * 获取图片素材列表
   */
  public function image() {
    $data = $this->wechat->getMaterial('image');
    return $this->succeed($data);
  }
  
  /**
   * JS-SDK 接口配置
   */
  public function config($url = '') {
    if (!Utils::isWechat()) {
      return $this->fail('非微信客户端。');
    } else if (!$url) {
      return $this->fail('URL 参数为空。');
    }

    $url = htmlspecialchars_decode($url);

    $randStr = Utils::getRandChar(32);
    $timestamp = time();
    $signature = $this->wechat->getJssdkSign($randStr, $timestamp, $url);

    if ($signature) {
      $data = [
        "debug" => false,
        "appId" => config('wechat.app_id'),
        "timestamp" => $timestamp,
        "nonceStr" => $randStr,
        "signature" => $signature
      ];
  
      return $this->succeed($data);
    } else {
      return $this->fail($this->wechat->getMessage());
    }
  }

  /**
   * 用户登录
   */
  public function login($redirect = '') {
    if ($redirect) {
      session('redirect', urldecode($redirect));
    }

    $wechatUrl = '';
    $callback = urlencode('https://' . config('app_host') . '/app/wechat/login');

    // 是否是微信客户端
    if (Utils::isWechat()) {
      $wechatUrl = sprintf(config('wechat.get_code_url'),
        config('wechat.app_id'), $callback, 'snsapi_base', config('wechat.state_code'));
    } else {
      $wechatUrl = sprintf(config('wechat.open_get_code_url'),
        config('wechat.open_app_id'), $callback, config('wechat.open_state_code'));
    }

    $this->redirect($wechatUrl);
  }

  /**
   * 登录后跳转
   */
  public function user($code, $state) {
    // 微信客户端通过 openid 自动登录
    if (!empty($code) && $state == config('wechat.state_code')) {
      $data = $this->wechat->getUserToken($code);
      if ($data) {
        $redirect = '';
        if (session('redirect')) {
          $redirect = session('redirect');
          session('redirect', null);
        }

        $user = Oauth::login($data);
        if ($user === true) {
          if ($redirect) {
            return $this->succeed(['redirect' => $redirect]);
          } else {
            return $this->succeed();
          }
        } else if ($user) {
          if ($redirect) {
            $user->redirect = $redirect;
          }
          return $this->succeed($user);
        } else {
          return $this->fail();
        }
      }
    }

    return $this->fail($this->wechat->getMessage());
  }

  /**
   * 微信小程序登录
   */
  public function miniLogin($code = '', $nickname = '', $avater = '') {
    if (!empty($code)) {
      $data = $this->wechat->getUserSession($code);
      log::info('miniLogin data:' . print_r($data, true));
      if ($data) {
        if ($nickname) {
          $data['nickname'] = $nickname;
        }
        if ($avater) {
          $data['avater'] = $avater;
        }
        $user = Oauth::miniLogin($data);
        log::info('miniLogin user:' . print_r($user, true));
        if ($user) {
          return $this->succeed($user);
        } else {
          return $this->exception('登录失败');
        }
      } else {
        return $this->exception($this->wechat->getMessage());
      }
    } else {
      return null;
    }
  }

  /**
   * 切换用户
   */
  public function switchUser() {
    if (input('?post.mobile')) {
      $mobile = input('post.mobile');
      $password = input('post.password');
      $vcode = input('post.vcode','');
      $verify_code = input('post.verifyCode');

      $user = null;

      if ($verify_code) {
        $user = User::loginByVerifyCode($mobile, $verify_code);
      } else {
        $user = User::loginByPassword($mobile, $password, $vcode);
      }

      if ($user && $this->user && $this->user->unionid) {
        $result = Oauth::switchUser($this->user->unionid, $user->id);
        if ($result) {
          $user->unionid = $this->user->unionid;
          return $this->succeed($user);
        }
      }

      return $this->fail();
    }
    return;
  }
}