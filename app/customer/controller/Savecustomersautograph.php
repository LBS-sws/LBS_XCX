<?php
declare (strict_types=1);

namespace app\customer\controller;

use app\BaseController;
use app\technician\model\AutographV2;
use think\facade\Request;
use think\facade\Db;


class Savecustomersautograph
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入';
        $result['data'] = null;

        $token = request()->header('token');
        if (!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['customer_signature'])) {
            return json($result);
        }
        if (empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['customer_signature'])) {
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID', $staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time) / 60 / 60;
        //验证登录状态
        if ($token == $user_token['token'] && ($c_time <= 24)) {
            $data['job_id'] = $_POST['job_id'];
            $data['job_type'] = $_POST['job_type'];
            //查询是否存在
           /* $q_f = Db::table('lbs_report_autograph')->where($data)->find();
            $data['customer_signature'] = $_POST['customer_signature'];
            if ($q_f) {
                $save_datas = Db::table('lbs_report_autograph')->where('id', $q_f['id'])->update($data);
            } else {
                $data['creat_time'] = date('Y-m-d H:i:s', time());
                $save_datas = Db::table('lbs_report_autograph')->insert($data);
            }*/
            /**
             * 得先到V2的表中查询一圈后，没有再查之前的表 【搁置】
             * */
            $autographV2Model = new AutographV2();
            $result = $autographV2Model->where($data)->find();
            //创建的日期
            $create_date = (date('Ymd', time()));
            $staff_dir = 'signature/staff/' . $create_date . '/';
            $customer_dir = 'signature/customer/' . $create_date . '/';
            if ($result !== null) {
                //如果查出来不是空的那么这里就需要进行update  只需要更新客户的评分以及客户的签名即可
                $data['customer_signature_url'] = conversionToImg($_POST['customer_signature'], $customer_dir);
                $save_datas = $autographV2Model->save($data);
            } else {
                $data['creat_time'] = date('Y-m-d H:i:s');
                $save_datas = $autographV2Model->insert($data);
            }
            if ($save_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '点评成功';
                $result['data'] = $save_datas;
            } else {
                $result['code'] = 1;
                $result['msg'] = '成功，无数据';
                $result['data'] = null;
            }
        } else {
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }
}
