<?php
declare (strict_types = 1);

namespace app\customer\controller;
use think\facade\Db;


class Savelastriskstatus
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['id']) || !isset($_POST['status'])){
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24 )) {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $time = date('Y-m-d H:i:s');
        	$data['status'] = $status;
        	$data['update_time'] = $time;
        	$data['update_by'] = $staffid;
            $data['confirm_status'] = 1; //这个状态是用来判断 是用户点击了 已解决的 但是需要技术员确认
            $save_datas = Db::table('lbs_service_risks')->where('id', $id)->update($data);
            if ($save_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = $save_datas;
            }else{
                $result['code'] = 1;
                $result['msg'] = '成功，无数据';
                $result['data'] = null;
            }
        }else{
             $result['code'] = 0;
             $result['msg'] = '登录失效，请重新登陆';
             $result['data'] = null;
        }
        return json($result);
    }
}
