<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;


class Gethistoryjobs
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和日期';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['jobdate']) || !isset($_POST['customerid']) || !isset($_POST['servicetype'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['jobdate']) || empty($_POST['customerid']) || empty($_POST['servicetype'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $jobdate = $_POST['jobdate'];
        $customerid = $_POST['customerid'];
        $servicetype = $_POST['servicetype'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            $job_wheres['j.CustomerID'] = $customerid;
            $job_wheres['s.ServiceType'] = $servicetype;
            $job_wheres['j.Status'] = 3;
            //获取城市
            $customer = Db::name('customercompany')->where('CustomerID',$customerid)->find();
            $launch_date = Db::name('enums')->alias('e')->join('officecity o ','o.Office=e.EnumID')->join('lbs_service_city_launch_date l ','e.Text=l.city')->where('o.City', $customer['City'])->where('e.EnumType', 8)->field('l.launch_date')->find();
            if($launch_date){
                //服务单
                $job_datas = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->where($job_wheres)->whereTime('JobDate', 'between', [$launch_date['launch_date'], $jobdate])->field('j.JobID,j.CustomerName,j.Addr,j.JobDate,j.JobTime,j.JobTime2,j.FirstJob,s.ServiceName,j.Status,j.StartTime,j.FirstJob')->order('JobDate desc')->limit(30)->select();
                //跟进单
                // $job_wheres['j.SType'] = $servicetype;
                $follow_datas = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->where($job_wheres)->whereTime('JobDate', 'between', [$launch_date['launch_date'], $jobdate])->field('j.FollowUpID,j.CustomerName,j.Addr,j.JobDate,j.JobTime,j.JobTime2,s.ServiceName,j.Status,j.StartTime')->order('JobDate desc')->limit(30)->select();
            }else{
                //服务单
                $job_datas = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->where($job_wheres)->whereTime('JobDate','<',$jobdate)->field('j.JobID,j.CustomerName,j.Addr,j.JobDate,j.JobTime,j.JobTime2,j.FirstJob,s.ServiceName,j.Status,j.StartTime,j.FirstJob')->order('JobDate desc')->limit(30)->select();
               
                //跟进单
                // $job_wheres['j.SType'] = $servicetype;
                $follow_datas = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->where($job_wheres)->whereTime('JobDate','<',$jobdate)->field('j.FollowUpID,j.CustomerName,j.Addr,j.JobDate,j.JobTime,j.JobTime2,s.ServiceName,j.Status,j.StartTime')->order('JobDate desc')->limit(30)->select();
            }
            
            if ($job_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data']['jobs'] = $job_datas;
                 $result['data']['follows'] = $follow_datas;
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
