<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;


class CheckLogin
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '认证过期，请重新登录';
        $result['data'] = null;
        $token = request()->param('token','');
        $staffid = request()->param('staffid','');
        if(empty($token) || $token =='' || empty($staffid)){
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
             $result['code'] = 1;
             $result['msg'] = 'ok';
             $result['data'] = [];
            
        }else{
             $result['code'] = 0;
             $result['msg'] = '认证过期';
             $result['data'] = null;
        }
        return json($result);
    }
}
