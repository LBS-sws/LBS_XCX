<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\common\controller\Base;
use think\facade\Request;
use think\facade\Db;
use think\facade\Session;
use think\cache\driver\Redis;


class Jobsignout
{
    public function index()
    {
        $redis = new Redis();
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
        $signdate = $_POST['signdate'];
        $starttime = date('H:i:s',time());//$_POST['starttime'];
        $invoice = $_POST['invoice'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();

        //直接查询该token下的账号密码 重新给xinU安排一波
        //用户信息
        $user_info = Db::name('staff')->field('StaffID,Password')->where('StaffID',$staffid)->find();
        //回传新U登录状态
        $user_data = ['staffid'=>$user_info['StaffID'],'password'=>$user_info['Password'],'token'=>$token];
        $xinu_result = $this->curl_post(config('app.uapp_url').config('app.uapi_list.edit_token') ,$user_data);
        $xinu_check = json_decode($xinu_result,true);
        if($xinu_check['code'] == 0){
            $result['code'] = 0;
            $result['msg'] = $xinu_check['msg'];
            $result['data'] = null;
        }
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        // dump($c_time);exit;

        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            if($jobtype==1){
                $job_time = Db::table('joborder')->alias('j')->join('staff s','j.Staff01=s.StaffID')->where('j.JobID', $jobid)->field('j.StartTime,j.ContractID,s.StaffName,ServiceType,j.FirstJob,j.ContractNumber,j.CustomerID')->find();
                //回传新U登录状态
                if ($job_time['ServiceType']==1) {
                    $jobcardtable = "JobCardBlue";
                }elseif ($job_time['ServiceType']==2) {
                    $jobcardtable = "JobCardOrange";
                }elseif ($job_time['ServiceType']==3) {
                    $jobcardtable = "JobCardYellow";
                }else{
                    $jobcardtable = "JobCardGeneric";
                }
                $arr = array('staffid'=>$staffid,'jobid'=>$jobid,'jobtype'=>$jobtype,'token'=>$token,'finishdate'=>$signdate,'starttime'=>$job_time['StartTime'],'finishtime'=>$starttime,'contractid'=>$job_time['ContractID'],'staffname'=>$job_time['StaffName'],'jobcardtable'=>$jobcardtable,'invoice'=>$invoice,'firstjob'=>$job_time['FirstJob'],'servicetype'=>$job_time['ServiceType'],'contractnumber'=>$job_time['ContractNumber'],'customerid'=>$job_time['CustomerID']);

                $xinu_data = $this->curl_post(config('app.uapp_url').config('app.uapi_list.edit_job_status'),$arr);
                $xinu = json_decode($xinu_data,true);
                
                $job_datas_key = 'job_start_'.$jobtype. 'key_'.$jobid;
                $job_start = $redis->get($job_datas_key);
                if($job_start){
                    $redis->delete($job_datas_key);
                }

                
                if($xinu['code']==1){
                    $job_datas = Db::table('joborder')->where('JobID', $jobid)->update(['FinishDate' => $signdate , 'FinishTime' => $starttime,'Status'=>3]);
// Percy 發電郵
                    $xdata = ['job_id'=>$jobid, 'job_type'=>$jobtype];
                    $x_datas = Db::table('queue_db.mail_report_queue')->insert($xdata);
// Percy - End
                }else{
                    //返回数据
                    $result['code'] = 0;
                    $result['msg'] = '新U回传失败';
                    $result['xinu'] = $xinu_data;
                    return json($result);
                }

            }elseif ($jobtype==2) {
                $job_time = Db::table('followuporder')->where('FollowUpID', $jobid)->field('JobDate as FinishDate,StartTime')->find();
                $briefing = Db::table('lbs_service_briefings')->where('job_id', $jobid)->where('job_type', 2)->field('content')->find();
                if ($briefing) {
                    $jobreport = $briefing['content'];
                }else{
                    $jobreport = '';
                }
                //回传新U登录状态
                $arr = array('staffid'=>$staffid,'jobid'=>$jobid,'jobtype'=>$jobtype,'token'=>$token,'finishdate'=>$job_time['FinishDate'],'starttime'=>$job_time['StartTime'],'finishtime'=>$starttime,'jobreport'=>$jobreport);
                $xinu_data = $this->curl_post(config('app.uapp_url').config('app.uapi_list.edit_job_status'),$arr);
                $xinu = json_decode($xinu_data,true);
                if($xinu['code']==1){
                    $job_datas = Db::table('followuporder')->where('FollowUpID', $jobid)->update(['FinishTime' => $starttime,'Status'=>3,'JobReport'=>$jobreport]);
// Percy 發電郵
                    $xdata = ['job_id'=>$jobid, 'job_type'=>$jobtype];
                    $x_datas = Db::table('queue_db.mail_report_queue')->insert($xdata);
// Percy - End
                }else{
                    //返回数据
                    $result['code'] = 0;
                    $result['msg'] = '新U回传失败';
                    $result['xinu'] = $xinu_data;
                    return json($result);
                }

            }
            if ($job_datas) {

                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = $job_datas;
                $result['xinu'] = $xinu_data;
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
    public function curl_post($url , $data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        if($output === false)
        {
            $output = curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }
}
