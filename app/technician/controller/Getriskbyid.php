<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Getriskbyid
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['city']) || !isset($_POST['service_type'])){
            return json($result);
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['city']) || empty($_POST['service_type'])){
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        $id = $_POST['id']?$_POST['id']:0;
        $city = $_POST['city'];
        $service_type = $_POST['service_type'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;

        $CustomerType = '';  // 客户类型
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            $wheres['id'] = $job_id;
            $wheres['job_id'] = $job_id;
            $wheres['job_type'] = $job_type;
            $service_data['risk'] = [];
            if($id>0){
                $service_data['risk'] = Db::table('lbs_service_risks')->where('id',$id)->find();


                $CustomerID = Db::table('joborder')->where('JobID',$service_data['risk']['job_id'])->value('CustomerID');
                $CustomerType = Db::table('customercompany')->where('CustomerID',$CustomerID)->value('CustomerType');
            }
            $service_data['targets'] =  Db::table('lbs_service_risk_target_lists')->where('city',$city)->field('target as label,target as value')->select();
            $service_data['types'] =  Db::table('lbs_service_risk_type_lists')->where('city',$city)->field('type as label,type as value')->select();
            $service_data['ranks'] =  Db::table('lbs_service_risk_rank_lists')->field('rank as label,rank as value')->select();
            $service_data['labels'] =  Db::table('lbs_service_risk_label_lists')->field('label as label,label as value')->select();
            $service_data['CustomerType'] = $CustomerType;

            // type=1 number| type=2 selct | type=3 text
            $service_data['check_datas'] = array(
                array('label'=>'鼠类数量','value'=>0, 'type' =>'1'),
                array('label'=>'有无鼠迹','value'=>'', 'type' =>'2'),
                array('label'=>'蟑螂活体数量','value'=>0, 'type' =>'1'),
                array('label'=>'蟑螂痕迹鼠迹','value'=>'', 'type' =>'2'),
                array('label'=>'飞虫数量','value'=>0, 'type' =>'1'),
                array('label'=>'飞虫类目','value'=>'', 'type' =>'3')
            );

            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $service_data;

        }else{
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }
}
