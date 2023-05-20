<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Coupon as CouponModel;
use think\facade\Db;

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
            //注入c_id
            $param['c_id'] = generateUid();
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
