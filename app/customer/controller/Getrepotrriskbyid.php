<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Getrepotrriskbyid
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['id']) ){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['id'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $id = $_POST['id'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $wheres['id'] = $id;
            $risk_data = Db::table('lbs_service_risks')->where($wheres)->find();
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $risk_data;
           
        }else{
             $result['code'] = 0;
             $result['msg'] = '登录失效，请重新登陆';
             $result['data'] = null;
        }
        return json($result);
    }
}
