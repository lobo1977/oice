<?php
namespace app\common;

use Qcloud\Sms\SmsSingleSender;
use app\common\Wechat;

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
    $company = $company . config('app_name');
    $wechat = new Wechat();
    $url = 'https://' . config('app_host') . '/app/login';
    $url = $wechat->getShortUrl($url);
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
			'account'  => config('sms.user'),
			'password' => config('sms.pwd'),
			'msg' => $msg,
			'phone' => $mobile,
			'report' => $needstatus,
    );
		$result = $this->curlPost(config('sms.url'), $postArr);
    if ($result) {
      $r = json_decode($result, true);
      if (!is_null($r) && isset($r['code'])) {
        if ($r['code'] != '0') {
          $this->error = '短信发送失败，错误码：' . $r['code'];
        }
        return $r['code'];
      } else {
        $this->error = $result;
      }
    }
    return false;
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
    //查询参数
		$postArr = array ( 
      'account' => config('sms.user'),
      'password' => config('sms.pwd'),
    );
    $result = $this->curlPost(config('sma.query_url'), $postArr);
    if ($result) {
      $r = json_decode($result, true);
      if (!is_null($r) && isset($r['balance'])) {
        return $r['balance'];
      } else if (!is_null($r) && isset($r['errorMsg'])) {
        $this->error = $r['errorMsg'];
      } else {
        $this->error = $result;
      }
    }
    return false;
  }

  /**
   * 获取错误信息
   */
  public function getError() {
    return $this->error;
  }

  /**
	 * 通过CURL发送HTTP请求
	 * @param string $url  //请求URL
	 * @param array $postFields //请求参数 
	 * @return mixed
	 */
	private function curlPost($url,$postFields){
		$postFields = json_encode($postFields);
		$ch = curl_init ();
		curl_setopt( $ch, CURLOPT_URL, $url ); 
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			  'Content-Type: application/json; charset=utf-8'   //json版本需要填写  Content-Type: application/json;
			)
		);
		curl_setopt( $ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //若果报错 name lookup timed out 报错时添加这一行代码
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt( $ch, CURLOPT_TIMEOUT, 60); 
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		$ret = curl_exec ( $ch );
    if (false == $ret) {
      $result = curl_error($ch);
    } else {
      $rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
      if (200 != $rsp) {
        $result = "请求状态 ". $rsp . " " . curl_error($ch);
      } else {
        $result = $ret;
      }
    }
		curl_close ( $ch );
		return $result;
	}
}