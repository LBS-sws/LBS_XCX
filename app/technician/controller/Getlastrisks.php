<?php
declare (strict_types=1);

namespace app\technician\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;


class Getlastrisks
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和日期';
        $result['data'] = null;

        $token = request()->header('token');
        if (!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type'])) {
            return json($result);
        }
        if (empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type'])) {
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID', $staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time) / 60 / 60;
        //验证登录状态
        if ($token == $user_token['token'] && ($c_time <= 24)) {
            $wheres['job_id'] = $job_id;
            $wheres['job_type'] = $job_type;
            $last_risk_datas = array();
            //查询当前服务
            if ($job_type == 1) {
                $job = Db::table('joborder')->where('JobID', $job_id)->field('ContractID,ServiceType')->find();
                $last_w['ContractID'] = $job['ContractID'];
                $last_w['ServiceType'] = $job['ServiceType'];
                $last_w['Status'] = 3;
                $last_e['job_type'] = 1;

                // 获取所有存在的id
                $last_job = Db::table('joborder')->where($last_w)->order('JobDate', 'desc')->field('GROUP_CONCAT(JobID) as id')->find();
            } else if ($job_type == 2) {
                // 第二种情况应该是不存在的   暂时不管
                // $job = Db::table('followuporder')->where('FollowUpID',$job_id)->field('ContractID,SType')->find();
                // $last_w['ContractID'] = $job['ContractID'];
                // $last_w['SType'] = $job['SType'];
                // $last_w['Status'] = 3 ;
                // $last_e['job_type'] = 2;

                // $last_job =  Db::table('followuporder')->where($last_w)->order('JobDate', 'desc')->field('FollowUpID as id')->find();
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = [];
                return json($result);
            }
            $y = [];
            $n = [];
            $f = [];
            if (isset($last_job) && $last_job['id'] != null) {
                $y = Db::table('lbs_service_risks')->where($last_e)->where('status', 1)->where('follow_id', 0)->whereIn('job_id', $last_job['id'])->order('id', 'asc')->select();
                $n = Db::table('lbs_service_risks')->where($last_e)->where('status', 0)->where('follow_id', 0)->whereIn('job_id', $last_job['id'])->order('id', 'asc')->select();
                $f = Db::table('lbs_service_risks')->where($last_e)->where('status', 2)->whereIn('job_id', $last_job['id'])->where('follow_id', '>', 0)->order('id', 'asc')->select();
            }
            $last_risk_datas['y'] = [];
            $last_risk_datas['n'] = [];
            $last_risk_datas['f'] = [];
            if (count($y) > 0) {
                $last_risk_datas['y'] = $y ?? 0;
            }
            if (count($n) > 0) {
                $last_risk_datas['n'] = $n ?? 0;
            }
            if (count($f) > 0) {
                $last_risk_datas['f'] = $f ?? 0;
            }
            //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $last_risk_datas;

        } else {
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }
}