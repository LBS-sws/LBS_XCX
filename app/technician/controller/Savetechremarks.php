<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Savetechremarks
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['contractnumber']) || !isset($_POST['customerid']) || !isset($_POST['servicetype'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['contractnumber']) || empty($_POST['customerid']) || empty($_POST['servicetype'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $contractnumber = $_POST['contractnumber'];
        $customerid = $_POST['customerid'];
        $servicetype = $_POST['servicetype'];
        $techremarks = $_POST['techremarks'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            $arr = array('staffid'=>$staffid,'token'=>$token,'contractnumber'=>$contractnumber,'customerid'=>$customerid,'servicetype'=>$servicetype,'techremarks'=>$techremarks);
            $xinu_data = $this->curl_post(config('app.uapp_url').config('app.uapi_list.edit_remarks'),$arr);
            $job_datas = Db::table('servicecontract')->where('ContractNumber', $contractnumber)->where('CustomerID', $customerid)->where('ServiceType', $servicetype)->update(['TechRemarks' => $techremarks]);
            //返回数据
            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $xinu_data;
           
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
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}
