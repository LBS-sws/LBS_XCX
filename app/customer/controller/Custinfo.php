<?php
declare (strict_types = 1);

namespace app\customer\controller;
use think\facade\Db;


class Custinfo
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入客户ID';
        $result['data'] = null;

        $token ='a513d72bb2105c7af09097cc4f902045';// request()->header('token');
        $_POST['customerid'] = 'wait973-ZY';
        $_POST['staffid'] = 'wait973-ZY001';
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['customerid'])){
            return json($result);
        }
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
            $customer = Db::name('customercompany')->where('CustomerID',$customerid)->find();
            if($customer['isHQ'] == 1 && !empty($customer['GroupID'])){
                //查询集团下的所有店
                $customer_group = Db::name('customercompany')->where('GroupID',$customer['GroupID'])->field('CustomerID as id,NameZH as name,City as city')->select();
                if(!empty($customer_group)){
                    return success(1,'ok',$customer_group);
                }
            }else{
                $data['id'] = $customer['CustomerID'];
                $data['name'] = $customer['NameZH'];
                return success(1,'ok',$data);
            }
            dd($customer);
        }else{
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }
}
