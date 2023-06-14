<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Addequipments
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['add_ids'])  || !isset($_POST['add_number'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['add_ids']) || empty($_POST['add_number'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        $add_ids = $_POST['add_ids'];
        $add_number = $_POST['add_number'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            
            $data['job_id'] = $_POST['job_id'];
            $data['job_type'] = $_POST['job_type'];
    
            $type_ids = explode(',',$add_ids);
            for($n=0;$n<$add_number;$n++){
                for ($i=0; $i < count($type_ids); $i++) { 
                    //查询设备
                    $e_type = Db::table('lbs_service_equipment_type')->where('id',$type_ids[$i])->find();
                    $data['equipment_type_id'] = $e_type['id'];
                    $data['equipment_name'] = $e_type['name'];
                    $data['creat_time'] = date('Y-m-d H:i:s', time());
                    $save_datas = Db::table('lbs_service_equipments')->insert($data);
                }
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
