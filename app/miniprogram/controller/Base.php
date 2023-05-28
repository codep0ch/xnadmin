<?php
namespace app\miniprogram\controller;
use EasyWeChat\Factory;
use think\App;


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


        parent::__construct($app);
    }
}