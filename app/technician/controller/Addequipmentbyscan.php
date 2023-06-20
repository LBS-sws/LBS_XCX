<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Addequipmentbyscan
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['city']) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['scan_code'])){
            return json($result);
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['city']) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['scan_code'])){
            return json($result);
        }
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$_POST['staffid'])->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24 * 30)) {

            //查询设备
			$n_where['n.equipment_number']= $_POST['scan_code'];
			$n_where['t.city']= $_POST['city'];
            $e_type = Db::table('lbs_service_equipment_numbers')->alias('n')->join('lbs_service_equipment_type t','n.eq_type_id=t.id','right')->field('n.*')->where($n_where)->find();
            if(!is_null($e_type)){
				if($e_type['status'] == '0' || $e_type['status'] == NULL){
					$is['equipment_number'] = $_POST['scan_code'];
					$is['job_id'] = $_POST['job_id'];
					$is['job_type'] = $_POST['job_type'];
					$equipment_is = Db::table('lbs_service_equipments')->where($is)->find();
					if(empty($equipment_is)){
						$up_da_status = $_POST['job_type'].','.$_POST['job_id'];
						$up_eqn = Db::table('lbs_service_equipment_numbers')->where('id', $e_type['id'])->update(['status' => $up_da_status]);
					    $data['equipment_type_id'] = $e_type['eq_type_id'];
					    $data['equipment_name'] = $e_type['name'];
					    $data['equipment_number'] = $e_type['equipment_number'];
					    $data['job_id'] = $_POST['job_id'];
					    $data['job_type'] = $_POST['job_type'];
					    $data['creat_time'] = date('Y-m-d H:i:s', time());
					    $id = Db::table('lbs_service_equipments')->insertGetId($data);
					}else{
					    $id = $equipment_is['id'];
					}
					if ($id) {
					    //返回数据
					    $result['code'] = 1;
					    $result['msg'] = '保存成功';
					    $result['data'] = $id;
					}else{
					    $result['code'] = 1;
					    $result['msg'] = '成功，无数据';
					    $result['data'] = null;
					}
				}else{
					$result['code'] = 1;
					$result['msg'] = '设备编号已被使用';
					$result['data'] = null;
				}
            }else{
                $result['code'] = 1;
                $result['msg'] = '设备编号不存在';
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
