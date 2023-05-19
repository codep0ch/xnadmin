<?php


namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\AdminLog as AdminLogModel;

class AdminLog extends AdminBase
{
    public function index()
    {
        $param = $this->request->param();
        $model = new AdminLogModel();
        if( $param['start_date']!=''&&$param['end_date']!='' ) {
            $model = $model->whereBetweenTime('create_time',$param['start_date'],$param['end_date']);
        }
        $list = $model->order('id desc')->paginate(['query' => $param]);
        return view('',['list'=>$list]);
    }
}
