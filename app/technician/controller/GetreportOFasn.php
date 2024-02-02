<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use app\common\model\AutographV2;
use think\facade\Db;
use think\facade\Request;


class GetreportOFasn
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和日期';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['job_id']) || !isset($_POST['job_type']) || !isset($_POST['city']) || !isset($_POST['service_type'])){
            return json($result);
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['job_id']) || empty($_POST['job_type']) || empty($_POST['city']) || empty($_POST['service_type'])){
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        $city = $_POST['city'];
        $service_type = $_POST['service_type'];

        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24 * 30)) {
            if ($job_type==1) {
                $report_datas['basic'] = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where('j.JobID',$job_id)->field('IF (
	j.StartTime >= j.FinishTime,
date_format( date_add( j.FinishDate, INTERVAL - 1 DAY ), "%Y-%m-%d" ),
date_format( j.FinishDate, "%Y-%m-%d" )) as startDate,j.JobID,j.CustomerName,j.Addr,j.ContactName,j.Mobile,j.JobDate,j.StartTime,j.FinishTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,j.Staff01 as jStaff01,j.Staff02 as jStaff02,j.Staff03 as jStaff03,s.ServiceName,j.Status,j.FinishDate')->find();
                $job_datas = Db::table('joborder')->where('JobID',$job_id)->find();

            }elseif($job_type==2){
                $report_datas['basic'] = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where('j.FollowUpID',$job_id)->field('IF (
	j.StartTime >= j.FinishTime,
date_format( date_add( j.JobDate, INTERVAL - 1 DAY ), "%Y-%m-%d" ),
date_format( j.JobDate, "%Y-%m-%d" )) as startDate,j.JobDate as FinishDate,j.FollowUpID as JobID,j.CustomerName,j.Addr,j.ContactName,j.Mobile,j.JobDate,j.StartTime,j.FinishTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,j.Staff01 as jStaff01,j.Staff02 as jStaff02,j.Staff03 as jStaff03,s.ServiceName,j.Status')->find();
                $job_datas = Db::table('followuporder')->where('FollowUpID',$job_id)->find();
            }
            //设备巡查
            $report_datas['basic']['equipments'] = '';
            $eq['e.job_id'] = $job_id;
            $eq['e.job_type'] = $job_type;
            $basic_equipments = Db::table('lbs_service_equipments')->alias('e')->join('lbs_service_equipment_type t','e.equipment_type_id=t.id','right')->field('t.name,e.equipment_type_id')->where($eq)->Distinct(true)->select();
            for ($i=0; $i < count($basic_equipments); $i++) {
                $n['job_id'] = $job_id;
                $n['job_type'] = $job_type;
                $n['equipment_type_id'] = $basic_equipments[$i]['equipment_type_id'];
                $numbers = Db::table('lbs_service_equipments')->where($n)->count();
                if ($report_datas['basic']['equipments'] == '') {
                    $report_datas['basic']['equipments'] = $basic_equipments[$i]['name'].'-'.$numbers;
                }else{
                    $report_datas['basic']['equipments'] =$report_datas['basic']['equipments'].','.$basic_equipments[$i]['name'].'-'.$numbers;
                }
            }
            //服务项目
            $service_projects = '';
            if($job_type==1 && $service_type==1){//洁净
                if ($job_datas["Item01"] > 0) $service_projects .= "坐厕：".$job_datas["Item01"].",";
                if ($job_datas["Item02"] > 0) $service_projects .= "尿缸：".$job_datas["Item02"].",";
                if ($job_datas["Item03"] > 0) $service_projects .= "洗手盆：".$job_datas["Item03"].",";
                if ($job_datas["Item11"] > 0) $service_projects .= "洗手间：".$job_datas["Item11"]." ".$job_datas["Item11Rmk"] . ",";
                if ($job_datas["Item04"] > 0) $service_projects .= "电动清新机：".$job_datas["Item04"]. " ".$job_datas["Item04Rmk"] . ",";
                if ($job_datas["Item05"] > 0) $service_projects .= "皂液机：".$job_datas["Item05"]." ".$job_datas["Item05Rmk"] . ",";
                if ($job_datas["Item06"] > 0) $service_projects .= "水剂喷机：".$job_datas["Item06"]." ".$job_datas["Item06Rmk"] . ",";
                if ($job_datas["Item07"] > 0) $service_projects .= "压缩罐喷机：".$job_datas["Item07"]." ".$job_datas["Item07Rmk"] . ",";
                if ($job_datas["Item08"] > 0) $service_projects .= "尿缸自动消毒器：".$job_datas["Item08"]." ".$job_datas["Item08Rmk"] . ",";
                if ($job_datas["Item09"] > 0) $service_projects .= "厕纸机：".$job_datas["Item09"]." ".$job_datas["Item09Rmk"] . ",";
                if ($job_datas["Item10"] > 0) $service_projects .= "抹手纸：".$job_datas["Item10"]." ".$job_datas["Item10Rmk"] . ",";
                if ($job_datas["Item13"] > 0) $service_projects .= "GOJO机：".$job_datas["Item13"]." ".$job_datas["Item13Rmk"] . ",";
                if ($job_datas["Item12"] > 0) $service_projects .= "其他：".$job_datas["Item12"]." ".$job_datas["Item12Rmk"] . ",";
            }else if($job_type==1 && $service_type==2){//灭虫
                if ($job_datas["Item01"] > 0) $service_projects .= "老鼠,";
                if ($job_datas["Item02"] > 0) $service_projects .= "蟑螂,";
                if ($job_datas["Item03"] > 0) $service_projects .= "蚁,";
                if ($job_datas["Item04"] > 0) $service_projects .= "果蝇,";
                if ($job_datas["Item09"] > 0) $service_projects .= "苍蝇,";
                if ($job_datas["Item06"] > 0) $service_projects .= "水剂喷机：".$job_datas["Item06"]." ".$job_datas["Item06Rmk"] . ",";
                if ($job_datas["Item07"] > 0) $service_projects .= "罐装灭虫喷机：".$job_datas["Item07"]." ".$job_datas["Item07Rmk"] . ",";
                if ($job_datas["Item10"] > 0) $service_projects .= "灭蝇灯：".$job_datas["Item10"]." ".$job_datas["Item10Rmk"] . ",";
                if ($job_datas["Item08"] > 0) $service_projects .= "其他：".$job_datas["Item08"]." ".$job_datas["Item08Rmk"] . ",";
            }else if($job_type==1 && $service_type==3){//灭虫喷焗
                if ($job_datas["Item01"] > 0) $service_projects .= "蚊子,";
                if ($job_datas["Item02"] > 0) $service_projects .= "苍蝇,";
                if ($job_datas["Item03"] > 0) $service_projects .= "蟑螂,";
                if ($job_datas["Item04"] > 0) $service_projects .= "跳蚤,";
                if ($job_datas["Item05"] > 0) $service_projects .= "蛀虫,";
                if ($job_datas["Item06"] > 0) $service_projects .= "白蚁,";
                if ($job_datas["Item07"] > 0) $service_projects .= "其他：".$job_datas["Item07Rmk"] . ",";
            }else if($job_type==1 && $service_type==4){//租机服务
                if ($job_datas["Item01"] > 0) $service_projects .= "白蚁,";
                if ($job_datas["Item02"] > 0) $service_projects .= "跳蚤,";
                if ($job_datas["Item03"] > 0) $service_projects .= "螨虫,";
                if ($job_datas["Item04"] > 0) $service_projects .= "臭虫,";
                if ($job_datas["Item05"] > 0) $service_projects .= "滞留,";
                if ($job_datas["Item06"] > 0) $service_projects .= "焗雾,";
                if ($job_datas["Item07"] > 0) $service_projects .= "勾枪,";
                if ($job_datas["Item08"] > 0) $service_projects .= "空间消毒,";
                if ($job_datas["Item09"] > 0) $service_projects .= "其他：".$job_datas["Item09Rmk"] . ",";
            }
            $report_datas['basic']['service_projects'] = $service_projects;

            //briefing
            $w['job_id'] = $job_id;
            $w['job_type'] = $job_type;
            $briefing = Db::table('lbs_service_briefings')->where($w)->field('content,proposal')->find();
            $report_datas['briefing'] = $briefing;

            //material
            $report_datas['material'] = Db::table('lbs_service_materials')->where($w)->select();

            //risk
            $report_datas['risk'] = Db::table('lbs_service_risks')->where($w)->select();

            //equipment
            $equipmenthz_datas = [];
            $equipment_type_ids = Db::table('lbs_service_equipments')->where($w)->where('equipment_type_id','<>',245)->group('equipment_type_id')->field('equipment_type_id')->select();
            for ($i=0; $i < count($equipment_type_ids); $i++) {
                $equipmenthz_allcount = Db::table('lbs_service_equipments')->where($w)->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])->count();
                $equipmenthz_count = Db::table('lbs_service_equipments')->where($w)->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])->whereNotNull('equipment_area')->whereNotNull('check_datas')->count();
                $equipment_type = Db::table('lbs_service_equipment_type')->where('id',$equipment_type_ids[$i]['equipment_type_id'])->field('name')->find();
                $equipmenthz_datas[$i]['title'] = $equipment_type['name']."(".$equipmenthz_count."/".$equipmenthz_allcount.")";

                $check_datas = Db::table('lbs_service_equipments')->where($w)->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])->whereNotNull('equipment_area')->whereNotNull('check_datas')->order('id', 'asc')->select();

                if ($check_datas) {
                    $check_datas = $check_datas->toArray();
                    // 获取number字段的值
                    $numbers = array_column($check_datas, 'number');

                    // 自然排序
                    natsort($numbers);

                    $sorted_check_datas = [];
                    foreach ($numbers as $number) {
                        foreach ($check_datas as $data) {
                            if ($data['number'] === $number) {
                                $sorted_check_datas[] = $data;
                                break;
                            }
                        }
                    }
                    $check_datas = $sorted_check_datas;
                    for($j=0; $j < count($check_datas); $j++){
                        $check_data = json_decode($check_datas[$j]['check_datas'],true);

                        $equipmenthz_datas[$i]['table_title'][0] = '编号';
                        $equipmenthz_datas[$i]['content'][$j][0] = $check_datas[$j]['equipment_number'].sprintf('%02s', $check_datas[$j]['number']);
                        $equipmenthz_datas[$i]['table_title'][1] = '区域';
                        $equipmenthz_datas[$i]['content'][$j][1] = $check_datas[$j]['equipment_area'];
                        for ($m=0; $m < count($check_data); $m++) {
                            $equipmenthz_datas[$i]['table_title'][$m+2] = $check_data[$m]['label'];
                            $equipmenthz_datas[$i]['content'][$j][$m+2] = $check_data[$m]['value'];
                        }

                    }
                }
            }




            // if(!empty($equipmenthz_datas)){
            //     foreach ($equipmenthz_datas as $key=>$item){
            //         if(!empty($item['content'])){
            //             $content =[];
            //             foreach ($item['content'] as $k=>$v){
            //                 $content[$v[0]] = $v;
            //             }
            //             ksort($content);
            //             $content =array_values($content);
            //             $equipmenthz_datas[$key]['content'] = $content;
            //         }
            //     }
            // }



            $report_datas['equipment'] = $equipmenthz_datas;

            //photo
            $report_datas['photo'] = Db::table('lbs_service_photos')->where($w)->select();

            //autograph
