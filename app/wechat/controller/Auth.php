<?php
namespace app\wechat\controller;
use app\wechat\controller\Base;

class Auth extends Base
{
    public function init2()
    {
        $params = [
            'stock_id' => '1264450000000081',
            'out_request_no' => uniqid(),
            'sign' => $this->wechatApp,
            'send_coupon_merchant' => $this->wechatSetting['app_id'],
            'open_id' => $_SESSION['wechat_user']['openid']
        ];
    }
}