<?php
use think\facade\Route;
Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::any('auth2', '/Auth/init2');