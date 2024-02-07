<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class SaveriskOFasn
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if($_POST['ct']==1){
            if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['risk_targets']) || !isset($_POST['risk_types']) || !isset($_POST['risk_rank']) || !isset($_POST['risk_label']) || !isset($_POST['risk_description']) || !isset($_POST['risk_area'])){
                return json($result);
            }
            if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['risk_targets']) || empty($_POST['risk_types']) || empty($_POST['risk_rank']) || empty($_POST['risk_label'])  || empty($_POST['risk_description'])  || empty($_POST['risk_area'])){
                return json($result);
            }
        }else{
            if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['risk_targets']) || !isset($_POST['risk_types']) || !isset($_POST['risk_rank']) || !isset($_POST['risk_label']) || !isset($_POST['risk_description']) || !isset($_POST['site_photos'])){
                return json($result);
            }
            if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['risk_targets']) || empty($_POST['risk_types']) || empty($_POST['risk_rank']) || empty($_POST['risk_label'])  || empty($_POST['risk_description'])  || empty($_POST['site_photos'])){
                return json($result);
            }
        }

        //获取信息
        $staffid = $_POST['staffid'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;


        // 客户类型
        if($_POST['customer_type']==248 || $_POST['customer_type']==139 && $_POST['check_datas']!='undefined'){
            $arr = json_decode($_POST['check_datas'],true);
            // print_r($arr);
            foreach ($arr as $key=>$val){
                if($val['type']==2 && $val['value']==''){
                    $result['code'] = 0;
                    $result['msg'] = $arr[$key]['label'] .'必填';
                    $result['data'] = null;
                    return json($result);
                }
                if($val['type']==3 && $val['value']==''){
                    $result['code'] = 0;
                    $result['msg'] = $arr[$key]['label'] .'必填';
                    $result['data'] = null;
                    return json($result);
                }
            }
        }

        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24 * 30)) {
            $id = $_POST['id']?$_POST['id']:0;
            if(isset($_POST['id']) && $_POST['id'] == 'undefined'){
                $id = 0;
            }
//            dd($id);
            $data['job_id'] = $_POST['job_id'];
            $data['job_type'] = $_POST['job_type'];
            $data['risk_targets'] = $_POST['risk_targets'];
            $data['risk_types'] = $_POST['risk_types'];
            $data['risk_rank'] = $_POST['risk_rank'];
            $data['risk_label'] = $_POST['risk_label'];
            $data['site_photos'] =  $_POST['site_photos'];
            $data['risk_description'] = $_POST['risk_description'];
            $data['risk_proposal'] = $_POST['risk_proposal'];
            $data['take_steps'] = $_POST['take_steps'];
            $data['risk_area'] = $_POST['risk_area'];

            $data['risk_data'] = $_POST['check_datas'] ? $_POST['check_datas'] : json_encode($_POST['check_datas'],true);


            if ($id>0) {


                $update_datas = Db::table('lbs_service_risks')->where('id', $id)->update($data);


                $save_datas = $id;
            }else{
                $data['creat_time'] = date('Y-m-d H:i:s', time());
                $save_datas = Db::table('lbs_service_risks')->insert($data);
            }
            if ($save_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = $save_datas;
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
}
