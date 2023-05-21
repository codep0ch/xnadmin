<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\WechatSetting as WechatSettingModel;

class Wechat extends AdminBase
{
    /**
     * 修改资料
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setting()
    {
        if( $this->request->isPost() ) {
            $param = $this->request->post();
            unset($param['file']);
            $result = WechatSettingModel::where('id',1)->update($param);
            if( $result ) {
                xn_add_admin_log('修改微信配置');
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $wechat_setting_data = WechatSettingModel::find(1);
        $wechat = new \utils\Wechat();
        $status = $wechat->createWechatPay(
            $wechat_setting_data['merchantId'],
            $wechat_setting_data['merchantPrivateKeyFile'],
            $wechat_setting_data['merchantCertificateSerial'],
            $wechat_setting_data['platformCertificateFilePath']
        )->sign_test();
        return view('', ['wechat_setting_data'=>$wechat_setting_data, 'status' => $status == true ? '1' : '2', 'error' => $wechat->getError()]);
    }

    public function uploader(){
        $file = request()->file('file');
        // 接收示例一
        // 上传到本地服务器   默认上传到runtime/storage目录下面生成以当前日期为子目录
        $fileName = \think\facade\Filesystem::putFile('file', $file,'sha1');
        return json_encode(['file' => '/www/wwwroot/test/runtime/storage/'.$fileName]);
    }
}
