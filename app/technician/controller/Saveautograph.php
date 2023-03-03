<?php
declare (strict_types = 1);

namespace app\technician\controller;
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
        $is_grade = isset($_REQUEST['is_grade'])?$_REQUEST['is_grade']:1;

        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $data['job_id'] = $_POST['job_id'];
            $data['job_type'] = $_POST['job_type'];
            /**
             * 得先到V2的表中查询一圈后，没有再查之前的表 【搁置】
             * */
            $autographV2Model =new AutographV2();
            $result = $autographV2Model->where($data)->find();
            //创建的日期
            $create_date = (date('Ymd', time()));
            $staff_dir = 'signature/staff/' . $create_date . '/';
            $customer_dir = 'signature/customer/' . $create_date . '/';
            $customer_file_name= $data['job_id'].'_'.$data['job_type'].'_'.$staffid;
            $staff_file_name= $staffid;
            $data['customer_signature_url'] = conversionToImg($_POST['customer_signature'],$customer_dir,$customer_file_name);
            if($result !== null) {
                //如果查出来不是空的那么这里就需要进行update  只需要更新客户的评分以及客户的签名即可
                $data['pid']=$result['pid']+1;
//                $data['staff_id01_url'] = conversionToImg($_POST['employee01_signature'], $staff_dir);
//                $data['staff_id02_url'] = conversionToImg($_POST['employee02_signature'], $staff_dir);
//                $data['staff_id03_url'] = conversionToImg($_POST['employee03_signature'], $staff_dir);
//                $data['customer_grade'] = $_POST['customer_grade'];
                if($is_grade != 0){
                    $data['customer_grade'] = $_POST['customer_grade'];
                }
                $imgPath = app()->getRootPath().'public'.$data['customer_signature_url'];
                $cmd = " /usr/bin/convert -resize 50%x50% -rotate -90 $imgPath  $imgPath 2>&1";
                @exec($cmd,$output,$return_val);
                if($return_val === 0){
                    $data['conversion_flag'] = 0;
                }
                $save_datas = $autographV2Model->where('id','=',$result['id'])->update($data);
            }else{
                $data['staff_id01_url'] = conversionToImg($_POST['employee01_signature'], $staff_dir,$staff_file_name);
                $data['staff_id02_url'] = conversionToImg($_POST['employee02_signature'], $staff_dir);
                $data['staff_id03_url'] = conversionToImg($_POST['employee03_signature'], $staff_dir);
//                $data['customer_grade'] = $_POST['customer_grade'];
                if($is_grade != 0){
                    $data['customer_grade'] = $_POST['customer_grade'];
                }
                $data['creat_time'] = date('Y-m-d H:i:s');
                $imgPath = app()->getRootPath().'public'.$data['customer_signature_url'];
                $cmd = " /usr/bin/convert -resize 50%x50% -rotate -90 $imgPath  $imgPath 2>&1";
                @exec($cmd,$output,$return_val);
                if($return_val === 0){
                    $data['conversion_flag'] = 0;
                }
                $save_datas = $autographV2Model->insert($data);
            }
            if ($save_datas) {
                //返回数据
                if($is_grade != 0){
                    $result['code'] = 1;
                    $result['msg'] = '【史伟莎】感谢您的点评';
                    $result['data'] = $save_datas;
                }else{
                    $result['code'] = 1;
                    $result['msg'] = '完成签名';
                    $result['data'] = $save_datas;
                }
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

    public function getStaffAutograph(){
        //autograph
        try{

            $data['job_id'] = $_REQUEST['job_id'];
            $data['job_type'] = $_REQUEST['job_type'];

            if ($data['job_type']==1) {
                $result['basic'] = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where('j.JobID',$data['job_id'])->field('j.Staff01 as jStaff01,j.Staff02 as jStaff02,j.Staff03 as jStaff03')->find();

            }elseif($data['job_type']==2){
                $result['basic'] = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where('j.FollowUpID',$data['job_id'])->field('j.Staff01 as jStaff01,j.Staff02 as jStaff02,j.Staff03 as jStaff03')->find();
            }

            $autographV2Model =new AutographV2();
            $result['autograph'] = $autographV2Model->where($data)->find();
            if(!isset($result['autograph']['employee01_signature']) || !isset($result['autograph']['employee02_signature']) || !isset($result['autograph']['employee03_signature'])){
                $employee_signature = Db::table('lbs_service_employee_signature')->where('staffid',$result['basic']['jStaff01'])->find();

                $result['autograph']['employee01_signature'] = $employee_signature['signature'];
                $result['autograph']['employee02_signature'] ='';
                $result['autograph']['employee03_signature'] ='';
                if ($result['basic']['jStaff02']) {
                    $employee_signature = Db::table('lbs_service_employee_signature')->where('staffid',$result['basic']['jStaff02'])->find();
                    $result['autograph']['employee02_signature'] = $employee_signature['signature'];
                }
                if ($result['basic']['jStaff03']) {
                    $employee_signature = Db::table('lbs_service_employee_signature')->where('staffid',$result['basic']['jStaff03'])->find();
                    $result['autograph']['employee03_signature'] = $employee_signature['signature'];
                }
            }
            return success(0,'ok',$result);
        }catch (\Exception $exception){
            return error(-1,$exception->getMessage(),[]);
        }

    }
}
