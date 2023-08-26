<?php
/**
 * Created by : lbs_xcx_RKyxZX
 * User: xiangsong
 * Date: 2022/10/18
 * Time: 10:14 AM
 */

declare (strict_types=1);

namespace app\customer\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;


class PlanTotals
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
        $staffid = $_POST['staffid'];           // 联络人ID
        $jobdate = isset($_POST['jobdate'])?$_POST['jobdate']:date('Y-m-d');
        $customerid = $_POST['customerid'];     // 客户ID
        //获取用户登录信息

        $user_token = Db::name('cuztoken')->where('StaffID', $staffid)->find();
        //echo Db::name('cuztoken')->getLastSql();
        //var_dump($user_token);exit;
        $login_time = strtotime($user_token['stamp']);
        $now_time = time();
        $c_time = ($now_time - $login_time) / 60 / 60;

        //验证登录状态
        if ($token == $user_token['token'] && ($c_time <= 24)) {
            $res = array();

            //判断分店还是总店
            $mainstore = $_POST['mainstore']?$_POST['mainstore']:0;

            //查询当前公司
            $customer = Db::name('customercompany')->where('CustomerID',$customerid)->find();
            if($mainstore == 1 && !empty($customer['GroupID'])){
                //查询集团下的所有店
                $customer_group = Db::name('customercompany')->where('GroupID', $customer['GroupID'])->field('CustomerID,NameZH,City')->select();

                foreach ($customer_group as $key=>$val){
                    $list = Db::table('joborder')->alias('j')->leftJoin('customercontact c','j.CustomerID = c.CustomerID')
                        ->where([['j.CustomerID','=',$val['CustomerID']]])
                        ->where([['j.Status','<>',9]])
                        ->field('j.JobDate  as date ')->select()->toArray();
                    // echo Db::table('joborder')->getLastSql();
                    $res[] = $list;

                    // 跟进单
                    $list1 = Db::table('followuporder')->alias('j')
                        ->join('service s','j.SType=s.ServiceType')
                        ->leftJoin('customercontact c','j.CustomerID = c.CustomerID')
                        ->where([['j.CustomerID','=',$customerid]])
                        ->where([['j.Status','<>',9]])
                        ->field('j.JobDate as date')->select()->toArray();
                    $res1[] = $list1;
                }
                $res = array_merge($res,$res1);

                $twoDimensionalArray = array();
                foreach ($res as $firstLevel) {
                    foreach ($firstLevel as $secondLevel) {
                        $twoDimensionalArray[] = $secondLevel;
                    }
                }

                $arr = $twoDimensionalArray;
                $count = count($arr);
                $data = array();

                for($i=0;$i<$count;$i++){
                    $a = $arr[$i];
                    unset($arr[$i]);
                    if(!in_array($a,$arr)){
                        $data[] = $a;
                    }
                }
                $res = $data;
            }else{
                $arr = Db::table('joborder')->alias('j')
                    ->join('service s','j.ServiceType=s.ServiceType')
                    ->leftJoin('customercontact c','j.CustomerID = c.CustomerID')
                    ->where([['j.CustomerID','=',$customerid]])
                    ->where([['j.Status','<>',9]])
                    ->field('j.JobDate  as date ')->select()->toArray();

                $arr_followup = Db::table('followuporder')->alias('j')
                    ->join('service s','j.SType=s.ServiceType')
                    ->leftJoin('customercontact c','j.CustomerID = c.CustomerID')
                    ->where([['j.CustomerID','=',$customerid]])
                    ->where([['j.Status','<>',9]])
                    ->field('j.JobDate as date')->select()->toArray();

                /* 合并 */
                $arr = array_merge($arr,$arr_followup);

                /* 去重复 */
                $count = count($arr);
                for($i=0;$i<$count;$i++){
                    $a = $arr[$i];
                    unset($arr[$i]);
                    if(!in_array($a,$arr)){
                        $res[] = $a;
                    }
                }
            }


            //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';

            $result['data'] = $res;
//            $result['data']['follows'] = $follow_datas;
        } else {
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }

    public function arraySort($arr, $keys, $type = 'asc') {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v){
            $keysvalue[$k] = $v[$keys];
        }
        $type == 'asc' ? asort($keysvalue) : arsort($keysvalue);
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
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
