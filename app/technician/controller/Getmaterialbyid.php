<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Getmaterialbyid
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['city']) || !isset($_POST['service_type'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['city']) || empty($_POST['service_type'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        $id = $_POST['id']?$_POST['id']:0;
        $city = $_POST['city'];
        $service_type = $_POST['service_type'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $wheres['id'] = $job_id;
            $wheres['job_id'] = $job_id;
            $wheres['job_type'] = $job_type;
            $service_data['material'] = [];
            if($id>0){
                $service_data['material'] = Db::table('lbs_service_materials')->where('id',$id)->find();
            }
            $service_data['material_lists'] =  Db::table('lbs_service_material_lists')->alias('m')->join('lbs_service_material_classifys c','c.id=m.classify_id')->where('city',$city)->field('m.name as label,m.name as value,m.registration_no,m.active_ingredient,m.ratio')->select();
            $material_targets=  Db::table('lbs_service_material_target_lists')->where('city',$city)->where('service_type',$service_type)->field('targets')->find();
            $material_targets = $material_targets?explode(',',$material_targets['targets']):null;
            $service_data['material_targets'] = [];
            if($material_targets){
                for ($i=0; $i < count($material_targets); $i++) { 
                    $service_data['material_targets'][$i]['label'] =$material_targets[$i] ;
                    $service_data['material_targets'][$i]['value'] =$material_targets[$i] ;
                }
            }
            $service_data['material_usemodes'] =  Db::table('lbs_service_material_use_modes')->where('city',$city)->field('use_mode as label,use_mode as value')->select();
            $material_useareas =  Db::table('lbs_service_use_areas')->where('city',$city)->where('area_type','material')->field('use_area')->find();
            $material_useareas = $material_useareas?explode(',',$material_useareas['use_area']):null;
            $service_data['material_useareas'] = [];
            if($material_useareas){
                for ($i=0; $i < count($material_useareas); $i++) { 
                    $service_data['material_useareas'][$i]['label'] =$material_useareas[$i] ;
                    $service_data['material_useareas'][$i]['value'] =$material_useareas[$i] ;
                }
            }
            //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $service_data;
           
        }else{
             $result['code'] = 0;
             $result['msg'] = '登录失效，请重新登陆';
             $result['data'] = null;
        }
        return json($result);
    }
}
