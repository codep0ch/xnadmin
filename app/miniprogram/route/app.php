<?php
use think\facade\Route;


Route::get('auth', '/wechat/Auth/init');
//分组路由与中间件的应用 （多应用模式）
//group （第一个参数路由组名称 ，）
Route::group('', function(){
    Route::get('info', '/wechat/Coupon/init');
    Route::post('doConsume', '/wechat/Coupon/doConsume');
})->middleware(\app\miniprogram\middleware\ApiMiddleware::class);
