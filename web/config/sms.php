<?php
// +----------------------------------------------------------------------
// | 短信设置
// +----------------------------------------------------------------------
return [
  // 短信签名
  'sign' => "商办云",
  // 需要发送短信的手机号码
  'phoneNumbers' => ["13661232134"],

  // 腾讯云短信接口配置
  'appid' => 1400076277,
  'appkey' => "78d5e6030b0717afe0c526f431ff00b5",
  // 短信模板ID，需要在短信应用中申请
  'tmpCodeId' => 95823,     // 验证码短信
  'tmpInviteId' => 0,       // 邀请加入短信

  // 创蓝短信接口配置
  'user' => 'N1260547',
  'pwd' => 'FIKs6QeZlac440',
  'url' => 'https://smssh1.253.com/msg/send/json',
  'query_url' => 'https://smssh1.253.com/msg/balance/json',
  'tmp_code' => '您的验证码是：%s，请于 %s 分钟内填写，如非本人操作，请忽略本短信。',
  'tmp_invite' => '您的同事%s邀请您加入“%s”，详情请点击：%s',
  'tmp_commission_confirm' => '请确认您在商办云（小程序）平台的委托信息：%s。请登录商办云（小程序）并绑定本手机号，搜索该项目，即可长期维护该项目。'
];