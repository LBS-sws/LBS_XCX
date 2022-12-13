<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;


class Searchjobs
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入客户ID';
        $result['data'] = null;
        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['customerid']) || !isset($_POST['daterange'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['customerid']) || empty($_POST['daterange'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $customerid = $_POST['customerid'];
        $daterange = $_POST['daterange'];
        $store = $_POST['store'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            //判断分店还是总店
            $mainstore = $_POST['mainstore']?$_POST['mainstore']:0;
            $datas = [] ;
            $customer = Db::name('customercompany')->where('CustomerID',$customerid)->find();
            
            if($mainstore==1 && $store==''){
                
                //查询集团下的所有店
                $customer_group = Db::name('customercompany')->where('GroupID',$customer['GroupID'])->field('CustomerID,NameZH,City')->select();
                
                for ($i=0; $i < count($customer_group); $i++) { 
                    //获取城市
                    $launch_date = Db::name('enums')->alias('e')->join('officecity o ','o.Office=e.EnumID')->join('lbs_service_city_launch_date l ','e.Text=l.city')->where('o.City', $customer_group[$i]['City'])->where('e.EnumType', 8)->field('l.launch_date')->find();
                    $job_wheres['j.CustomerID'] = $customer_group[$i]['CustomerID'];
                    $job_wheres['j.Status'] = 3;
                    if($launch_date){
                         //服务单
                        $job_datas = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where($job_wheres)->whereBetween('j.JobDate',$daterange)->whereTime('j.JobDate','>=',$launch_date['launch_date'])->order('j.JobDate','desc')->field('j.JobID,j.CustomerName,j.JobDate,j.StartTime,j.FinishTime,s.ServiceName,j.StartTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,j.FirstJob')->select()->toArray();
                        //跟进单
                        $follow_datas = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where($job_wheres)->whereBetween('j.JobDate',$daterange)->whereTime('j.JobDate','>=',$launch_date['launch_date'])->order('j.JobDate','desc')->field('j.FollowUpID as JobID,j.CustomerName,j.JobDate,j.StartTime,j.FinishTime,s.ServiceName,j.StartTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03')->select()->toArray();
                    }else{
                         //服务单
                        $job_datas = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where($job_wheres)->whereBetween('j.JobDate',$daterange)->order('j.JobDate','desc')->field('j.JobID,j.CustomerName,j.JobDate,j.StartTime,j.FinishTime,s.ServiceName,j.StartTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,j.FirstJob')->select()->toArray();
                        //跟进单
                        $follow_datas = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where($job_wheres)->whereBetween('j.JobDate',$daterange)->order('j.JobDate','desc')->field('j.FollowUpID as JobID,j.CustomerName,j.JobDate,j.StartTime,j.FinishTime,s.ServiceName,j.StartTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03')->select()->toArray();
                    }
                    
                   
                    if (count($job_datas)>0) {
                        foreach ($job_datas as $key => $value) {
                            $value['type'] = 1;
                            if($value['FirstJob']==1){
                                $value['task_type'] = "首次服务";
                            }else{
                                $value['task_type'] = "常规服务";
                            }
                            array_push($datas,$value);
                        }
                        
                    }
                    if (count($follow_datas)>0) {
                        foreach ($follow_datas as $key => $value) {
                            $value['type'] = 2;
                            $value['task_type'] = "跟进服务";
                            array_push($datas,$value);
                        }
                    }
                }
            }else{
                //获取城市
                $launch_date = Db::name('enums')->alias('e')->join('officecity o ','o.Office=e.EnumID')->join('lbs_service_city_launch_date l ','e.Text=l.city')->where('o.City', $customer['City'])->where('e.EnumType', 8)->field('l.launch_date')->find();
                $job_wheres['j.CustomerID'] = $store==''?$customerid:$store;
                $job_wheres['j.Status'] = 3;
                if($launch_date){
                    //服务单
                    $job_datas = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where($job_wheres)->whereTime('j.JobDate','>=',$launch_date['launch_date'])->whereBetween('j.JobDate',$daterange)->order('j.JobDate','desc')->field('j.JobID,j.CustomerName,j.JobDate,j.StartTime,j.FinishTime,s.ServiceName,j.StartTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,j.FirstJob')->select()->toArray();
                    //跟进单
                    $follow_datas = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where($job_wheres)->whereTime('j.JobDate','>=',$launch_date['launch_date'])->whereBetween('j.JobDate',$daterange)->order('j.JobDate','desc')->field('j.FollowUpID as JobID,j.CustomerName,j.JobDate,j.StartTime,j.FinishTime,s.ServiceName,j.StartTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03')->select()->toArray();
                }else{
                    //服务单
                    $job_datas = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where($job_wheres)->whereBetween('j.JobDate',$daterange)->order('j.JobDate','desc')->field('j.JobID,j.CustomerName,j.JobDate,j.StartTime,j.FinishTime,s.ServiceName,j.StartTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,j.FirstJob')->select()->toArray();
                    //跟进单
                    $follow_datas = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where($job_wheres)->whereBetween('j.JobDate',$daterange)->order('j.JobDate','desc')->field('j.FollowUpID as JobID,j.CustomerName,j.JobDate,j.StartTime,j.FinishTime,s.ServiceName,j.StartTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03')->select()->toArray();
                }
                
                
                if (count($job_datas)>0) {
                     foreach ($job_datas as $key => $value) {
                            $value['type'] = 1;
                            if($value['FirstJob']==1){
                                $value['task_type'] = "首次服务";
                            }else{
                                $value['task_type'] = "常规服务";
                            }
                            array_push($datas,$value);
                        }
                }
                if (count($follow_datas)>0) {
                    foreach ($follow_datas as $key => $value) {
                        $value['type'] = 2;
                        $value['task_type'] = "跟进服务";
                        array_push($datas,$value);
                    }
                }
            }
            
            if ($datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = $datas;
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
