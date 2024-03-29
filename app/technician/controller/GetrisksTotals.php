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
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
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
                $last_e['job_type'] = 1;
                $last_job =  Db::table('joborder')->where($last_w)->order('JobDate', 'desc')->field('GROUP_CONCAT(JobID) as id')->find();
            }else if ($job_type == 2) {
                // 查询到followuporder表中不存在 ContractID 所以直接返回 0
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = 0;
                return json($result);
                /*
                $job = Db::table('followuporder')->where('FollowUpID',$job_id)->field('ContractID,SType')->find();
                $last_w['ContractID'] = $job['ContractID'];
                $last_w['SType'] = $job['SType'];
                $last_w['Status'] = 3 ;
                $last_e['job_type'] = 2;
                $last_job =  Db::table('followuporder')->where($last_w)->order('JobDate', 'desc')->field('GROUP_CONCAT(FollowUpID) as id')->find();
                */

            }
            // dd($last_job['id']);
                if($last_job['id']){
                    $last_e['status'] = 0; // 0未解决，1已解决
                    $last_e['follow_id'] = 0; // 0 为跟进的

                    $n = Db::table('lbs_service_risks')->where($last_e)->whereIn('job_id',$last_job['id'])->order('id', 'asc')->count();
                    $last_g['confirm_status'] = 1;
                    $g = Db::table('lbs_service_risks')->where($last_g)->whereIn('job_id',$last_job['id'])->order('id', 'asc')->count();

                }
                // $last_e['job_id'] = $last_job['id'];


                 //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data']['n'] = $n??0;
                $result['data']['g'] = $g??0;
            } catch (\Exception $e) {
                 $result['code'] = 1;
                 $result['msg'] = $e->getMessage();
                 $result['data']['n'] = 0;
                 $result['data']['g'] = 0;
                return json($result);
            }

        }else{
             $result['code'] = 0;
             $result['msg'] = '登录失效，请重新登陆';
             $result['data'] = null;
        }
        return json($result);

    }

    public function index1()
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
        // if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
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
                $y_array = [] ;
                $n_array = [] ;
                $f_array = [] ;
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
                    if (count($y)>0) {
                        for ($m=0; $m < count($y); $m++) {
                            array_push($y_array,$y[$m]);
                        }
                    }
                    if (count($n)>0) {
                        for ($j=0; $j < count($n); $j++) {
                            array_push($n_array,$n[$j]);
                        }
                    }
                    if (count($f)>0) {
                        for ($k=0; $k < count($f); $k++) {
                            array_push($f_array,$f[$k]);
                        }
                    }
                }
            }
            if (count($n_array)>0) {
                $last_risk_datas['n'] = $n_array;
            }
            if (count($y_array)>0) {
                $last_risk_datas['y'] = $y_array;
            }
            if (count($f_array)>0) {
                $last_risk_datas['f'] = $f_array;
            }
             //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $last_risk_datas;

        // }else{
        //      $result['code'] = 0;
        //      $result['msg'] = '登录失效，请重新登陆';
        //      $result['data'] = null;
        // }
        return json($result);
    }
}
