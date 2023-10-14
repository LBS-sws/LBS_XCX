<?php
declare (strict_types = 1);

namespace app\customer\controller;

use app\common\model\AutographV2;
use app\common\model\FollowupOrder;
use app\common\model\JobOrder;
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
        if (!isset($_POST['staffid']) || !isset($token) || !isset($_POST['customerid']) || !isset($_POST['daterange'])) {
            return json($result);
        }
        if (empty($_POST['staffid']) || empty($token) || empty($_POST['customerid']) || empty($_POST['daterange'])) {
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $customerid = $_POST['customerid'];
        $daterange = $_POST['daterange'];
        $store = $_POST['store'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID', $staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time) / 60 / 60;
        //验证登录状态
        if ($token == $user_token['token'] && ($c_time <= 24 * 366)) {
            //判断分店还是总店
            $mainstore = $_POST['mainstore'] ? $_POST['mainstore'] : 0;
            $datas = [];
            $customer = Db::name('customercompany')->where('CustomerID', $customerid)->find();

            if (($mainstore == 1 && $store == '' ) && !empty($customer['GroupID'])) {
                //查询集团下的所有店
                $customer_group = Db::name('customercompany')->where('GroupID',$customer['GroupID'])->field('CustomerID,NameZH,City')->select()->toArray();;
                // var_dump(Db::name('customercompany')->getLastSql());exit();

                // 获取客户组中所有城市的启动日期
                $customerCities = array_column($customer_group, 'City');
                $launchDates = Db::name('enums')
                    ->alias('e')
                    ->join('officecity o', 'o.Office=e.EnumID')
                    ->join('lbs_service_city_launch_date l', 'e.Text=l.city')
                    ->whereIn('o.City', $customerCities)
                    ->where('e.EnumType', 8)
                    ->field('o.City, l.launch_date')
                    ->select();

                $launchDatesMap = [];
                foreach ($launchDates as $launchDate) {
                    $launchDatesMap[$launchDate['City']] = $launchDate['launch_date'];
                }

                // 构建查询语句，获取客户组中所有客户的工作和跟进数据
                $customerIDs = array_column($customer_group, 'CustomerID');
                $job_wheres = [
                    'j.CustomerID' => $customerIDs,
                    'j.Status' => 3,
                ];
                $jobQuery = JobOrder::alias('j')->alias('j')
                    ->with(['ReportAutographV2'=>function($query){
                        return $query->field('job_id,customer_grade')->where(['job_type'=>AutographV2::jobType_jobOrder])->find();
                    }])
                    ->join('service s', 'j.ServiceType=s.ServiceType')
                    ->join('staff u', 'j.Staff01=u.StaffID')
                    ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')
                    ->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
                    ->where($job_wheres)
                    ->whereBetween('j.JobDate', $daterange)
                    ->order('j.JobDate', 'desc')
                    ->field('j.JobID, j.CustomerName, j.JobDate, j.StartTime, j.FinishTime, s.ServiceName, j.StartTime, u.StaffName as Staff01, uo.StaffName as Staff02, ut.StaffName as Staff03, j.FirstJob');

                $followQuery = FollowupOrder::alias('j')
                    ->field('j.FollowUpID,j.FollowUpID as JobID, j.CustomerName, j.JobDate, j.StartTime, j.FinishTime, s.ServiceName, j.StartTime, u.StaffName as Staff01, uo.StaffName as Staff02, ut.StaffName as Staff03')
                    ->with(['ReportAutographV2'=>function($query){
                        return $query->field('job_id,customer_grade')->where(['job_type'=>AutographV2::jobType_followOrder])->find();
                    }])
                    ->join('service s', 'j.SType=s.ServiceType')
                    ->join('staff u', 'j.Staff01=u.StaffID')
                    ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')
                    ->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
                    ->where($job_wheres)
                    ->whereBetween('j.JobDate', $daterange)
                    ->order('j.JobDate', 'desc');

                $job_datas = $jobQuery->select()->toArray();
                $follow_datas = $followQuery->select()->toArray();
            } else {
                // 获取城市的启动日期
                $launch_date = Db::name('enums')
                    ->alias('e')
                    ->join('officecity o', 'o.Office=e.EnumID')
                    ->join('lbs_service_city_launch_date l', 'e.Text=l.city')
                    ->where('o.City', $customer['City'])
                    ->where('e.EnumType', 8)
                    ->field('l.launch_date')
                    ->find();

                $job_wheres['j.CustomerID'] = $store == '' ? $customerid : $store;
                $job_wheres['j.Status'] = 3;
                if ($launch_date) {
                    //服务单
                    $job_datas = JobOrder::alias('j')
                        ->with(['ReportAutographV2'=>function($query){
                            return $query->field('job_id,customer_grade')->where(['job_type'=>AutographV2::jobType_jobOrder])->find();
                        }])
                        ->join('service s', 'j.ServiceType=s.ServiceType')
                        ->join('staff u', 'j.Staff01=u.StaffID')
                        ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')
                        ->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
                        ->where($job_wheres)
                        ->whereTime('j.JobDate', '>=', $launch_date['launch_date'])
                        ->whereBetween('j.JobDate', $daterange)
                        ->order('j.JobDate', 'desc')
                        ->field('j.JobID, j.CustomerName, j.JobDate, j.StartTime, j.FinishTime, s.ServiceName, j.StartTime, u.StaffName as Staff01, uo.StaffName as Staff02, ut.StaffName as Staff03, j.FirstJob')
                        ->select()
                        ->toArray();

                    //跟进单
                    $follow_datas = FollowupOrder::alias('j')
                        ->field('j.FollowUpID, j.FollowUpID as JobID, j.CustomerName, j.JobDate, j.StartTime, j.FinishTime, s.ServiceName, j.StartTime, u.StaffName as Staff01, uo.StaffName as Staff02, ut.StaffName as Staff03')
                        ->with(['ReportAutographV2'=>function($query){
                            return $query->field('job_id,customer_grade')->where(['job_type'=>AutographV2::jobType_followOrder])->find();
                        }])
                        ->join('service s', 'j.SType=s.ServiceType')
                        ->join('staff u', 'j.Staff01=u.StaffID')
                        ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')
                        ->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
                        ->where($job_wheres)
                        ->whereTime('j.JobDate', '>=', $launch_date['launch_date'])
                        ->whereBetween('j.JobDate', $daterange)
                        ->order('j.JobDate', 'desc')
                        ->select()
                        ->toArray();
                } else {
                    //服务单
                    $job_datas = JobOrder::alias('j')
                        ->with(['ReportAutographV2'=>function($query){
                            return $query->field('job_id,customer_grade')->where(['job_type'=>AutographV2::jobType_jobOrder])->find();
                        }])
                        ->join('service s', 'j.ServiceType=s.ServiceType')
                        ->join('staff u', 'j.Staff01=u.StaffID')
                        ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')
                        ->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
                        ->where($job_wheres)
                        ->whereBetween('j.JobDate', $daterange)
                        ->order('j.JobDate', 'desc')
                        ->field('j.JobID, j.CustomerName, j.JobDate, j.StartTime, j.FinishTime, s.ServiceName, j.StartTime, u.StaffName as Staff01, uo.StaffName as Staff02, ut.StaffName as Staff03, j.FirstJob')
                        ->select()
                        ->toArray();
                    //跟进单
                    $follow_datas = FollowupOrder::alias('j')
                        ->field('j.FollowUpID,j.FollowUpID as JobID, j.CustomerName, j.JobDate, j.StartTime, j.FinishTime, s.ServiceName, j.StartTime, u.StaffName as Staff01, uo.StaffName as Staff02, ut.StaffName as Staff03')
                        ->with(['ReportAutographV2'=>function($query){
                            return $query->field('job_id,customer_grade')->where(['job_type'=>AutographV2::jobType_followOrder])->find();
                        }])
                        ->join('service s', 'j.SType=s.ServiceType')
                        ->join('staff u', 'j.Staff01=u.StaffID')
                        ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')
                        ->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
                        ->where($job_wheres)
                        ->whereBetween('j.JobDate', $daterange)
                        ->order('j.JobDate', 'desc')
                        ->select()
                        ->toArray();
                }
            }

            // 处理查询结果
            foreach ($job_datas as $value) {
                $value['type'] = 1;
                if ($value['FirstJob'] == 1) {
                    $value['task_type'] = "首次服务";
                } else {
                    $value['task_type'] = "常规服务";
                }
                $datas[] = $value;
            }

            foreach ($follow_datas as $value) {
                $value['type'] = 2;
                $value['task_type'] = "跟进服务";
                $datas[] = $value;
            }

            // 获取每个数据项的附加数据
            foreach ($datas as &$data) {
                $item = Db::table('lbs_invoice')->where('jobid', $data['JobID'])->find();
                $data['pics'] = $item['pics'];

                $key = $data['Staff01'];
                $code = '';
                $itemx = Db::table('lbs_papersstaff')->where('name|code', '=', $key)->find();
                if ($itemx) {
                    $staffCode = $itemx['code'];
                    $itemInfo = Db::table('lbs_papersstaff_info')->where('StaffCode', '=', $staffCode)->find();
                    if ($itemInfo['imgUrl']) {
                        $code = $itemx['code'];
                    }
                }
                $data['papers'] = $code;
            }

            // 返回结果
            if ($datas) {
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = $datas;
            } else {
                $result['code'] = 1;
                $result['msg'] = '成功，无数据';
                $result['data'] = null;
            }
        } else {
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }
}
