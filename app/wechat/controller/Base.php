<?php
namespace app\wechat\controller;
use app\common\model\WechatSetting as WechatSettingModel;
use EasyWeChat\Factory;
use think\App;

class Base extends \app\common\controller\Base
{
    public function __construct(App $app){
        $wechat_setting_data = WechatSettingModel::find(1);
        $config = [
            'app_id' => $wechat_setting_data['wechatAppId'],
            'secret' => $wechat_setting_data['wechatAppSecret'],

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            //...
        ];

        $app = Factory::officialAccount($config);
        // 未登录
        if (empty($_SESSION['wechat_user'])) {
            if(empty(app()->request->get('code'))){
                // $redirectUrl 为跳转目标，请自行 302 跳转到目标地址
                $redirectUrl = $app->oauth->scopes(['snsapi_userinfo'])
                    ->redirect(app()->request->url());
                $this->redirect($redirectUrl);
            }else{
                $user = $app->oauth->userFromCode(app()->request->get('code'));
                $_SESSION['wechat_user'] = $user;
            }
        }else{
            // 已经登录过
            $this->redirect('/wechat/coupon');
        }
    }
}