<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;


class GetRiskTotal
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入客户ID';
        $result['data'] = null;
        $token = request()->header('token');
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['customerid'])){
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $customerid = $_POST['customerid'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $last_risk_datas = array();
            //查询当前服务                $last_w['ContractID'] = $job['ContractID'];
            $last_w['Status'] = 3 ;
            $last_w['CustomerID'] = $_POST['customerid'];
            $last_e['job_type'] = 1;
                // 获取所有存在的id
            $last_job =  Db::table('joborder')->where($last_w)->order('JobDate', 'desc')->field('GROUP_CONCAT(JobID ORDER BY JobID DESC ) as id')->find();
            $y = [];$n = [];$f = [];
            if (isset($last_job) && $last_job['id'] != null){
                $y = Db::table('lbs_service_risks')->where($last_e)->where('status', 1)->where('follow_id', 0)->whereIn('job_id', $last_job['id'])->order('id', 'asc')->select();
//                dd(Db::table('lbs_service_risks')->getLastSql());
                $n = Db::table('lbs_service_risks')->where($last_e)->where('status', 0)->where('follow_id', 0)->whereIn('job_id', $last_job['id'])->order('id', 'asc')->select();
                $f = Db::table('lbs_service_risks')->where($last_e)->where('status', 2)->whereIn('job_id', $last_job['id'])->where('follow_id', '>', 0)->order('id', 'asc')->select();
            }
            $last_risk_datas['y'] = [];
            $last_risk_datas['n'] = [];
            $last_risk_datas['f'] = [];
            if (count($y) > 0) {
                $last_risk_datas['y'] = $y??0;
            }
            if (count($n) > 0) {
                $last_risk_datas['n'] = $n??0;
            }
            if (count($f) > 0) {
                $last_risk_datas['f'] = $f??0;
            }
            // var_dump($last_risk_datas);exit;
            //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $last_risk_datas;

        }else{
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }
}
