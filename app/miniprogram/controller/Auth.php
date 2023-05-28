<?php
namespace app\miniprogram\controller;
use app\miniprogram\controller\Base;
use app\miniprogram\middleware\JwtBaseService;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;

class Auth extends Base
{
    /**
     * @throws InvalidConfigException
     */
    public function init(): \think\response\Json
    {
        $code = $this->request->get('code');
        $resp = $this->instance->auth->session($code);
        $token = JwtBaseService::getInstance()->createToken($resp->toArray());

        return commonApiReturn(200, [
            'token' => $token,
            'expire' => 7200,
            'expire_time' => time()
        ], 'Success');
    }
}