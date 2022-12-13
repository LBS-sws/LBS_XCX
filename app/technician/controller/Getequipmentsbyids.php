<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Getequipmentsbyids
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['eq_ids'])  || !isset($_POST['city'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['eq_ids']) || empty($_POST['city'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        $eq_ids = $_POST['eq_ids'];
        $city = $_POST['city'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            
            $data['e.job_id'] = $_POST['job_id'];
            $data['e.job_type'] = $_POST['job_type'];

            $equipment_datas['equipments'] = Db::table('lbs_service_equipments')->alias('e')->join('lbs_service_equipment_type t','e.equipment_type_id=t.id','right')->where($data)->whereIn('e.id',$eq_ids)->field('e.*,t.type,t.check_targt,t.check_handles')->select()->toArray();
            for ($i=0; $i < count($equipment_datas['equipments']); $i++) { 
                // $equipment_datas['equipments'][$i]['check_datas'] = $equipment_datas['equipments'][$i]['equipment_name'].str_pad(($i+1,4,"0",STR_PAD_LEFT);sprintf('%05s', $m+1);
            	if($equipment_datas['equipments'][$i]['check_datas']==null){
            		$check_datas = [];
            		$targets = explode(',',$equipment_datas['equipments'][$i]['check_targt']);
            		for ($j=0; $j < count($targets); $j++) { 
                        $check_datas[$j]['label'] =  $targets[$j];
                        $check_datas[$j]['value'] =  0;
                    }
            		$equipment_datas['equipments'][$i]['check_datas'] = $check_datas;
            	}else{
                    $equipment_datas['equipments'][$i]['check_datas'] = json_decode($equipment_datas['equipments'][$i]['check_datas'],true);
                }
                if($equipment_datas['equipments'][$i]['check_handles']){
                    $check_handles = [];
                    $check_handle = explode(',',$equipment_datas['equipments'][$i]['check_handles']); 
                    for ($j=0; $j < count($check_handle); $j++) { 
                        $check_handles[$j]['label'] =  $check_handle[$j];
                        $check_handles[$j]['value'] = $check_handle[$j];
                    }
                    $equipment_datas['equipments'][$i]['check_handles'] = $check_handles;
                }
            }
            $equipment_datas['use_areas'] = Db::table('lbs_service_use_areas')->where('city',$city)->where('area_type','equipment')->field('use_area as label,use_area as value')->select();
            if ($equipment_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = $equipment_datas;
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
