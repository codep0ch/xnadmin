<?php
namespace app\wechat\controller;
use app\wechat\controller\Base;

class Auth extends Base
{
    public function init2()
    {
        $code = $this->request->post('code');
        $resp = $this->instance->auth->session($code);
        return $resp;
    }
}