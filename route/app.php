<?php
use think\facade\Route;
Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('auth', '/wechat/Auth/init2');