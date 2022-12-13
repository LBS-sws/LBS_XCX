<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Searchequipments
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) ){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) ){
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
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            
            $wheres['job_id'] = $_POST['job_id'];
            $wheres['job_type'] = $_POST['job_type'];
            if ($_POST['equipment']) {
                $wheres['equipment_type_id'] = $_POST['equipment'];
            }
            if ($_POST['area']) {
                $wheres['equipment_area'] = $_POST['area'];
            }
            
            //所有设备
            $service_data['equipments'] = Db::table('lbs_service_equipments')->where($wheres)->order('id', 'asc')->field('equipment_name as label,id as value')->select();
            if ($service_data) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = $service_data;
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
