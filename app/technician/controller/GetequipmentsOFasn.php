<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use COM;
use think\facade\Db;
use think\facade\Request;


class GetequipmentsOFasn
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['city']) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['service_type'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['city']) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['service_type'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $city = $_POST['city'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $wheres['job_id'] = $job_id;
            $wheres['job_type'] = $job_type;
            //所有设备
            $service_data['equipments'] = Db::table('lbs_service_equipments')->where($wheres)->order('id', 'asc')->field('equipment_name as label,id as value,check_datas')->select();
            $equipment_inherit = Db::table('lbs_service_equipment_inherits')->where($wheres)->find();
            if (count($service_data['equipments'])==0) {
                if ($job_type == 1) {

                    $job = Db::table('joborder')->where('JobID',$job_id)->field('ContractID,ServiceType')->find();
                    $last_w['ContractID'] = $job['ContractID'];
                    $last_w['ServiceType'] = $job['ServiceType'];
                    if (empty($equipment_inherit)) {
                        $last_w['Status'] = 3 ;
                        $last_job =  Db::table('joborder')->where($last_w)->order('JobDate', 'desc')->field('JobID')->find();
                        $last_e['e.job_id'] = $last_job['JobID'];
                        $last_e['e.job_type'] = 1;
                    }
                    
                }
                // else if ($job_type == 2) {
                //     $job = Db::table('followuporder')->where('FollowUpID',$job_id)->field('ContractID,SType')->find();
                //     $last_w['ContractID'] = $job['ContractID'];
                //     $last_w['SType'] = $job['SType'];
                //     if (empty($equipment_inherit)) {
                //         $last_w['Status'] = 3 ;
                //         $last_job =  Db::table('followuporder')->where($last_w)->order('JobDate', 'desc')->field('FollowUpID')->find();
                //         $last_e['e.job_id'] = $last_job['FollowUpID'];
                //         $last_e['e.job_type'] = 2;
                //     }
                // }
                 if (empty($equipment_inherit) && !empty($last_job)) {      
                        $last_equipments = Db::table('lbs_service_equipments')->alias('e')->join('lbs_service_equipment_type t','e.equipment_type_id=t.id','right')->field('e.*,t.name as equipment_type_name')->where($last_e)->order('id', 'asc')->select();
                        if (count($last_equipments)>0) {
                            for ($i=0; $i < count($last_equipments); $i++) { 
                                $data['job_id'] = $job_id;
                                $data['job_type'] = $job_type;
                                $data['equipment_type_id'] = $last_equipments[$i]['equipment_type_id'];
                                $data['equipment_name'] = $last_equipments[$i]['equipment_type_name'];
                                $data['equipment_area'] = $last_equipments[$i]['equipment_area'];
                                $data['equipment_number'] = $last_equipments[$i]['equipment_number'];
                                $data['number'] = $last_equipments[$i]['number'];
                                $data['creat_time'] = date('Y-m-d H:i:s', time());
                                $save_datas = Db::table('lbs_service_equipments')->insert($data);
                            }
                           
                        }
                        $inherit['job_id'] = $job_id;
                        $inherit['job_type'] = $job_type;
                        $inherit['inherit_job_id'] = $last_e['e.job_id'];
                        $inherit['creat_time'] = date('Y-m-d H:i:s', time());
                        Db::table('lbs_service_equipment_inherits')->insert($inherit);
                    }
            }
            if($_POST['ct']==1){
                $city = 'CN';
                $eqs_file = 'equipment_name as label,id as value,check_datas,equipment_number as eq_number,number';
            }else{
                $eqs_file = 'equipment_name as label,id as value,check_datas,equipment_number as eq_number,number';
            }
             $service_data['equipments'] = Db::table('lbs_service_equipments')->where($wheres)->order('equipment_number', 'desc')->order('number', 'asc')->field($eqs_file)->select();
            //使用区域
            $usearea_select1 = array(array("label"=>"全部区域","value"=>""));
            $usearea_select2 =  Db::table('lbs_service_equipments')->Distinct(true)->where($wheres)->where('equipment_area','not null')->field('equipment_area as label,equipment_area as value')->select()->toArray();
            $service_data['usearea_select'] = $usearea_select2?array_merge($usearea_select1,$usearea_select2):'';
            //所有设备
            $equipment_select1 =  array(array("label"=>"全部设备","value"=>""));
            $equipment_select2 =  Db::table('lbs_service_equipments')->Distinct(true)->where($wheres)->field('equipment_type_id as value,equipment_name as label')->select()->toArray();
            $service_data['equipment_select'] = $equipment_select2?array_merge($equipment_select1,$equipment_select2):'';
            //新增所有设备
            $allow_eqs = Db::table('lbs_service_serviceequipments')->where('city',$city)->where('service_type',$_POST['service_type'])->find();
            $service_data['equipment_add_lists'] = $allow_eqs ? Db::table('lbs_service_equipment_type')->where('city',$city)->whereIn('id',$allow_eqs['equipment_ids'])->field('name as label,id as value')->select():'';
            if ($service_data) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
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
