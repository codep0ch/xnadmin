<?php
namespace app\miniprogram\controller;
use app\miniprogram\controller\Base;
use thans\jwt\facade\JWTAuth;
class Auth extends Base
{
    public function init()
    {
        $code = $this->request->get('code');
        $resp = $this->instance->auth->session($code);
        $token = JWTAuth::builder($resp);
        return commonApiReturn(200, [
            'token' => $token,
            'expire' => 7200,
            'expire_time' => time()
        ], 'Success');
    }
}