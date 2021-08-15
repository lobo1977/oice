<?php
// +----------------------------------------------------------------------
// | 微信相关配置
// +----------------------------------------------------------------------
return [
    // 微信公众平台配置
    'app_id' => 'wx64a7cd02fbf372de',
    'app_secret' => '3f78e1cf4086a8e6b4ece920442e334c',
    'app_uid' => 'gh_130b424fb5b3',
    'token' => 'oice_0127',
    'state_code' => 'OICE',

    'token_url' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s',
    'ticket_url' => 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=%s',
    'get_code_url' => 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s'
        . '&redirect_uri=%s&response_type=code&scope=%s&state=%s#wechat_redirect',
    'access_token_url' => 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code',
    'refresh_token_url' => 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code',
    'user_info_url' => 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN',

    // 微信开放平台配置
    'open_app_id' => '',
    'open_app_secret' => '',
    'open_state_code' => 'OPEN_OICE',
    'open_get_code_url' => 'https://open.weixin.qq.com/connect/qrconnect?appid=%s'
        . '&redirect_uri=%s&response_type=code&scope=snsapi_login&state=%s#wechat_redirect',

    // 微信小程序配置
    'mini_app_id' => 'wxa797b3aba4faaa75',
    'mini_app_secret' => 'a168d1ddd69363276f9303d3280c6650',
    'mini_get_user_session_url' => 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
    'mini_get_access_token_url' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s',
    'mini_get_code_url' => 'https://api.weixin.qq.com/wxa/getwxacode?access_token=%s',
    'mini_template_building_audit' => '1CWqFvCATiyHzJUWKddmwLDxKGcdsx_aznWhqH'
];  