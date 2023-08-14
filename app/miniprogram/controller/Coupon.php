<?php
namespace app\miniprogram\controller;
use app\common\model\CouponSendLog;
use app\common\model\WechatSetting as WechatSettingModel;
use app\miniprogram\controller\Base;
use thans\jwt\facade\JWTAuth;
class Coupon extends Base
{
    public function init()
    {
        return commonApiReturn(200,[],'success');
    }

    public function doConsume()
    {
        $coupon_code = $this->request->post('code');
        //创建微信实例
        $wechat_setting_data = WechatSettingModel::find(1);
        $wechatInstance = (new \utils\Wechat())->createWechatPay(
            $wechat_setting_data['merchantId'],
            $wechat_setting_data['merchantPrivateKeyFile'],
            $wechat_setting_data['merchantCertificateSerial'],
            $wechat_setting_data['platformCertificateFilePath']
        )->getInstance();
        $couponLog = CouponSendLog::create()->where(['code' => $coupon_code])->find();
        if(\app\common\model\Coupon::create()->find($couponLog['couponid'])['status'] != 1){
            return commonApiReturn(400,[],'券禁止核销');
        }
        try {
            $resp = $wechatInstance->chain("v3/marketing/busifavor/coupons/use")->post([
                'json' => [
                    'coupon_code' => $coupon_code,
                    'appid' => $wechat_setting_data['wechatAppId'],
                    'use_time' => date('c',time()),
                    'use_request_no' => random(32,false)
                ]
            ]);
            $statusCode = $resp->getStatusCode();
            if($statusCode != 200){
                return commonApiReturn(400,[],'微信返回核销失败');
            }else{
                return commonApiReturn(200,[],'核销成功');
            }
        }catch (\Exception $e){
            return commonApiReturn(400,$e->getMessage(),'未知错误');
        }

    }
}