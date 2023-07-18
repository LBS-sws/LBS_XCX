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
            if($status==2){
                $last_risk = Db::table('lbs_service_risks')->where('id', $id)->find();
                $add_data = $last_risk;
                $add_data['id'] = '';
                $add_data['job_id'] = $_POST['job_id'] ;
                $add_data['job_type'] = $_POST['job_type'] ;
                $add_data['creat_time'] = $time;
                $add_data['create_by'] = $staffid;
                $add_data['follow_times'] = $add_data['follow_times']+1;
                $add_risk_id = Db::table('lbs_service_risks')->insertGetId($add_data);
                $data['follow_id'] = $add_risk_id;
                $save_datas = Db::table('lbs_service_risks')->where('id', $id)->update($data);
            }else{
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
