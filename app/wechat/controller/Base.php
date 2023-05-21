<?php
namespace app\wechat\controller;
use EasyWeChat\Factory;
class Base
{
    public function __construct(){
        $config = [
            'app_id' => 'wx3cf0f39249eb0exx',
            'secret' => 'f1c242f4f28f735d4687abb469072axx',

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            //...
        ];

        $app = Factory::officialAccount($config);
        // $redirectUrl 为跳转目标，请自行 302 跳转到目标地址
        $redirectUrl = $app->oauth->scopes(['snsapi_userinfo'])
            ->redirect();
        echo $redirectUrl;
    }
}