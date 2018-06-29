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
    echo $this->wechat->response();
    $user = $this->wechat->getUser();
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
  }

  /**
   * 自定义菜单
   */
  public function menu() {
    $app_root = 'http://' . config('app_host');

    $menu = array(
      'button' => array(
        array(
          'name'=>urlencode("房源"),
          'sub_button'=> array(
            array(
              'name'=> urlencode("写字楼"),
              'type'=> 'view',
              'url'=> $app_root . '/app/building?type=1'
            ),
            array(
              'name'=> urlencode("商铺独楼"),
              'type'=> 'view',
              'url'=> $app_root . '/app/building?type=2'
            ),
            array(
              'name'=> urlencode("商务中心"),
              'type'=> 'view',
              'url'=> $app_root . '/app/building?type=3'
            ),
            array(
              'name'=> urlencode("商住公寓"),
              'type'=> 'view',
              'url'=> $app_root . '/app/building?type=4'
            )
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
  public function config($url) {
    if (!Utils::isWechat() || !$url) {
      return $this->fail('非微信客户端。');
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
        "signature" => $signature,
        "jsApiList" => ['checkJsApi','showOptionMenu','hideOptionMenu',
          'onMenuShareTimeline','onMenuShareAppMessage','onMenuShareQQ',
          'openLocation','getLocation','previewImage']
      ];
  
      return $this->succeed($data);
    } else {
      return $this->fail($this->wechat->getMessage());
    }
  }
}