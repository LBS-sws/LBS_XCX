<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;
use think\cache\driver\Redis;


class Saveequipment
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['id']) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['equipment_name']) || !isset($_POST['equipment_area']) || !isset($_POST['check_datas']) ){
            return json($result);
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['id']) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['equipment_name']) || empty($_POST['equipment_area']) || empty($_POST['check_datas'])){
            return json($result);
        }

        //获取信息
        $staffid = $_POST['staffid'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {

            $data['equipment_name'] = $_POST['equipment_name'];

            $data['check_datas'] = is_string($_POST['check_datas']) ? $_POST['check_datas'] : json_encode($_POST['check_datas'],JSON_UNESCAPED_UNICODE);
            $data['site_photos'] = is_string($_POST['site_photos']) ? $_POST['site_photos'] : json_encode($_POST['site_photos'],JSON_UNESCAPED_UNICODE);
            $data['check_handle'] = isset($_POST['check_handle']) ? $_POST['check_handle'] : null;
            $data['more_info'] = $_POST['more_info'];

            $ids = explode(',',$_POST['id']);
            if(count($ids) == 1){
                $data['equipment_area'] = $_POST['equipment_area'];
                $save_datas = Db::table('lbs_service_equipments')->whereIn('id', $ids)->update($data);
            }else{
                foreach ($ids as $key=>$item){
                    $equipment_area = Db::table('lbs_service_equipments')->where('id', $item)->value('equipment_area');
                    $data['equipment_area'] = $equipment_area && $equipment_area != 'null' ? $equipment_area : $_POST['equipment_area'];
                    $save_datas = Db::table('lbs_service_equipments')->where('id', $item)->update($data);
                }
            }


            $redis = new Redis();
            if ($save_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = $save_datas;
            }else{
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = null;
            }
            $lock_key = 'lock_equipment_'.$_POST['id'];
            $lock_content = $redis->has($lock_key);
            if($lock_content){
                $redis->delete($lock_key);
            }
        }else{
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }
}
