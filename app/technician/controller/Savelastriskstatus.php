<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
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
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['id']) || empty($_POST['status'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            $id = $_POST['id'];
            $status = $_POST['status'];
        	$data['status'] = $status;
            if ($status==1) {
               $save_datas = Db::table('lbs_service_risks')->where('id', $id)->update($data);
            }else if ($status==2) {
            	$last_risk = Db::table('lbs_service_risks')->where('id', $id)->find();
            	$add_data = $last_risk;
            	$add_data['id'] = '' ;
            	$add_data['job_id'] = $_POST['job_id'] ;
            	$add_data['job_type'] = $_POST['job_type'] ;
            	$add_data['creat_time'] = date('Y-m-d H:i:s', time());
            	$add_data['follow_times'] = $add_data['follow_times']+1;
            	$add_risk_id = Db::table('lbs_service_risks')->insertGetId($add_data);
            	$data['follow_id'] = $add_risk_id;
            	$save_datas = Db::table('lbs_service_risks')->where('id', $id)->update($data);
            }
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
