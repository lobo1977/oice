<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::rule('api/verify','api/index/verify');
Route::rule('api/login','api/index/login');
Route::rule('api/mobile','api/index/mobile');
Route::rule('api/sendVerifyCode','api/index/sendVerifyCode');
Route::rule('api/getUserInfo','api/index/getUserInfo');
Route::rule('api/updateToken','api/index/updateToken');
Route::rule('api/logout','api/index/logout');
Route::rule('index/print/:id/:mode','index/recommend/index');
Route::rule('index/print/:id','index/recommend/index');
Route::rule('index/import','index/index/import');

return [

];
