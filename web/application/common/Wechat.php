<?php
namespace app\common;

use think\facade\Log;

/**
 * 微信公众号接口类
 * @author lobo
 *
 */
class Wechat {
	private $user;
	private $errcode;
	private $errmsg;
	
	/**
	 * 构造函数
	 */
	function __construct() {
		$this->user = null;
		$this->errcode = 0;
		$this->errmsg = '';
	}

	public function getUser() {
		return $this->user;
	}

	public function getCode() {
		return $this->errcode;
	}

	public function getMessage() {
		return $this->errmsg;
	}

	private function parseError($res, $function) {
		if (isset($res['errcode'])) {
			$this->errcode = $res['errcode'];
		}
		if (isset($res['errmsg'])) {
			$this->errmsg = $res['errmsg'];
			Log::error('[ Wechat ' . $function . ' ] ' .
				$this->errmsg . '(' . $this->errcode . ')');
		}
	}
	
	/**
	 * 获取微信 access_token
	 * @return unknown|mixed|object
	 */
	public function getAccessToken() {
		$token = cache('wx_token');
		if (!$token) {
			$url = sprintf(config('wechat.token_url'), 
				config('wechat.app_id'), config('wechat.app_secret'));
			
			$res = file_get_contents($url);
			$res = json_decode($res, true);
			
			if (isset($res['access_token'])) {
				$token = $res['access_token'];
				cache('wx_token', $token, 5400);	//token 有效期为两小时，缓存设定为1小时30分钟
			} else {
				$this->parseError($res, 'getAccessToken');
			}
		}
		return $token;
	}

	/**
	 * 获取微信 JS-SDK ticket
	 * @return unknown|mixed|object
	 */
	public function getJssdkTicket() {
		$ticket = cache('wx_ticket');
		
		if (empty($ticket)) {
			$token = $this->getAccessToken();
			
			if (!empty($token)) {
				$url = sprintf(config('wechat.ticket_url'), $token, 'jsapi');
				$res = file_get_contents($url);
				$res = json_decode($res, true);

				if (isset($res['ticket'])) {
					$ticket = $res['ticket'];
					cache('wx_ticket', $ticket, 5400);
				} else {
					$this->parseError($res, 'getJssdkTicket');
				}
			}
		}
		return $ticket;
	}
	
