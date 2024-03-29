<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Getequipmenthzs
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
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            
            $wheres['job_id'] = $_POST['job_id'];
            $wheres['job_type'] = $_POST['job_type'];
            $equipment_type_ids = Db::table('lbs_service_equipments')->where($wheres)->group('equipment_type_id')->field('equipment_type_id')->select();
            $equipmenthz_datas = [];
            for ($i=0; $i < count($equipment_type_ids); $i++) {
            	$equipmenthz_allcount = Db::table('lbs_service_equipments')->where($wheres)->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])->count();
            	$equipmenthz_count = Db::table('lbs_service_equipments')->where($wheres)->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])->whereNotNull('equipment_area')->whereNotNull('check_datas')->count();
            	$equipment_type = Db::table('lbs_service_equipment_type')->where('id',$equipment_type_ids[$i]['equipment_type_id'])->field('name')->find();
				$equipmenthz_datas[$i]['title'] = $equipment_type['name']."(".$equipmenthz_count."/".$equipmenthz_allcount.")";
				
            	$check_datas = Db::table('lbs_service_equipments')->where($wheres)->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])->whereNotNull('equipment_area')->whereNotNull('check_datas')->order('id', 'asc')->select();
            	if ($check_datas) {
            		for($j=0; $j < count($check_datas); $j++){
	            		$check_data = json_decode($check_datas[$j]['check_datas'],true);
	            		
	            		$equipmenthz_datas[$i]['table_title'][0] = '序号';
	            		$equipmenthz_datas[$i]['content'][$j][0] = sprintf('%02s', $j+1);
                        $equipmenthz_datas[$i]['table_title'][1] = '编号';
	            		$equipmenthz_datas[$i]['content'][$j][1] = $check_datas[$j]['equipment_number'];
	            		$equipmenthz_datas[$i]['table_title'][2] = '区域';
                        $equipmenthz_datas[$i]['content'][$j][2] = $check_datas[$j]['equipment_area'];
                        for ($m=0; $m < count($check_data); $m++) { 
                            $equipmenthz_datas[$i]['table_title'][$m+3] = $check_data[$m]['label'];
                            $equipmenthz_datas[$i]['content'][$j][$m+3] = $check_data[$m]['value'];
                        } 
	            		
            		}
            	}
            }
            if ($equipmenthz_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = $equipmenthz_datas;
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
