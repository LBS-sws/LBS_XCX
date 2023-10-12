<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use app\technician\model\AutographV2;
use app\technician\model\JobOrder;
use app\technician\model\FollowupOrder;
use think\facade\Db;
use think\facade\Request;


class Getjobs
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和日期';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['jobdate'])){
            return json($result);
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['jobdate'])){
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $jobdate = $_POST['jobdate'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24 * 30)) {
            $job_wheres['j.JobDate'] = $jobdate;
            //服务单
            $job_datas = JobOrder::alias('j')
                ->field('j.JobID,j.CustomerName,j.Addr,j.JobDate,j.JobTime,j.JobTime2,j.FirstJob,s.ServiceName,j.Status,j.StartTime,j.FirstJob,c.CustomerType')
                ->with(['ReportAutographV2'=>function($query){
                    return $query->field('job_id,customer_grade')->where(['job_type'=>AutographV2::jobType_jobOrder])->find();
                }])
                ->join('service s','j.ServiceType=s.ServiceType')
                ->join('customercompany c','c.CustomerID=j.CustomerID')
                ->where($job_wheres)
                ->where('j.Staff01|j.Staff02|j.Staff03','=',$staffid)
                ->whereIn('j.Status',[-1,2,3])
                ->select();

            //跟进单
            $follow_datas = FollowupOrder::alias('j')
                ->field('j.FollowUpID,j.CustomerName,j.Addr,j.JobDate,j.JobTime,j.JobTime2,s.ServiceName,j.Status,j.StartTime,c.CustomerType')
                ->with(['ReportAutographV2'=>function($query){
                    return $query->field('job_id,customer_grade')->where(['job_type'=>AutographV2::jobType_followOrder])->find();
                }])
                ->join('service s','j.SType=s.ServiceType')
                ->join('customercompany c','c.CustomerID=j.CustomerID')
                ->where($job_wheres)
                ->where('j.Staff01|j.Staff02|j.Staff03','=',$staffid)
                ->whereIn('j.Status',[-1,2,3])
                ->select();
             //获取城市
            $user = Db::name('staff')->where('StaffID', $staffid)->find();
            $launch_date = Db::name('enums')->alias('e')->join('officecity o ','o.Office=e.EnumID')->join('lbs_service_city_launch_date l ','e.Text=l.city')->where('o.City', $user['City'])->where('e.EnumType', 8)->field('l.launch_date')->find();
            if($launch_date){
                if($launch_date['launch_date']>$jobdate){
                    $job_datas = [];
                    $follow_datas = [];
                }
            }
            //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data']['jobs'] = $job_datas;
            $result['data']['follows'] = $follow_datas;
        }else{
             $result['code'] = 0;
             $result['msg'] = '登录失效，请重新登陆';
             $result['data'] = null;
        }
        return json($result);
    }
}
