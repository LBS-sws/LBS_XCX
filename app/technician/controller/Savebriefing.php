<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Savebriefing
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入服务内容和跟进建议等';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['content']) || !isset($_POST['proposal'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['content']) || empty($_POST['proposal'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        $content = $_POST['content'];
        $proposal = $_POST['proposal'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $data['job_id'] = $job_id;
            $data['job_type'] = $job_type;
            //查询是否存在
            $q_f = Db::table('lbs_service_briefings')->where($data)->find();
            $data['content'] = $content;
            $data['proposal'] = $proposal;
            if ($q_f) {
               $save_datas = Db::table('lbs_service_briefings')->where('id', $q_f['id'])->update($data);
            }else{
               $data['creat_time'] = date('Y-m-d H:i:s', time());
               $save_datas = Db::table('lbs_service_briefings')->insert($data);
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
