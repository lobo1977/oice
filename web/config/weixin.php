<?php
// +----------------------------------------------------------------------
// | 微信相关配置
// +----------------------------------------------------------------------
return [
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
    'user_info_url' => 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN'
];