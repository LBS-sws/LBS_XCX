<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Jobstart
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、工作单编号和工作单类型等';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['jobid']) || !isset($_POST['jobtype']) || !isset($_POST['city'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['jobid']) || empty($_POST['jobtype']) || empty($_POST['city'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $jobid = $_POST['jobid'];
        $jobtype = $_POST['jobtype'];
        $city = $_POST['city'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            // $job_wheres['j.Staff01'] = $staffid;
            if($jobtype==1){
                $job_wheres['j.JobID'] = $jobid;
                $job_datas = Db::table('joborder')->alias('j')->join('staff u','j.Staff01=u.StaffID')->where($job_wheres)->field('j.Staff01,j.CustomerID,j.CustomerName,j.ContactName,j.Mobile,j.Tel,j.Addr,j.lat,j.lng,j.FinishTime,j.Status,j.ServiceType as service_type,u.StaffName,j.JobDate')->find();
            }elseif ($jobtype==2) {
                $job_wheres['j.FollowUpID'] = $jobid;
                $job_datas = Db::table('followuporder')->alias('j')->join('staff u','j.Staff01=u.StaffID')->where($job_wheres)->field('j.Staff01,j.CustomerID,j.CustomerName,j.ContactName,j.Mobile,j.Tel,j.Addr,j.lat,j.lng,j.FinishTime,j.Status,j.SType as service_type,u.StaffName,j.JobDate')->find();
            }
            
            if ($job_datas) {
                //查询服务次数
                $job_datas['service_number'] = Db::table('joborder')->where('ServiceType',$job_datas['service_type'])->where('CustomerID',$job_datas['CustomerID'])->where('Status',3)->where('JobDate','<=',$job_datas['JobDate'])->count();
                //查询服务板块
                $service_sections = Db::table('lbs_service_reportsections')->where('city',$city)->where('service_type',$job_datas['service_type'])->find();
                //查询服务报告填写情况
                $table_sections = ["lbs_service_briefings","lbs_service_materials","lbs_service_equipments","lbs_service_risks","lbs_service_photos"];
                $is_where['job_id'] = $jobid;
                $is_where['job_type'] = $jobtype;
                $job_datas['table_sections'] = [];
                if($service_sections){
                    $job_datas['service_sections'] = explode(',',$service_sections['section_ids']);
                    for($i=0;$i<count($job_datas['service_sections']);$i++){
                        $count = Db::table($table_sections[$job_datas['service_sections'][$i]-1])->where($is_where)->count();
                        array_push($job_datas['table_sections'], $count>0?1:0);
                    }
                }else{
                    $job_datas['service_sections'] = '';
                    for($i=0;$i<count($table_sections);$i++){
                        $count = Db::table($table_sections[$i])->where($is_where)->count();
                        array_push($job_datas['table_sections'], $count>0?1:0);
                    }
                }
                //查询签名
                $job_datas['autograph'] = 0;
                $autograph = Db::table('lbs_report_autograph')->where($is_where)->find();
                if ($autograph) {
                    if ($autograph['customer_signature']!='' && $autograph['customer_signature']!='undefined') {
                        $job_datas['autograph'] = 1;
                    }
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
