<?php
/**
 * Created by : lbs_xcx_RKyxZX
 * User: xiangsong
 * Date: 2022/10/18
 * Time: 10:14 AM
 */

declare (strict_types=1);

namespace app\technician\controller;

use think\cache\driver\Redis;
use think\facade\Db;

class JobTotals
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和日期';
        $result['data'] = null;

        $token = request()->header('token');
        if (!isset($_POST['staffid']) || !isset($token) || !isset($_POST['jobdate'])) {
            return json($result);
        }
        if (empty($_POST['staffid']) || empty($token) || empty($_POST['jobdate'])) {
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $jobdate = isset($_POST['jobdate'])?$_POST['jobdate']:date('Y-m-d');
        //获取用户登录信息
        $redis = new Redis();
        $token_key = 'token_' . $staffid;
        $user_token = $redis->get($token_key);
        if (!$user_token) {
            $user_token = Db::name('token')->where('StaffID',$staffid)->find();
            $redis->set($token_key,$user_token,600);
        }
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time) / 60 / 60;
        //验证登录状态
        if ($token == $user_token['token'] && ($c_time <= 24)) {
            $job_total_cache = 'jobtotal_' . $staffid;
            $result = $redis->get($job_total_cache);
            if (!$result) {
//                $job_wheres['j.JobDate'] = $jobdate;
                $start_date = date("Y-m-d", strtotime("$jobdate -3 month"));
                $end_date = date("Y-m-d", strtotime("$jobdate +2 month"));
                //服务单

                $job_datas = Db::query("SELECT COUNT(1) AS count, j.JobDate AS date FROM joborder j INNER JOIN service s ON j.ServiceType = s.ServiceType WHERE j.JobDate BETWEEN :start_date AND :end_date AND :staffid IN (j.Staff01, j.Staff02, j.Staff03) AND j.Status IN ('-1', '2', '3') GROUP BY j.JobDate ORDER BY j.JobDate ASC", ['start_date' => $start_date,'end_date'=>$end_date,'staffid'=>$staffid], true);
                //跟进单

                $follow_datas = Db::query("SELECT COUNT(1) AS count, j.JobDate AS date FROM followuporder j INNER JOIN service s ON j.SType = s.ServiceType WHERE j.JobDate BETWEEN :start_date AND :end_date AND :staffid IN (j.Staff01, j.Staff02, j.Staff03) AND j.Status IN ('-1', '2', '3') GROUP BY j.JobDate ORDER BY j.JobDate ASC", ['start_date' => $start_date,'end_date'=>$end_date,'staffid'=>$staffid], true);


                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $data = $this->array_combine($job_datas, $follow_datas);
                $result['data'] = $data;
                $redis->set($job_total_cache, $result,3600);
            }
//            $result['data']['follows'] = $follow_datas;
        } else {
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
            $redis->delete($token_key);
        }
        return json($result);
    }

    public function array_combine($arr1,$arr2)
    {
        $arrs = [];
        // 如果数组arr1不为空
        if (!empty($arr1)) {
            // 将二维数组arr1提取id值出来转换成一维数组
            $arr_id = array_column($arr1, 'date');

            foreach ($arr2 as $v) {
                // 去重操作
                if (!in_array($v['date'], $arr_id)) {
                    $arrs[] = [
                        'date' => $v['date'],
                        'count' => $v['count']??1
                    ];
                }
            }
// 合并数组
            $arr2 = array_merge($arr1, $arrs);
        }
        return $arr2;
    }
}
