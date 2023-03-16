<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;


class Getphotobyid
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和日期';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['id'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['id'])){
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
            $wheres['id'] = $_POST['id'];
            $wheres['job_id'] = $_POST['job_id'];
            $wheres['job_type'] = $_POST['job_type'];
            $photo_datas = Db::table('lbs_service_photos')->where($wheres)->cache(true,60)->find();   
             //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $photo_datas;
           
        }else{
             $result['code'] = 0;
             $result['msg'] = '登录失效，请重新登陆';
             $result['data'] = null;
        }
        return json($result);
    }
}