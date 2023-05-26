<?php
namespace app\miniprogram\controller;
use EasyWeChat\Factory;
use think\App;
use thans\jwt\facade\JWTAuth;

class Base extends \app\common\controller\Base
{
    public $instance = null;
    public function __construct(App $app){
        $config = [
            'app_id' => 'wxa4c7b2c91daafc5f',
            'secret' => '65de6e5f23d9f86155d9dfe84e10b969',
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array'
        ];
        $this->instance = Factory::miniProgram($config);

        $tokenStr = JWTAuth::token()->get(); //可以获取请求中的完整token字符串
        $payload = JWTAuth::auth(); //可验证token, 并获取token中的payload部分
        parent::__construct($app);
    }
}