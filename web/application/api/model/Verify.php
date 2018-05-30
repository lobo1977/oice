<?php
namespace app\api\model;

use think\facade\Validate;

use app\common\Utils;
use app\common\Sms;
use app\api\model\Base;

class Verify extends Base
{
  protected $pk = 'id';
  
  /**
   * 发送手机验证码
   */
  public static function send($mobile) {
    if (!Validate::checkRule($mobile, 'mobile')) {
      self::exception('手机号码无效。');
    }

    $code = Utils::getRandNumber(4);
    
    $sender = new Sms();
    $result = $sender->sendCode($mobile, $code, 2);

    if ($result == 0) {
      $vc = new Verify();
      $vc->mobile = $mobile;
      $vc->verify_code = $code;
      $vc->expire_time = date('Y-m-d H:i:s', time() + 180);   //3分钟有效
      $count = $vc->save();
  
      if ($count) {
        return true;
      } else {
        return false;
      }
    } else {
      self::exception($sender->getError());
    }
  }

  /**
   * 校验手机验证码
   */
  public static function check($mobile, $code) {
    $vc = self::where('mobile', $mobile)
      ->where('verify_code', $code)
      ->whereTime('expire_time', '>=', time())
      ->find();
    
    if ($vc == null) {
      return false;
    } else {
      return true;
    }
  }
}