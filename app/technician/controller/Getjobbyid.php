<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Getjobbyid
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、工作单编号和工作单类型等';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['jobid']) || !isset($_POST['jobtype'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['jobid']) || empty($_POST['jobtype'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $jobid = $_POST['jobid'];
        $jobtype = $_POST['jobtype'];
        
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $job_wheres['j.Staff01'] = $staffid;
            if($jobtype==1){
                $job_wheres['j.JobID'] = $jobid;
                $job_datas = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->where($job_wheres)->field('j.*,s.ServiceName,u.StaffName')->find();
            }elseif ($jobtype==2) {
                $job_wheres['j.FollowUpID'] = $jobid;
                $job_datas = Db::table('followuporder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->where($job_wheres)->field('j.*,s.ServiceName,u.StaffName')->find();
            }
            
            if ($job_datas) {
                //数据添加
                $job_datas['Watchdog'] = '';
                $job_datas['StaffName01'] = '';
                $job_datas['StaffName02'] = '';
                $job_datas['button'] = '';
                if ($job_datas['Staff02']) {
                    $StaffName01 = Db::table('staff')->where('StaffID',$job_datas['Staff02'])->field('StaffName')->find();
                    $job_datas['StaffName01'] = $StaffName01['StaffName'];
                }
                 if ($job_datas['Staff03']) {
                    $StaffName02 = Db::table('staff')->where('StaffID',$job_datas['Staff03'])->field('StaffName')->find();
                    $job_datas['StaffName02'] = $StaffName02['StaffName'];
                }

                
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
