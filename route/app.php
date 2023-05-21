<?php
use think\facade\Route;
Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::any('wechat/auth2', 'wechat/Auth/Auth2');