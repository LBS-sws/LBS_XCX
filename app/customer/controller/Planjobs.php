<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;

class Planjobs
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入客户ID';
        $result['data'] = null;

        $token = request()->header('token');

        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['customerid'])){
            return json($result);
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['customerid'])){
            return json($result);
        }
        $sql = '';
        $datas_followup = [];
        //获取信息
        $staffid = $_POST['staffid'];
        $customerid = $_POST['customerid'];
        $store = $_POST['store'];
        $daterange = $_POST['daterange'];
        $job_data = $_POST['jobdate'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID',$staffid)->find();
        $user_item = Db::name('customercontact')->where('ContactID',$staffid)->find();
        //print_r($user_item);exit;
        // echo Db::name('cuztoken')->getLastSql();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            //判断分店还是总店
            $mainstore = $_POST['mainstore']?$_POST['mainstore']:0;
            $datas = [] ;
            $datas_followup = [];
            $options = [['label'=>'全部','value'=>'']];
            //查询当前公司
            $customer = Db::name('customercompany')->where('CustomerID',$customerid)->find();
            if($mainstore == 1 && !empty($customer['GroupID'])){

                //查询集团下的所有店
                $customer_group = Db::name('customercompany')->where('GroupID', $customer['GroupID'])->field('CustomerID,NameZH,City')->select();
                // print_r($customer_group);exit;
                // echo Db::name('customercompany')->getLastSql();exit;
                foreach ($customer_group as $key=>$val){
                    $list = Db::table('joborder')->alias('j')
                        ->leftJoin('service s','j.ServiceType=s.ServiceType')
                        ->leftJoin('staff u','j.Staff01=u.StaffID')
                        ->join('staff uo','j.Staff02=uo.StaffID','left')
                        ->join('staff ut','j.Staff03=ut.StaffID','left')
                        // ->leftJoin('customercontact c','j.CustomerID = c.CustomerID')
                        ->where([['j.CustomerID','=',$val['CustomerID']],['j.JobDate','=',$job_data]])
                        ->where([['j.Status','<>',9]])
                        ->field('j.JobID,j.ContractID,j.ContractNumber,j.JobDate,j.JobTime,j.JobTime2,j.Staff02,j.Staff03,j.CustomerID,j.CustomerName,j.Status,j.FirstJob,s.ServiceName,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03')

                        ->select()
                        ->toArray();

                    // $sql = Db::table('joborder')->getLastSql();

                    if($list){
                        $datas[] = $list;
                    }
                    // 服务单
                    $list_follow = Db::table('followuporder')->alias('j')
                        ->join('service s','j.SType=s.ServiceType')
                        ->join('staff u','j.Staff01=u.StaffID')
                        ->join('staff uo','j.Staff02=uo.StaffID','left')
                        ->join('staff ut','j.Staff03=ut.StaffID','left')
                        ->leftJoin('customercontact c','j.CustomerID = c.CustomerID')
                        ->where([['j.CustomerID','=',$val['CustomerID']],['j.JobDate','=',$job_data]])
                        ->where([['j.Status','<>',9]])
                        ->where([['c.Mobile','=',$user_item['Mobile']]])
                        ->field('j.JobDate,j.JobTime,j.JobTime2,j.Staff02,j.Staff03,j.CustomerID,j.CustomerName,j.Status,s.ServiceName,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03')
                        ->select()
                        ->toArray();
                    // $sql = Db::table('followuporder')->getLastSql();

                    if($list_follow){
                        $datas_followup[] = $list_follow;
                    }
                }

                // 常规服务
                $twoDimensionalArray = array();
                foreach ($datas as $firstLevel) {
                    foreach ($firstLevel as $secondLevel) {
                        $twoDimensionalArray[] = $secondLevel;
                    }
                }
                $datas = $twoDimensionalArray;

                // 跟进
                $twoDimensionalArrayx = array();
                foreach ($datas_followup as $firstLevelx) {
                    foreach ($firstLevelx as $secondLevelx) {
                        $twoDimensionalArrayx[] = $secondLevelx;
                    }
                }
                $datas_followup = $twoDimensionalArrayx;

                foreach ($datas as $key=>$val){
                    if($val['FirstJob']==1){
                        $data[$key]['task_type'] = "首次服务";
                    }else{
                        $data[$key]['task_type'] = "常规服务";
                    }
                }
                foreach ($datas_followup as $key=>$val){
                    $datas_followup[$key]['FirstJob'] = 2;
                }


            }else{
                $sql = '2';
                // 服务单
                $datas = Db::table('joborder')->alias('j')
                    ->join('service s','j.ServiceType=s.ServiceType')
                    ->join('staff u','j.Staff01=u.StaffID')
                    ->join('staff uo','j.Staff02=uo.StaffID','left')
                    ->join('staff ut','j.Staff03=ut.StaffID','left')
                    ->leftJoin('customercontact c','j.CustomerID = c.CustomerID')
                    ->where([['j.CustomerID','=',$customerid],['j.JobDate','=',$job_data]])
                    ->where([['j.Status','<>',9]])
                    ->field('j.JobID,j.ContractID,j.ContractNumber,j.JobDate,j.JobTime,j.JobTime2,j.Staff02,j.Staff03,j.CustomerID,j.CustomerName,j.Status,j.FirstJob,s.ServiceName,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03')
                    ->group('j.JobID')
                    ->select()
                    ->toArray();
                // $sql = Db::table('joborder')->getLastSQL();
                // 跟进单
                $datas_followup = Db::table('followuporder')->alias('j')
                    ->join('service s','j.SType=s.ServiceType')
                    ->join('staff u','j.Staff01=u.StaffID')
                    ->join('staff uo','j.Staff02=uo.StaffID','left')
                    ->join('staff ut','j.Staff03=ut.StaffID','left')
                    ->leftJoin('customercontact c','j.CustomerID = c.CustomerID')
                    ->where([['j.CustomerID','=',$customerid],['j.JobDate','=',$job_data]])
                    ->where([['j.Status','<>',9]])
                    ->field('j.JobDate,j.JobTime,j.JobTime2,j.Staff02,j.Staff03,j.CustomerID,j.CustomerName,j.Status,s.ServiceName,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03')->select()->toArray();

            }

            //获取时间
            $begin_date = date("Y-m-d",strtotime("now"));
            $end_date = date("Y-m-d",strtotime("-4 month"));
            $result['data']['daterange'] = [$end_date,$begin_date];
            $result['sql'] = $sql;
            if ($datas || $datas_followup) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data']['jobs'] = $datas;
                $result['data']['followup'] = $datas_followup;
                $result['data']['options'] = $options;
            }else{
                $result['code'] = 1;
                $result['msg'] = '成功，无数据';
                // $result['data'] = null;
            }
        }else{
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }

}