//            $report_datas['autograph'] = Db::table('lbs_report_autograph')->where($w)->find();
            $autographModel =new AutographV2();
            $autograph= $autographModel->where($w)->append(['score'])->find();
            //获取当前域名
            $sign_url = Request::instance()->domain();
            $report_datas['autograph'] = $autograph;
            $report_datas['autograph']['employee01_signature'] = !empty($autograph['staff_id01_url'])?$sign_url.$autograph['staff_id01_url']:'';
            $report_datas['autograph']['employee02_signature'] = !empty($autograph['staff_id02_url'])?$sign_url.$autograph['staff_id02_url']:'';
            $report_datas['autograph']['employee03_signature'] = !empty($autograph['staff_id03_url'])?$sign_url.$autograph['staff_id03_url']:'';
            $report_datas['autograph']['customer_signature'] = !empty($autograph['customer_signature_url'])?$sign_url.$autograph['customer_signature_url']:'';
            $report_datas['autograph']['customer_signature_add'] = !empty($autograph['customer_signature_url_add'])?$sign_url.$autograph['customer_signature_url_add']:'';
            if(empty($autograph)){
                $employee_signature = Db::table('lbs_service_employee_signature')->where('staffid',$report_datas['basic']['jStaff01'])->find();

                $report_datas['autograph']['employee01_signature'] = $employee_signature['signature'];
                $report_datas['autograph']['employee02_signature'] ='';
                $report_datas['autograph']['employee03_signature'] ='';
                if ($report_datas['basic']['jStaff02']) {
                    $employee_signature = Db::table('lbs_service_employee_signature')->where('staffid',$report_datas['basic']['jStaff02'])->find();
                    $report_datas['autograph']['employee02_signature'] = $employee_signature['signature'];
                }
                if ($report_datas['basic']['jStaff03']) {
                    $employee_signature = Db::table('lbs_service_employee_signature')->where('staffid',$report_datas['basic']['jStaff03'])->find();
                    $report_datas['autograph']['employee03_signature'] = $employee_signature['signature'];
                }
            }

            //点评问卷
            $questionJsonData = (new Evaluates)->getAnswer()->getData();
            $report_datas['question'] = $questionJsonData['data'];

            //查询服务板块
            $service_sections = Db::table('lbs_service_reportsections')->where('city',$city)->where('service_type',$service_type)->find();
            if($service_sections){
                $report_datas['service_sections'] = explode(',',$service_sections['section_ids']);
            }else{
                $report_datas['service_sections'] = '';
            }
            if ($report_datas) {
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = $report_datas;
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
