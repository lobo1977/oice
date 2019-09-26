<?php
namespace app\api\controller;

use think\facade\Log;
use app\common\Utils;
use app\common\Wechat as WechatApi;
use app\api\model\Oauth;
use app\api\controller\Base;

class Wechat extends Base
{
  protected $wechat;

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
              'type'=> 'view',
              'url'=> $app_root . '/app/building'
            ),
            array(
              'name'=> urlencode("发布项目"),
              'type'=> 'view',
              'url'=> $app_root . '/app/building/edit'
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
          'type'=> 'view',
          'url'=> $app_root . '/app/customer'
        ),
        array(
          'name'=> urlencode("我的"),
          'type'=> 'view',
          'url'=> $app_root . '/app/my'
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
  public function miniLogin($code = '') {
    if (!empty($code)) {
      $data = $this->wechat->getUserSession($code);
      if ($data) {
        $user = Oauth::miniLogin($data);
        if ($user) {
          return $this->succeed($user);
        }
      }
    }
    return $this->fail($this->wechat->getMessage());
  }
}