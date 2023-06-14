<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;

class Getshortcuts
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和类型';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['city']) || !isset($_POST['shortcut_type']) || !isset($_POST['service_type'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['city']) || empty($_POST['shortcut_type']) || empty($_POST['service_type'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $city = $_POST['city'];
        $shortcut_type = $_POST['shortcut_type'];
        $service_type = $_POST['service_type'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            //获取数据
            $wheres['s.city'] = $city;
            $wheres['s.shortcut_type'] = $shortcut_type;
            $wheres['s.service_type'] = $service_type;
            
             //search_key 开始
            $wheres_search = [];
            if(isset($_POST['search_key']) && $_POST['search_key'] != ''){
                $wheres_search =  [['c.content', 'like', "%{$_POST['search_key']}%"]];
            }
            //search_key 结束
            
            $shortcut_datas = Db::table('lbs_service_shortcuts')->alias('s')->join('lbs_service_shortcut_contents c','s.id=c.shortcut_id')->where($wheres)->where($wheres_search)->field('c.content as value,c.content as label')->select();
             if ($shortcut_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = $shortcut_datas;
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