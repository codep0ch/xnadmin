<?php
namespace app\miniprogram\controller;
use app\miniprogram\controller\Base;
use thans\jwt\facade\JWTAuth;
class Coupon extends Base
{
    public function init()
    {
        return json([
            'hi' => 'hi',
        ]);
    }
}