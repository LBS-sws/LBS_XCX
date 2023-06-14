<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Delequipments
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['del_ids']) ){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['del_ids'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        $del_ids = $_POST['del_ids'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
        
            $data['job_id'] = $_POST['job_id'];
            $data['job_type'] = $_POST['job_type'];
            $del_ids = explode(',',$del_ids);
            for ($i=0; $i < count($del_ids); $i++) { 

                $e = Db::table('lbs_service_equipments')->where('id',$del_ids[$i])->find();
                $data_up['eq_type_id'] = $e['equipment_type_id'];
                $data_up['equipment_number'] = $e['equipment_number'];
				$up_eqn = Db::table('lbs_service_equipment_numbers')->where($data_up)->update(['status' => '0']);

                
                $save_datas = Db::table('lbs_service_equipments')->delete($del_ids[$i]);
            }
            if ($save_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '删除成功';
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
