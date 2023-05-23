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
        return json($resp);
    }
}