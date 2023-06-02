<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Coupon as CouponModel;
use app\common\model\WechatSetting as WechatSettingModel;
use GuzzleHttp\Exception\ClientException;
use think\Exception;
use think\facade\Db;
use utils\qrCode;
use utils\Wechat;

class Coupon extends AdminBase
{
    public function index()
    {
        $param = $this->request->param();
        $model = new CouponModel();
        if( $param['start_date']!=''&&$param['end_date']!='' ) {
            $model = $model->whereBetweenTime('create_time',$param['start_date'],$param['end_date']);
        }
        $list = $model->order('id desc')->paginate(['query' => $param]);
        return view('',['list'=>$list]);
    }

    /**
     * 添加管理员
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        if( $this->request->isPost() ) {
            $param = $this->request->param();
            //处理表单
            $param['natural_person_limit'] = $param['natural_person_limit'] ?? '0';
            $param['prevent_api_abuse'] = $param['prevent_api_abuse'] ?? '0';
            //注入c_id
            $param['c_id'] = generateUid();
            //批次归属商户号
            $param['belong_merchant'] = '123';
            //获取订单号
            $out_request_no = random(32,false);
            $param['out_request_no'] = $out_request_no;

            //创建微信实例
            $wechat_setting_data = WechatSettingModel::find(1);
            $wechatInstance = (new \utils\Wechat())->createWechatPay(
                $wechat_setting_data['merchantId'],
                $wechat_setting_data['merchantPrivateKeyFile'],
                $wechat_setting_data['merchantCertificateSerial'],
                $wechat_setting_data['platformCertificateFilePath']
            )->getInstance();

            // 启动事务
            Db::startTrans();
            try {
                if($param['stock_type'] == 'NORMAL'){
                    $param['transaction_minimum'] = $param['n_transaction_minimum'];
                }else{
                    $param['transaction_minimum'] = $param['d_transaction_minimum'];
                }
                unset($param['n_transaction_minimum'], $param['d_transaction_minimum']);
                $postData = [
                    'stock_name' => $param['stock_name'],
                    'belong_merchant' => $wechat_setting_data['merchantId'],
                    'goods_name' => $param['goods_name'],
                    'stock_type' => $param['stock_type'],
                    'coupon_use_rule' => [
                        'coupon_available_time' => [
                            'available_begin_time' => date('c',strtotime($param['available_begin_time'])),
                            'available_end_time' => date('c',strtotime($param['available_end_time'])),
                            'available_day_after_receive' => (int)$param['available_day_after_receive']
                        ],
                        'fixed_normal_coupon' => [
                            'discount_amount' => (int)$param['discount_amount'],
                            'transaction_minimum' => (int)$param['transaction_minimum']
                        ],
                        'discount_coupon' => [
                            'discount_percent' => (int)$param['discount_percent'],
                            'transaction_minimum' => (int)$param['transaction_minimum']
                        ],
                        'use_method' => 'OFF_LINE'
                    ],
                    'stock_send_rule' => [
                        'max_coupons' => (int)$param['max_coupons'],
                        'max_coupons_per_user' => (int)$param['max_coupons_per_user'],
                        'prevent_api_abuse' => (bool)$param['prevent_api_abuse']
                    ],
                    'out_request_no' => $out_request_no,
                    'display_pattern_info' => [
                        'description' => $param['description']
                    ],
                    'coupon_code_mode' => 'WECHATPAY_MODE'
                ];
                if($param['stock_type'] == 'NORMAL'){
                    unset($postData['coupon_use_rule']['discount_coupon']);
                }else{
                    unset($postData['coupon_use_rule']['fixed_normal_coupon']);
                }
                $resp = $wechatInstance->chain('v3/marketing/busifavor/stocks')->post([
                    'json' => $postData
                    ]);
                $respBody = json_decode($resp->getBody(), true);
                $stock_id = $respBody['stock_id'];
                if(empty($stock_id)){
                    throw new Exception('微信返回创建失败');
                }
                $param['stock_id'] = $stock_id;
                $insert_id = CouponModel::insertGetId($param);
                if($insert_id) {
                    xn_add_admin_log('添加优惠券');
                } else {
                    throw new Exception('添加失败,数据无法写入');
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e){
                // 回滚事务
                Db::rollback();
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $this->error('添加失败:'.$e->getResponse()->getBody());
                }else{
                    $this->error('添加失败:'.$e->getMessage());
                }
            }
            $this->success('添加成功');
        }
        return view('form',['coupon_data' => []]);
    }

    /**
     * 编辑
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        if( $this->request->isPost() ) {
            $param = $this->request->param();

            //创建微信实例
            $wechat_setting_data = WechatSettingModel::find(1);
            $wechatInstance = (new \utils\Wechat())->createWechatPay(
                $wechat_setting_data['merchantId'],
                $wechat_setting_data['merchantPrivateKeyFile'],
                $wechat_setting_data['merchantCertificateSerial'],
                $wechat_setting_data['platformCertificateFilePath']
            )->getInstance();


            // 启动事务
            Db::startTrans();
            try {
                unset($param['n_transaction_minimum'], $param['d_transaction_minimum']);
                $postData = [
                    'goods_name' => $param['goods_name'],
                    'stock_send_rule' => [
                        'prevent_api_abuse' => (bool)$param['prevent_api_abuse']
                    ],
                    'display_pattern_info' => [
                        'description' => $param['description']
                    ],
                    'out_request_no' => random(32,false)
                ];
                $resp = $wechatInstance->chain("v3/marketing/busifavor/stocks/{$param['stock_id']}")->patch([
                    'json' => $postData
                ]);
                $statusCode = $resp->getStatusCode();
                if($statusCode != 200){
                    throw new Exception('微信返回修改失败');
                }
                if(CouponModel::update($param)) {
                    xn_add_admin_log('修改优惠券');
                } else {
                    throw new Exception('添加失败,数据无法写入');
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e){
                // 回滚事务
                Db::rollback();
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $this->error('修改失败:',null,$e->getResponse()->getBody());
                }else{
                    $this->error('修改失败:'.$e->getMessage());
                }
            }
            $this->success('修改成功');
        }
        $id = $this->request->get('id');
        $coupon_data = CouponModel::find(['id' => $id]);
        return view('form',['coupon_data' => $coupon_data, 'edit' => 1]);
    }

   public function url(){
       $id = $this->request->get('id');
       $url = "https://test.codepoch.com/wechat/auth?id=".$id;
       return view('url',['url' => $url]);
   }

    public function qrCode(){
        $id = $this->request->get('id');
        $url = "https://test.codepoch.com/wechat/auth?id=".$id;
        $qrCode = new qrCode();
        $resp = $qrCode->qrcode64($url, 'L', 6);
        return view('qrcode',['qrcode' => $resp]);
    }

}
