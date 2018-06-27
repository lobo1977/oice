<?php
namespace app\api\controller;

use think\facade\Log;
use app\common\Utils;
use app\common\Wechat as WechatApi;
use app\api\controller\Base;

class Wechat extends Base
{
  protected $wechat;

  protected function initialize() {
    $this->wechat = new WechatApi();
  }
  
  public function config($url) {
    if (!Utils::isWechat() || !$url) {
    }

    $randStr = Utils::getRandChar(32);
    $timestamp = time();

    $data = [
      "debug" => false,
      "appId" => config('wechat.app_id'),
      "timestamp" => $timestamp,
      "nonceStr" => $randStr,
      "signature" => $this->wechat->get_jssdk_sign($randStr, $timestamp, $url),
      "jsApiList" => ['checkJsApi','showOptionMenu','hideOptionMenu',
        'onMenuShareTimeline','onMenuShareAppMessage','onMenuShareQQ',
        'openLocation','getLocation','previewImage']
    ];

    return $this->succeed($data);
  }
}