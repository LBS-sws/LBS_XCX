<?php
/**
 * Created by : lbs_xcx_RKyxZX
 * User: xiangsong
 * Date: 2022/10/18
 * Time: 10:14 AM
 */

declare (strict_types=1);

namespace app\technician\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;


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
        // var_dump($jobdate);exit;
        $user_token = Db::name('token')->where('StaffID', $staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time) / 60 / 60;
        //验证登录状态
        if ($token == $user_token['token'] && ($c_time <= 24)) {
            $job_wheres['j.JobDate'] = $jobdate;
            $start_date = date("Y-m-d", strtotime("$jobdate -6 month"));
            $end_date = date("Y-m-d", strtotime("$jobdate +6 month"));
            //服务单
            $job_datas = Db::table('joborder')->alias('j')
                ->join('service s', 'j.ServiceType=s.ServiceType')
                ->where('j.JobDate', '>=', $start_date)
                ->where('j.JobDate', '<=', $end_date)
                ->where('j.Staff01|j.Staff02|j.Staff03', '=', $staffid)
                ->whereIn('j.Status', [-1, 2, 3])
                ->field('count(1) as count,j.JobDate as date')
                ->group('j.JobDate asc')
                ->select()->toArray();
            //跟进单

            $follow_datas = Db::table('followuporder')->alias('j')
                ->join('service s', 'j.SType=s.ServiceType')
                ->where('j.JobDate', '>=', $start_date)
                ->where('j.JobDate', '<=', $end_date)
                ->where('j.Staff01|j.Staff02|j.Staff03', '=', $staffid)
                ->whereIn('j.Status', [-1, 2, 3])
                ->field('count(1) as count,j.JobDate as date')
                ->group('j.JobDate asc')
                ->select()->toArray();
                // var_dump($follow_datas);die();

            // $follow_datas = Db::table('followuporder')->alias('j')->join('service s', 'j.SType=s.ServiceType')->where($job_wheres)->where('j.Staff01|j.Staff02|j.Staff03', '=', $staffid)->whereIn('j.Status', [-1, 2, 3])->field('j.FollowUpID,j.CustomerName,j.Addr,j.JobDate,j.JobTime,j.JobTime2,s.ServiceName,j.Status,j.StartTime')->select();
            //获取城市
            $user = Db::name('staff')->where('StaffID', $staffid)->find();
            $launch_date = Db::name('enums')->alias('e')->join('officecity o ', 'o.Office=e.EnumID')->join('lbs_service_city_launch_date l ', 'e.Text=l.city')->where('o.City', $user['City'])->where('e.EnumType', 8)->field('l.launch_date')->find();
            if ($launch_date) {
                if ($launch_date['launch_date'] > $jobdate) {
                    $job_datas = [];
                    $follow_datas = [];
                }
            }
            //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $data = $this->array_combine($job_datas,$follow_datas);
            $result['data'] = $data;
//            $result['data']['follows'] = $follow_datas;
        } else {
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
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
