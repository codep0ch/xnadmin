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
            'send_coupon_merchant' => $this->wechatSetting['merchantId'],
            'open_id' => session('wechat_user')['id']
        ];
        ksort($params);
        $stringA = http_build_query($params);
        $stringSignTemp = $stringA."&key=RL6VHZ1DG78N5Y4X1S9FP6QK0U345790";
        $sign = md5($stringSignTemp);
        $params['sign'] = strtoupper($sign);
        //RL6VHZ1DG78N5Y4X1S9FP6QK0U345790
        $url = 'https://action.weixin.qq.com/busifavor/getcouponinfo?'.http_build_query($params).'aaaaaaaaaaaaaaaaaaaaa#wechat_redirect';
        $this->redirect($url);
    }
}