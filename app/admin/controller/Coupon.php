<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Coupon as CouponModel;
use app\common\model\WechatSetting as WechatSettingModel;
use GuzzleHttp\Exception\ClientException;
use think\facade\Db;
use utils\Wechat;

class Coupon extends AdminBase
{
    public function index()
    {
        $list = CouponModel::select();
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
            try {
                $postData = [
                    'stock_name' => $param['stock_name'],
                    'belong_merchant' => $wechat_setting_data['merchantId'],
                    'goods_name' => $param['goods_name'],
                    'stock_type' => 'NORMAL',
                    'coupon_use_rule' => [
                        'coupon_available_time' => [
                            'coupon_available_time' => $param['coupon_available_time'],
                            'available_end_time' => $param['available_end_time'],
                            'available_day_after_receive' => $param['available_day_after_receive']
                        ],
                        'use_method' => 'OFF_LINE'
                    ],
                    'stock_send_rule' => [
                        'stock_send_rule' => $param['stock_send_rule'],
                        'max_coupons_per_user' => $param['max_coupons_per_user'],
                        'prevent_api_abuse' => $param['prevent_api_abuse']
                    ],
                    'out_request_no' => $out_request_no,
                    'display_pattern_info' => [
                        'description' => $param['description']
                    ],
                    'coupon_code_mode' => 'WECHATPAY_MODE'
                ];
                $resp = $wechatInstance->chain('v3/marketing/busifavor/stocks')->post([
                    'json' => json_encode($postData)
                    ]);
//                $array = json_decode($resp->getBody(), true);
            }catch (\Exception $e){
                // 进行错误处理
//                echo $e->getMessage(), PHP_EOL;
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $r = $e->getResponse();
                    echo $r->getStatusCode() . ' ' . $r->getReasonPhrase(), PHP_EOL;
//                    echo $r->getBody(), PHP_EOL, PHP_EOL, PHP_EOL;
                }
//                echo $e->getTraceAsString(), PHP_EOL;
            }


            die;
            $insert_id = CouponModel::insertGetId($param);
            if( $insert_id ) {
                xn_add_admin_log('添加优惠券');
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
        $coupon_data = CouponModel::select();
        return view('form',['coupon_data'=>$coupon_data]);
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
            $id = $param['id'];
            $group_ids = $param['group_ids'];

            //更新权限
            if( !empty($group_ids) ) {
                Db::name('auth_group_access')->where("admin_id",$id)->delete();

                foreach( $group_ids as $group_id ) {
                    AuthGroupAccess::create(['admin_id'=>$id,'group_id'=>$group_id]);
                }
            }

            if( $param['password']!='' ){
                $param['password'] = xn_encrypt($param['password']);
            } else {
                unset($param['password']);
            }

            $result = AdminModel::update($param);
            if( $result ) {
                xn_add_admin_log('修改管理员信息');
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $id = $this->request->get('id');
        $assign = [
            'user_data'=> AdminModel::find($id),
            'group_data'=> AuthGroup::select(),
            'user_group_ids'=> Db::name('auth_group_access')->where("admin_id",$id)->column('group_id')
        ];
        return view('form', $assign);
    }

    /**
     * 删除节点
     */
    public function delete()
    {
        $id = intval($this->request->get('id'));
        !($id>1) && $this->error('参数错误');
        AuthGroupAccess::where('admin_id', $id)->delete();
        AdminModel::destroy($id);
        xn_add_admin_log('删除管理员');
        $this->success('删除成功');
    }

    /**
     * 修改资料
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function info()
    {
        if( $this->request->isPost() ) {
            $param = $this->request->param();
            $id = $this->getAdminId();
            if( $param['password']!='' ){
                $param['password'] = xn_encrypt($param['password']);
            } else {
                unset($param['password']);
            }
            $result = AdminModel::where('id',$id)->update($param);
            if( $result ) {
                xn_add_admin_log('修改个人资料');
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $id = $this->getAdminId();
        $user_data = AdminModel::find($id);
        return view('', ['user_data'=>$user_data]);
    }
}
