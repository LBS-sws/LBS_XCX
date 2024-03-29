<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;


class Getequipmentsbyid
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['id'])  || !isset($_POST['city'])){
            return json($result);
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['id']) || empty($_POST['city'])){
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $city = $_POST['city'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            $ids = explode(',',$_POST['id']);
//            $data['e.id'] = ['in',$ids];
            $data['e.job_id'] = $_POST['job_id'];
            $data['e.job_type'] = $_POST['job_type'];

            $equipment_datas['eq'] = Db::table('lbs_service_equipments')->alias('e')
                ->join('lbs_service_equipment_type t','e.equipment_type_id=t.id','right')
                ->where($data)
                ->whereIn('e.id',$ids)
                ->field('e.*,t.type,t.check_targt,t.check_handles,t.id as tid')
                ->select();
            $idsCount = count($ids);
            //检查数据
            foreach ($equipment_datas['eq'] as $key=>$item){
                if($idsCount > 1) $item['equipment_area'] = null;
                $equipment_datas['eq'][$key] = $this->checkDatas($item);
            }
//            print_r($result);exit;

            $equipment_datas['use_areas'] = Db::table('lbs_service_use_areas')
                ->where('city',$city)
                ->where('area_type','equipment')
                ->field('use_area as label,use_area as value')
                ->select();
            if ($equipment_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '保存成功';
                $result['data'] = $equipment_datas;
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

    public function checkDatas($data)
    {
        if($data['check_datas']==null){
            $check_datas = [];
            $targets = $data['check_targt']?explode(',',$data['check_targt']):[];
            if ($data['type']==1) {
                for ($j=0; $j < count($targets); $j++) {
                    $check_datas[$j]['label'] =  $targets[$j];
                    $check_datas[$j]['value'] =  0;
                }
            }elseif($data['type']==2){
                for ($j=0; $j < count($targets); $j++) {
                    $check_datas[$j]['label'] =  $targets[$j];
                    $cd['check_targt'] = $j;
                    $cd['equipment_type_id'] = $data['tid'];
                    $cd_value = Db::table('lbs_service_equipment_type_selects')->where($cd)->find();
                    $selects =  isset($cd_value['check_selects'])?explode(',',$cd_value['check_selects']):[];
                    $g_s =array();
                    for ($m=0; $m < count($selects); $m++) {
                        $g_s[$m]['label'] = $selects[$m];
                        $g_s[$m]['value'] = $selects[$m];
                    }
                    $check_datas[$j]['selects'] =  $g_s;
                    $check_datas[$j]['value'] =  '';
                }
            }

            $data['check_datas'] = $check_datas;
        }else{
            $data['check_datas'] = json_decode($data['check_datas'],true);
        }

        if($data['check_handles']){
            $check_handles = [];
            $check_handle = $data['check_handles']?explode(',',$data['check_handles']):[];
            for ($j=0; $j < count($check_handle); $j++) {
                $check_handles[$j]['label'] =  $check_handle[$j];
                $check_handles[$j]['value'] = $check_handle[$j];
            }
            $data['check_handles'] = $check_handles;
        }

        return $data;
    }
}
