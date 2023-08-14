<?php
namespace app\miniprogram\controller;
use app\common\model\CouponSendLog;
use app\common\model\Coupon as CouponModel;
use app\common\model\WechatSetting as WechatSettingModel;
use app\miniprogram\controller\Base;
use thans\jwt\facade\JWTAuth;
class Coupon extends Base
{
    public function init()
    {
        return commonApiReturn(200,[],'success');
    }
    public function getCouponInfo()
    {
        $coupon_code = $this->request->post('code');
        $couponLog = CouponSendLog::where('coupon_code', $coupon_code)->find();
        $couponInfo = CouponModel::find($couponLog['couponid']);
        $couponInfo['discount_amount'] = round($couponInfo['discount_amount']/100, 2);
        $couponInfo['transaction_minimum'] = round($couponInfo['transaction_minimum']/100, 2);
        if($couponInfo['stock_type'] == 'NORMAL'){
            $couponInfo['stock_type'] = '满减券';
        }else{
            $couponInfo['stock_type'] = '折扣券';
        }

        try {
            //创建微信实例
            $wechat_setting_data = WechatSettingModel::find(1);
            $wechatInstance = (new \utils\Wechat())->createWechatPay(
                $wechat_setting_data['merchantId'],
                $wechat_setting_data['merchantPrivateKeyFile'],
                $wechat_setting_data['merchantCertificateSerial'],
                $wechat_setting_data['platformCertificateFilePath']
            )->getInstance();

            $resp = $wechatInstance->v3->marketing->busifavor->users->_openid_->coupons->_coupon_code_->appids->_appid_->get([
                // 变量名 => 变量值
                'openid' => $couponLog['open_id'],
                'coupon_code' => $coupon_code,
                'appid' => $wechat_setting_data['wechatAppId']
            ]);


            //$resp = $wechatInstance->chain("v3/marketing/busifavor/users/{$couponLog['open_id']}/coupons/{$coupon_code}/appids/{$wechat_setting_data['wechatAppId']}")->get();
            return commonApiReturn(200,json_encode($resp),'查询成功'.$resp->getStatusCode());
        } catch (\Exception $e) {
            return commonApiReturn(200,$e->getMessage(),'查询失败');
        }
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
        $couponLog = CouponSendLog::where('coupon_code', $coupon_code)->find();
        $couponInfo = CouponModel::find($couponLog['couponid']);
        if($couponInfo['status'] != 1){
            return commonApiReturn(401,[],'券禁止核销');
        }
        try {
            $resp = $wechatInstance->chain("v3/marketing/busifavor/coupons/use")->post([
                'json' => [
                    'coupon_code' => $coupon_code,
                    'stock_id' => $couponInfo['stock_id'],
                    'appid' => $wechat_setting_data['wechatAppId'],
                    'use_time' => date('c',time()),
                    'use_request_no' => random(32,false)
                ]
            ]);
            $statusCode = $resp->getStatusCode();
            if($statusCode != 200){
                return commonApiReturn(400,[],'微信返回核销失败');
            }else{
                $couponLog->is_consume = 1;
                $couponLog->save();
                return commonApiReturn(200,[],'核销成功');
            }
        }catch (\Exception $e){
            return commonApiReturn(400,$e->getMessage(),'未知错误');
        }

    }
}