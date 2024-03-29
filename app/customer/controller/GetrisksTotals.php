<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;


class GetrisksTotals
{
    public function index()
    {
       
        
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和日期';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
             try {
            $wheres['job_id'] = $job_id;
            $wheres['job_type'] = $job_type;
            $last_risk_datas = array();
            //查询当前服务
            if ($job_type == 1) {
                $job = Db::table('joborder')->where('JobID',$job_id)->field('ContractID,ServiceType')->find();
                $last_w['ContractID'] = $job['ContractID'];
                $last_w['ServiceType'] = $job['ServiceType'];
                $last_w['Status'] = 3 ;
                $last_job =  Db::table('joborder')->where($last_w)->order('JobDate', 'desc')->field('JobID as id')->select();
            }else if ($job_type == 2) {
                $job = Db::table('followuporder')->where('FollowUpID',$job_id)->field('ContractID,SType')->find();
                $last_w['ContractID'] = $job['ContractID'];
                $last_w['SType'] = $job['SType'];
                $last_w['Status'] = 3 ;
                $last_job =  Db::table('followuporder')->where($last_w)->order('JobDate', 'desc')->field('FollowUpID as id')->select();
            }
            if (!empty($last_job)) {  
                // $y_array = [] ;
                $n_array = [] ;
                // $f_array = [] ;
                for($i=0;$i<count($last_job);$i++){
                    $last_e['job_id'] = $last_job[$i]['id'];
                    $last_e['job_type'] = $job_type;
                    $y = Db::table('lbs_service_risks')->where($last_e)->where('status',1)->where('follow_id',0)->order('id', 'asc')->select()->toArray();
                    $n = Db::table('lbs_service_risks')->where($last_e)->where('status',0)->where('follow_id',0)->order('id', 'asc')->select()->toArray();
                    $risk_id_datas = Db::table('lbs_service_risks')->where('job_id',$job_id)->where('job_type',$job_type)->order('id', 'asc')->field('id')->select()->toArray();
                    $risk_ids = [] ;
                    if (count($risk_id_datas)>0) {
                        for($r=0; $r < count($risk_id_datas); $r++){
                            array_push($risk_ids,$risk_id_datas[$r]['id']);
                        }
                    }
                    $f = Db::table('lbs_service_risks')->where($last_e)->where('status',2)->whereIn('follow_id',$risk_ids)->order('id', 'asc')->select()->toArray();
                    if (count($n)>0) {
                        for ($j=0; $j < count($n); $j++) { 
                            array_push($n_array,$n[$j]);
                        }
                    }
                }    
            }
            if (count($n_array)>0) {
                $data = count($n_array);
            }
             //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $data;
        } catch (\Exception $e) {
             $result['code'] = 1;
             $result['msg'] = $e->getMessage();
             $result['data'] = 0;
            return json($result);
        }
           
        }else{
             $result['code'] = 0;
             $result['msg'] = '登录失效，请重新登陆';
             $result['data'] = null;
        }
        return json($result);
    
    }
}