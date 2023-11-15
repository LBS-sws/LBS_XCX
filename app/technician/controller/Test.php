<?php

declare (strict_types = 1);

namespace app\technician\controller;

use app\common\model\AutographV2;

class Test{
    public function index(){
        $img = 'https://dss2.bdstatic.com/8_V1bjqh_Q23odCf/pacific/1990894160.png?x=0&y=0&h=150&w=242&vh=150.00&vw=242.00&oh=150.00&ow=242.00';
        $res = imagecreatefrom($img);
        var_dump($res);
    }

    public function updateStaffId01Url($date = '')
    {
        if(!empty($date)){
            $dir = date('Ymd', strtotime($date));
            $autographV2Model = new AutographV2();

            $autographs = $autographV2Model
                ->alias('a')
                ->leftJoin('joborder j', 'j.JobID = a.job_id')
                ->where('a.creat_time', '>=', $date.' 00:00:00')
                ->where('a.staff_id01_url', '')
                ->where('a.creat_time', '<=', $date.' 23:59:59')
                ->field('a.staff_id01_url,a.id, a.job_id, CONCAT(/signature/staff/".$dir."/", j.staff01, ".jpg") as staff_id01,a.staff_id01_url as og_01_staff, a.customer_signature_url')
                ->limit(50)
                ->select();

            foreach ($autographs as $autograph) {
                $autograph->staff_id01_url = $autograph->staff_id01;
                $autograph->save();
            }

            return 'Staff ID更新完成.';
        }else{
            exit("参数错误！");
        }

    }

}
