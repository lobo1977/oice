<?php
namespace app\common;

use Qcloud\Sms\SmsSingleSender;

/**
 * 手机短信接口类
 * @author lobo
 *
 */
class Sms {
  private $error;
  private $gatway;
	
  /**
   * 构造函数
   */
  function __construct($gatway = '') {
    if ($gatway == 'TX' || $gatway == 'CL') {
      $this->getway = $gatway;
    } else {
      $this->getway = 'CL';
    }
    $this->error = "";
  }

	/**
	 * 发送验证码
	 */
  public function sendCode($mobile, $code, $time) {
    if ($this->gatway == 'TX') {
      return $this->sendSMSTX($mobile, 'sms.tmpCodeId', [$code, $time]);
    } else {
      $sms = sprintf(config('sms.tmp_code'), $code, $time);
      return $this->sendSMS($mobile, $sms);
    }
  }

  /**
   * 发送邀请
   */
  public function sendInvite($mobile, $username, $company) {
    $company = $company . ' ' . config('app_name');
    $url = 'http://' . config('app_host') . '/app/login';
    if ($this->gatway == 'TX') {
      return $this->sendSMSTX($mobile, 'sms.tmpInviteId', [$username, $company, $url]);
    } else {
      $sms = sprintf(config('sms.tmp_invite'), $username, $company, $url);
      return $this->sendSMS($mobile, $sms);
    }
  }
	
  /**
   * 发送短信 API (创蓝)
   *
   * @param string $mobile 手机号码
   * @param string $msg 短信内容
   * @param string $needstatus 是否需要状态报告
   * @param string $product 产品id，可选
   * @param string $extno   扩展码，可选
   */
  public function sendSMS($mobile, $msg, $needstatus = 'false', $product = '', $extno = '') {
    //创蓝接口参数
    $postArr = array (
      'account' => config('sms.user'),
      'pswd' => config('sms.pwd'),
      'msg' => $msg,
      'mobile' => $mobile,
      'needstatus' => $needstatus,
      'product' => $product,
      'extno' => $extno
    );
    $result = $this->curlPost(config('sms.url'), $postArr);
    if ($result) {
      $r = $this->execResult($result);
      if ($r[1] != 0) {
        $this->error = '短信发送失败，错误码：' . $r[1];
      }
      return $r[1];
    } else {
      return false;
    }
  }

  /**
   * 发送短信 API（腾讯云）
   */
  public function sendSMSTX($mobile, $template, $params) {
    $appid = config('sms.appid');
    $appkey = config('sms.appkey');
    $templateId = config($template);
    $sign = config('sms.sign');

    $ssender = new SmsSingleSender($appid, $appkey);
    $result = $ssender->sendWithParam("86", $mobile, $templateId,
    $params, $sign, "", "");

    $rsp = json_decode($result);

    if ($rsp->result != 0) {
      $this->error = $rsp->errmsg;
    }
    return $rsp->result;
  }

  /**
   * 查询余额（创蓝）
   * @return boolean
   */
  public function getBalance() {
    $postArr = array (
      'account' => config('sms.user'),
      'pswd' => config('sms.pwd')
    );
    $result = $this->curlPost(config('sma.query_url'), $postArr);
    if ($result) {
      return $this->execResult($result);
    } else {
      return false;
    }
  }

  /**
   * 获取错误信息
   */
  public function getError() {
    return $this->error;
  }

  /**
   * 处理返回值（创蓝）
   */
  private function execResult($result) {
    $result = preg_split("/[,\r\n]/", $result);
    return $result;
  }

  /**
   * 通过CURL发送HTTP请求
   * @param string $url  //请求URL
   * @param array $postFields //请求参数
   * @return mixed
   */
  private function curlPost($url,$postFields){
    $postFields = http_build_query($postFields);
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_POST, 1 );
    curl_setopt ( $ch, CURLOPT_HEADER, 0 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );
    $result = curl_exec ( $ch );
    curl_close ( $ch );
    return $result;
  }
}