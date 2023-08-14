<?php
namespace app\wechat\controller;
use app\common\model\Coupon;
use app\common\model\CouponSendLog;
use app\wechat\controller\Base;

class Auth extends Base
{
    public function init2()
    {
        $id = app()->request->get('id');
        $couponData = Coupon::find($id);
        $params = [
            'stock_id' => $couponData['stock_id'],
            'out_request_no' => uniqid(),
            'send_coupon_merchant' => $this->wechatSetting['merchantId'],
            'open_id' => session('wechat_user')['id']
        ];
        CouponSendLog::insertGetId([
            'couponid' => $id,
            'out_request_no' => $params['out_request_no'],
            'open_id' => $params['open_id'],
            'create_time' => time()
        ]);
        $params['sign'] = $this->getSignV2($params, 'RL6VHZ1DG78N5Y4X1S9FP6QK0U345790');
        $url = 'https://action.weixin.qq.com/busifavor/getcouponinfo?'.http_build_query($params).'#wechat_redirect';
        $this->redirect($url);
    }
}