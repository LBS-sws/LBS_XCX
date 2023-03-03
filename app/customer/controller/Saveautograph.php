<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use app\technician\model\AutographV2;
use think\facade\Request;
use think\facade\Db;


class Saveautograph
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $data['job_id'] = $_POST['job_id'];
            $data['job_type'] = $_POST['job_type'];
            //查询是否存在
            /**
             * 得先到V2的表中查询一圈后，没有再查之前的表 【搁置】
             * */
            $autographV2Model =new AutographV2();
            $result = $autographV2Model->where($data)->find();
            //创建的日期
            $create_date = (date('Ymd', time()));
            $staff_dir = 'signature/staff/' . $create_date . '/';
            $customer_dir = 'signature/customer/' . $create_date . '/';
            if($result !== null) {
                //如果查出来不是空的那么这里就需要进行update  只需要更新客户的评分以及客户的签名即可
                $data['pid']=$result['pid']+1;
                $data['customer_signature_url'] = conversionToImg($_POST['customer_signature'],$customer_dir);
//                $data['staff_id01_url'] = conversionToImg($_POST['employee01_signature'], $staff_dir);
//                $data['staff_id02_url'] = conversionToImg($_POST['employee02_signature'], $staff_dir);
//                $data['staff_id03_url'] = conversionToImg($_POST['employee03_signature'], $staff_dir);
                $imgPath = app()->getRootPath().'public'.$data['customer_signature_url'];
                $cmd = " /usr/bin/convert -resize 50%x50% -rotate -90 $imgPath  $imgPath 2>&1";
                @exec($cmd,$output,$return_val);
                if($return_val === 0){
                    $data['conversion_flag'] = 0;
                }
                $data['customer_grade'] = $_POST['customer_grade'];
                $save_datas = $autographV2Model->where('id','=',$result['id'])->update($data);
            }else{
                $data['customer_signature_url'] = conversionToImg($_POST['customer_signature'],$customer_dir);
                $data['staff_id01_url'] = conversionToImg($_POST['employee01_signature'], $staff_dir);
                $data['staff_id02_url'] = conversionToImg($_POST['employee02_signature'], $staff_dir);
                $data['staff_id03_url'] = conversionToImg($_POST['employee03_signature'], $staff_dir);
                $data['customer_grade'] = $_POST['customer_grade'];
                $data['creat_time'] = date('Y-m-d H:i:s');

                $imgPath = app()->getRootPath().'public'.$data['customer_signature_url'];
                $cmd = " /usr/bin/convert -resize 50%x50% -rotate -90 $imgPath  $imgPath 2>&1";
                @exec($cmd,$output,$return_val);
                if($return_val === 0){
                    $data['conversion_flag'] = 0;
                }
                $save_datas = $autographV2Model->insert($data);
            }
            /**
             *
             * $q_f = Db::table('lbs_report_autograph')->where($data)->find();
            $data['employee01_signature'] = $_POST['employee01_signature'];
            $data['employee02_signature'] = $_POST['employee02_signature'];
            $data['employee03_signature'] = $_POST['employee03_signature'];
            $data['customer_signature'] = $_POST['customer_signature'];
            $data['customer_grade'] = $_POST['customer_grade'];
            if ($q_f) {
            $save_datas = Db::table('lbs_report_autograph')->where('id', $q_f['id'])->update($data);
            }else{
            $data['creat_time'] = date('Y-m-d H:i:s', time());
            $save_datas = Db::table('lbs_report_autograph')->insert($data);
            }
             * */




            if ($save_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '点评成功';
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
