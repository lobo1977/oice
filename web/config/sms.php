<?php
// +----------------------------------------------------------------------
// | 短信设置
// +----------------------------------------------------------------------
return [
  // 短信应用SDK AppID
  'appid' => 1400076277,

  // 短信应用SDK AppKey
  'appkey' => "78d5e6030b0717afe0c526f431ff00b5",

  // 需要发送短信的手机号码
  'phoneNumbers' => ["13661232134", "13901109546"],

  // 短信模板ID，需要在短信应用中申请
  'tmpCodeId' => 95823,     // 验证码短信
  'tmpInviteId' => 0,       // 邀请加入短信

  // 短信签名
  'sign' => "商办云",

  // 创蓝短信接口配置
  'user' => 'yunpu888',
  'pwd' => 'Tch123456',
  'url' => 'http://222.73.117.158/msg/HttpBatchSendSM',
  'query_url' => 'http://222.73.117.158/msg/QueryBalance',
  'tmp_code' => '【商办云】您的验证码是：%s，请于 %s 分钟内填写，如非本人操作，请忽略本短信。',
  'tmp_invite' => '【商办云】您的同事%s邀请您加入%s，详情请点击：%s'
];