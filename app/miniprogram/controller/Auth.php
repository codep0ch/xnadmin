<?php
namespace app\wechat\controller;
use app\wechat\controller\Base;
use thans\jwt\facade\JWTAuth;
class Auth extends Base
{
    public function init()
    {
        $code = $this->request->post('code');
        $resp = $this->instance->auth->session($code);
        return $resp;
    }
}