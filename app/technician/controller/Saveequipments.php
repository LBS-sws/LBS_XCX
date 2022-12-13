<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Saveequipments
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['equipments'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['equipments'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $equipments = json_decode($_POST['equipments'],true);
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            for ($i=0; $i < count($equipments); $i++) { 
                $data['equipment_name'] = $equipments[$i]['equipment_name'];
                $data['equipment_area'] =$equipments[$i]['equipment_area'];
                $data['check_datas'] = is_string($equipments[$i]['check_datas']) ? $equipments[$i]['check_datas'] : json_encode($equipments[$i]['check_datas'],JSON_UNESCAPED_UNICODE);
                $data['site_photos'] = is_string($equipments[$i]['site_photos']) ? $equipments[$i]['site_photos'] : json_encode($equipments[$i]['site_photos'],JSON_UNESCAPED_UNICODE);
                $data['check_handle'] = isset($equipments[$i]['check_handle']) ? implode(',',$equipments[$i]['check_handle']) : null;
                $data['more_info'] = $equipments[$i]['more_info'];
                $save_datas = Db::table('lbs_service_equipments')->where('id', $equipments[$i]['id'])->update($data);
            }
            if ($save_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = $save_datas;
            }else{
                $result['code'] = 1;
                $result['msg'] = '保存成功';
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
