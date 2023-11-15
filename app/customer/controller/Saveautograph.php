<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use app\common\model\AutographV2;
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
                if(isset($_POST['employee01_signature'])) {
                    $data['staff_id01_url'] = conversionToImg($_POST['employee01_signature'], $staff_dir);
                }
                if(isset($_POST['employee02_signature'])) {
                    $data['staff_id02_url'] = conversionToImg($_POST['employee02_signature'], $staff_dir);
                }
                if(isset($_POST['employee03_signature'])) {
                    $data['staff_id03_url'] = conversionToImg($_POST['employee03_signature'], $staff_dir);
                }
                $imgPath = app()->getRootPath().'public'.$data['customer_signature_url'];
                $cmd = " /usr/bin/convert -resize 50%x50% -rotate -90 $imgPath  $imgPath 2>&1";
                @exec($cmd,$output,$return_val);
                if($return_val === 0){
                    $data['conversion_flag'] = 0;
                }
                $save_datas = $autographV2Model->where('id','=',$result['id'])->update($data);
            }else{
                $data['customer_signature_url'] = conversionToImg($_POST['customer_signature'],$customer_dir);
                if(isset($_POST['employee01_signature'])) {
                    $data['staff_id01_url'] = conversionToImg($_POST['employee01_signature'], $staff_dir);
                }
                if(isset($_POST['employee02_signature'])) {
                    $data['staff_id02_url'] = conversionToImg($_POST['employee02_signature'], $staff_dir);
                }
                if(isset($_POST['employee03_signature'])) {
                    $data['staff_id03_url'] = conversionToImg($_POST['employee03_signature'], $staff_dir);
                }
                $data['creat_time'] = date('Y-m-d H:i:s');
                $imgPath = app()->getRootPath().'public'.$data['customer_signature_url'];
                $cmd = " /usr/bin/convert -resize 50%x50% -rotate -90 $imgPath  $imgPath 2>&1";
                @exec($cmd,$output,$return_val);
                if($return_val === 0){
                    $data['conversion_flag'] = 0;
                }
                $save_datas = $autographV2Model->insert($data);
                if($data['job_type'] == 1){
                    $more_sign = $this->checkOrders($data['job_type'],$_POST['staffid']);
                    unset($_POST['job_id']);
                    $more_sign_data = [];
                    foreach ($more_sign as $k =>$v){
                        $more_sign_data[] =$data;
                        $more_sign_data[$k]['job_id'] =$v['JobID'];
                    }
                    $save_datas = $autographV2Model->insertAll($more_sign_data);
                }
            }
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

    /**
     * @param string $job_id 订单id
     * @param string $customer 员工id
     * @return array
     * */
    public function checkOrders($job_id = '',$customer = ''){
        //var_dump($job_id);var_dump($staffid);exit();
        if(empty($job_id)){
            return [];
        }
        //根据工作id查询出客户编号是多少
        $result = Db::table('joborder')->alias('j')->where('j.JobID',$job_id)->field('j.CustomerID,j.JobDate')->find();
        $where = [
            'j.JobDate' =>$result['JobDate'],
            'j.CustomerID' =>$result['CustomerID'],
            //   [],
        ];
        //->where('j.StartTime','<>', '')
        $more_sign =  Db::table('joborder')->alias('j')->where($where)->where('j.JobID','<>', $job_id)->where('j.StartTime','<>', '00:00:00')->field('j.JobID')->select()->toArray();
        // dd(Db::table('joborder')->getLastSql());
        return $more_sign;
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
