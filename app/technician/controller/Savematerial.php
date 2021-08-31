<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Savematerial
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['material_name']) || !isset($_POST['material_active_ingredient']) || !isset($_POST['material_ratio']) || !isset($_POST['targets']) || !isset($_POST['use_mode']) || !isset($_POST['use_area']) || !isset($_POST['dosage']) || !isset($_POST['processing_space']) || !isset($_POST['matters_needing_attention'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['material_name']) || empty($_POST['material_active_ingredient']) || empty($_POST['material_ratio']) || empty($_POST['targets']) || empty($_POST['use_mode']) || empty($_POST['use_area']) || empty($_POST['dosage']) || empty($_POST['processing_space']) || empty($_POST['matters_needing_attention'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];

        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $id = $_POST['id']?$_POST['id']:0;
            $data['job_id'] = $_POST['job_id'];
            $data['job_type'] = $_POST['job_type'];
            $data['material_name'] = $_POST['material_name'];
            $data['material_registration_no'] = $_POST['material_registration_no'];
            $data['material_active_ingredient'] = $_POST['material_active_ingredient'];
            $data['material_ratio'] = $_POST['material_ratio'];
            $data['targets'] = $_POST['targets'];
            $data['use_mode'] = $_POST['use_mode'];
            $data['use_area'] = $_POST['use_area'];
            $data['dosage'] = $_POST['dosage'];
            $data['processing_space'] = $_POST['processing_space'];
            $data['matters_needing_attention'] = $_POST['matters_needing_attention'];
        
            if ($id>0) {
               $save_datas = Db::table('lbs_service_materials')->where('id', $id)->update($data);
            }else{
               $data['creat_time'] = date('Y-m-d H:i:s', time());
               $save_datas = Db::table('lbs_service_materials')->insert($data);
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
