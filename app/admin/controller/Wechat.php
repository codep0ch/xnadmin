<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\WechatSetting as WechatSettingModel;
use think\facade\Db;

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
            $param = $this->request->param();
            $result = WechatSettingModel::where('id',1)->update($param);
            if( $result ) {
                xn_add_admin_log('修改微信配置');
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $wechat_setting_data = WechatSettingModel::find(1);
        return view('', ['wechat_setting_data'=>$wechat_setting_data]);
    }
}
