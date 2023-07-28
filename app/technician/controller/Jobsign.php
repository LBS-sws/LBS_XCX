<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\common\controller\Base;
use think\facade\Request;
use think\facade\Db;
use think\facade\Session;

class Jobsign
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和工作单等';
        $result['data'] = null;

        $token = request()->header('token');

        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['jobid']) || !isset($_POST['jobtype']) || !isset($_POST['signdate']) || !isset($_POST['starttime'])){
            return json($result);
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['jobid']) || empty($_POST['jobtype']) || empty($_POST['signdate']) || empty($_POST['starttime'])){
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $jobid = $_POST['jobid'];
        $jobtype = $_POST['jobtype'];
        $signdate = date('Y-m-d');
        $starttime = date('H:i:s');//$_POST['starttime'];

        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;

        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            if($jobtype==1){
                $job_datas = Db::table('joborder')->where('JobID', $jobid)->update(['FinishDate' => $signdate , 'StartTime' => $starttime]);
            }elseif ($jobtype==2) {
               $job_datas = Db::table('followuporder')->where('FollowUpID', $jobid)->update(['StartTime' => $starttime]);
            }
            if ($job_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = $job_datas;
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