	/**
	 * 获取JS-SDK使用权限签名
	 * @param unknown $nonceStr
	 * @param unknown $timestamp
	 * @param unknown $url
	 * @return string
	 */
	public function getJssdkSign($nonceStr, $timestamp, $url) {
		$ticket = $this->getJssdkTicket();
		if ($ticket) {
			$strOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $ticket, $nonceStr,
				$timestamp, $url);
			return sha1($strOri);
		} else {
			return '';
		}
	}

	/**
	 * 自定义菜单
	 */
	public function menuCreate($menu) {
		$access_token = $this->getAccessToken();
		if ($access_token) {
			$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
			$jsondata = urldecode(json_encode($menu));
			$res = $this->https_request($url, $jsondata);
			$this->errcode = $res['errcode'];
			if ($this->errcode == 0) {
				return true;
			} else {
				$this->parseError($res, 'menuCreate');
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * 获取用户信息
	 * @param unknown $openid
	 * @return mixed
	 */
	public function getUserInfo($openid) {
		$access_token = $this->getAccessToken();
		if ($access_token) {
			$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN";
			$url = sprintf($url, $access_token, $openid);
			$res = file_get_contents($url);
			$res = json_decode($res, true);
			if (isset($res['subscribe'])){
				if ($res['subscribe'] == 1) {
					return $res;
				} else {
					$this->errmsg = '用户未关注。';
					return null;
				}
			} else {
				$this->parseError($res, 'getUserInfo');
				return null;
			}
		} else {
			return null;
		}
	}
	
	/**
	 * 获取短网址
	 * @param unknown $longUrl
	 * @return array
	 */
	public function getShortUrl($longUrl) {
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token=" .  $access_token;
		$arr = array (
			"action" => "long2short",
			"long_url" => $longUrl
		);
		$jsondata = json_encode($arr);
		$res = $this->https_request($url, $jsondata);
		if (isset($res['short_url'])) {
			return $res['short_url'];
		} else {
			$this->parseError($res, 'getShortUrl');
			return $longUrl;
		}
	}
	
	/**
	 * 验证签名
	 */
	public function checkSign() {
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
		$token = config('wechat.token');
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		return $tmpStr == $signature;
	}

	/**
	 * 发送消息
	 * @param unknown $openId
	 * @param unknown $msg
	 * @return array
	 */
	private function sendMsg($openId, $msg) {
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;
		$jsondata = urldecode(json_encode($msg));
		$res = $this->https_request($url, $jsondata);
		if (isset($res['errcode']) && $res['errcode'] == 0) {
			return true;
		} else {
			$this->parseError($res, 'sendMsg');
			return false;
		}
	}
	
	/**
	 * 发送文本消息
	 * @param unknown $openId
	 * @param unknown $message
	 * @return array
	 */
	public function sendTextMsg($openId, $message) {
		$arr = array (
			"touser" => $openId,
			"msgtype" => "text",
			"text" => array(
				"content" => $message
			)
		);
		return $this->sendMsg($openId, $arr);
	}
	
	/**
	 * 发送图文消息
	 * @param unknown $openId
	 * @param unknown $message
	 * @return array
	 */
	public function sendNewsMsg($openId, $title, $description, $url, $picUrl) {
		$arr = array (
			"touser" => $openId,
			"msgtype" => "news",
			"news" => array(
				"articles" => array(
					array (
						"title" => $title,
						"description" => $description,
						"url" => $url,
						"picurl" => $picUrl
					)
				)
			)
		);
		return $this->sendMsg($openId, $arr);
	}
	
	/**
	 * 接收并响应消息
	 */
	public function response() {
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		if (!empty($postStr)){
			
			$this->logger("Receive: ".$postStr);
			
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$RX_TYPE = trim($postObj->MsgType);
			$this->user = $this->getUserInfo($postObj->FromUserName);
			
			//消息类型路由
			switch ($RX_TYPE) {
				case "event":
					$result = $this->receiveEvent($postObj);
					break;
				case "text":
					$result = $this->receiveText($postObj);
					break;
				case "image":
					$result = $this->receiveImage($postObj);
					break;
				case "location":
					$result = $this->receiveLocation($postObj);
					break;
				case "voice":
					$result = $this->receiveVoice($postObj);
					break;
				case "video":
					$result = $this->receiveVideo($postObj);
					break;
				case "link":
					$result = $this->receiveLink($postObj);
					break;
				default:
					$result = $this->transmitText($postObj, "对不起，不知道你在说什么啊。 " .  $RX_TYPE);
					break;
			}
			
			$this->logger("Response: " . $result);
			
			return $result;
		} else {
			return "";
		}
	}
	
	/**
	 * 接收事件消息
	 */
	private function receiveEvent($object) {
		$content = "";
		
		switch ($object->Event) {
			//关注
			case "subscribe":
				$content = "欢迎关注" . config('app_name');
				break;
			//取消关注
			case "unsubscribe":
				break;
			//点击菜单
			case "CLICK":
				if ($object->EventKey == "SIGN") {
					if ($this->user) {
						// $Sign = D("Mobile/Sign");
						// $res = $Sign->sign(0, $this->user['openid']);
						// if ($res) {
						// 	$content = '签到成功，您获得 2 聚币奖励。';
						// } else {
						// 	$content = $Sign->getError();
						// }
					}
				}
				break;
			//跳转链接
			case "VIEW":
				break;
			//扫一扫
			case "SCAN":
				break;
			//上报地址
			case "LOCATION":
				break;
			//扫码带提示
			case "scancode_waitmsg":
				break;
			//扫码推事件
			case "scancode_push":
				break;
			//系统拍照
			case "pic_sysphoto":
				break;
			//相册发图
			case "pic_weixin":
				break;
			//拍照或者相册
			case "pic_photo_or_album":
				break;
			//发送位置
			case "location_select":
				break;
			//其他事件
			default:
				break;
		}
		
		return $this->returnMsg($object, $content);
	}
	
	/**
	 * 接收文本消息
	 * @param unknown $object
	 */
	private function receiveText($object) {
		$content = "";
		$keyword = trim($object->Content);
		
		if ($keyword == "签到") {
			if ($this->user) {
				// $Sign = D("Mobile/Sign");
				// $res= $Sign->sign(0, $this->user['openid']);
				// if ($res) {
				// 	$content = '签到成功，您获得 2 聚币奖励。';
				// } else {
				// 	$content = $Sign->getError();
				// }
			}
		}
		
		return $this->returnMsg($object, $content);
	}
	
	/**
	 * 接收图片消息
	 * @param unknown $object
	 * @return unknown
	 */
	private function receiveImage($object) {
		$content = "";
		//$content = array("MediaId"=>$object->MediaId);
		$result = $this->transmitImage($object, $content);
		return $result;
	}
	
	/**
	 * 接收位置消息
	 * @param unknown $object
	 * @return unknown
	 */
	private function receiveLocation($object) {
		$content = "";
		//$content = "你发送的是位置，经度为：".$object->Location_Y."；纬度为：".$object->Location_X."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
		$result = $this->transmitText($object, $content);
		return $result;
	}
	
	/**
	 * 接收语音消息
	 * @param unknown $object
	 * @return unknown
	 */
	private function receiveVoice($object) {
		$content = "";
		if (isset($object->Recognition) && !empty($object->Recognition)) {
			//$content = "你刚才说的是：". $object->Recognition;
		} else {
			//$content = array("MediaId"=>$object->MediaId);
		}
		$result = $this->transmitVoice($object, $content);
		return $result;
	}
	
	/**
	 * 接收视频消息
	 * @param unknown $object
	 * @return unknown
	 */
	private function receiveVideo($object) {
		$content = "";
		//$content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");
		$result = $this->transmitVideo($object, $content);
		return $result;
	}
	
	/**
	 * 接收链接消息
	 * @param unknown $object
	 * @return unknown
	 */
	private function receiveLink($object) {
		$content = "";
		//$content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
		$result = $this->transmitText($object, $content);
		return $result;
	}
	
	/**
	 * 回复文本、图文音乐消息
	 * @param unknown $content
	 * @return unknown
	 */
	private function returnMsg($object, $content) {
		if (is_array($content)) {
			if (isset($content[0]['PicUrl'])) {
				$result = $this->transmitNews($object, $content);
			} else if (isset($content['MusicUrl'])) {
				$result = $this->transmitMusic($object, $content);
			}
		} else {
			$result = $this->transmitText($object, $content);
		}
		return $result;
	}
	
	/**
	 * 回复文本消息
	 * @param unknown $object
	 * @param unknown $content
	 * @return string
	 */
	private function transmitText($object, $content) {
		if (!isset($content) || empty($content)){
			return "";
		}
		
		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content></xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
		
		return $result;
	}
	
	/**
	 * 回复图文消息
	 * @param unknown $object
	 * @param unknown $newsArray
	 * @return string
	 */
	private function transmitNews($object, $newsArray) {
		if(!is_array($newsArray)){
			return "";
		}
		$itemTpl = "<item><Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			<PicUrl><![CDATA[%s]]></PicUrl>
			<Url><![CDATA[%s]]></Url></item>";
		
		$item_str = "";
		foreach ($newsArray as $item){
			$item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
		}
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles> $item_str  </Articles></xml>";
		
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
		
		return $result;
	}
	
	/**
	 * 回复音乐消息
	 * @param unknown $object
	 * @param unknown $musicArray
	 * @return string
	 */
	private function transmitMusic($object, $musicArray) {
		if(!is_array($musicArray)){
			return "";
		}
		
		$itemTpl = "<Music>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			<MusicUrl><![CDATA[%s]]></MusicUrl>
			<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
			</Music>";
		
		$item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);
		
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[music]]></MsgType> $item_str </xml>";
		
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		
		return $result;
	}
	
	/**
	 * 回复图片消息
	 * @param unknown $object
	 * @param unknown $imageArray
	 * @return string
	 */
	private function transmitImage($object, $imageArray) {
		$itemTpl = "<Image><MediaId><![CDATA[%s]]></MediaId></Image>";
		$item_str = sprintf($itemTpl, $imageArray['MediaId']);
		
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[image]]></MsgType> $item_str </xml>";
		
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}
	
	/**
	 * 回复语音消息
	 * @param unknown $object
	 * @param unknown $voiceArray
	 * @return string
	 */
	private function transmitVoice($object, $voiceArray) {
		$itemTpl = "<Voice><MediaId><![CDATA[%s]]></MediaId></Voice>";
		$item_str = sprintf($itemTpl, $voiceArray['MediaId']);
		
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[voice]]></MsgType> $item_str </xml>";
		
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}
	
	/**
	 * 回复视频消息
	 * @param unknown $object
	 * @param unknown $videoArray
	 * @return string
	 */
	private function transmitVideo($object, $videoArray) {
		$itemTpl = "<Video>
			<MediaId><![CDATA[%s]]></MediaId>
			<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			</Video>";
		
		$item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);
		
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[video]]></MsgType> $item_str </xml>";
		
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		
		return $result;
	}
	
	/**
	 * 回复客服消息
	 * @param unknown $object
	 * @return string
	 */
	private function transmitService($object) {
		$xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[transfer_customer_service]]></MsgType>
			</xml>";
		
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		
		return $result;
	}
	
	/**
	 * 上传多媒体素材并获取缩略图ID
	 * @param unknown $filename
	 * @return mixed
	 */
	function addMaterial($filename) {
		$access_token = $this->getAccessToken();
		$type = 'image';
		$url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$access_token."&type=".$type;
		$data = array("media"=>'@'. $filename);
		$res = $this->https_request($url, $data);
		return $res['media_id'];
	}
	
	/**
	 * 上传图文消息内的图片获取URL
	 * @return unknown
	 */
	function uploadImage($filename) {
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".$access_token;
		$data = array("media" => '@'. $filename);
		$res = $this->https_request($url, $data);
		return $res['url'];
	}
	
	/**
	 * 上传图文素材
	 * @return unknown
	 */
	function uploadNews($author, $title, $thumb, $images, $content, $digest, $source) {
		//1.获取全局access_token
		$access_token = $this->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token=". $access_token;
		
		//2.组装数据
		$thumb_media_id = $this->addMaterial($thumb);
		$content = urlencode($content);
		
		if ($images) {
			for ($i = 0; $i < count($images); $i++) {
				$img_url = $this->uploadImage($images[$i]);
				if ($img_url) {
					$content = $content . '<br/><a src="' . $img_url . '" style="margin:10px 0;">';
				}
			}
		}

		$array = array(
			"articles" => array( 	/*若新增的是多图文素材，则此处应还有几段articles结构  */
				array(
					"thumb_media_id"      => $thumb_media_id,        //图文消息缩略图的media_id
					"author"              => urlencode($author),            //作者
					"title"               => urlencode($title),					//标题
					"content_source_url" => $source,    //图文消息的原文地址，即点击“阅读原文”后的URL
					"content"             => $content,       //图文消息的具体内容，支持HTML标签，必须少于2万字符，小于1M，且此处会去除JS
					"digest"             => urlencode($digest),            //图文消息的摘要，仅有单图文消息才有摘要，多图文此处为空
					"show_cover_pic"      => 1            //是否显示封面，0为false，即不显示，1为true，即显示
				)
			)
		);
		$postJson = urldecode(json_encode($array));
		$res = $this->https_request($url, $postJson);
		return $res['media_id'];
	}
	
	/**
	 * 发送预览消息
	 * @return unknown
	 */
	function sendMsgForPreview($openid, $media_id, $content) {
		//1.获取全局access_token
		$access_token = $this->getAccessToken();
		//2.组装群发预览接口数据  array
		$url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=".$access_token;
		$array = array();
		$array['touser'] = $openid;
		if ($media_id) {
			$array['mpnews'] = array("media_id"=>$media_id);
			$array['msgtype'] = "mpnews";
		} else if ($content) {
			$array['text'] = array("content"=>$content);
			$array['msgtype'] = "text";
		} else {
			return false;
		}
		//3.将数组转成json格式
		$postJson = json_encode($array);
		//4.调用第三方接口
		$res = $this->https_request($url, $postJson);
		return $res;
	}
	
	/**
	 * 发送群发消息
	 * @param unknown $media_id
	 * @param unknown $content
	 * @return boolean
	 */
	function sendMsgToAll($media_id, $content) {
		//1.获取全局access_token
		$access_token = $this->getAccessToken();
		//2.组装群发预览接口数据  array
		$url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=".$access_token;
		$array = array();
		$array['filter'] = array('is_to_all' => true, 'tag_id'=>'');
		if ($media_id) {
			$array['mpnews'] = array("media_id"=>$media_id);
			$array['msgtype'] = "mpnews";
		} else if ($content) {
			$array['text'] = array("content"=>$content);
			$array['msgtype'] = "text";
		} else {
			return false;
		}
		//3.将数组转成json格式
		$postJson = json_encode($array);
		//4.调用第三方接口
		$res = $this->https_request($url, $postJson);
		return $res;
	}

	/**
	 * 消息日志
	 */
	private function logger($log_content) {
		Log::info('[ Wechat ] ' . $log_content);
	}

	/**
	 * 构造HTTP请求
	 */
	private function https_request($url, $data = '', $type='post', $res='json') {
		//1.初始化curl
		$curl = curl_init();
		//2.设置curl的参数
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,2);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if ($type == "post"){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		//3.采集
		$output = curl_exec($curl);
		//4.关闭
		curl_close($curl);
		if ($res == 'json') {
			return json_decode(htmlspecialchars_decode($output),true);
		} else {
			return $output;
		}
	}

	/**
	 * 获取用户 openid 及 access_token
	 */
	public function getUserToken($code) {
		$url = sprintf(config('wechat.access_token_url'), 
		  	config('wechat.app_id'), config('wechat.app_secret'), $code);
		$res = file_get_contents($url);
		$res = json_decode($res, true);
		if (isset($res['access_token'])) {
			return $res;
		} else {
			$this->parseError($res, 'getUserToken');
			return null;
		}
	}
}