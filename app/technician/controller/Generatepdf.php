<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use app\common\model\AutographV2;
use app\common\model\CustomerDeviceModel;
use app\technician\model\AnalyseReport;
use app\technician\model\CustomerCompany;
use beyong\echarts\charts\Pie;
use beyong\echarts\ECharts;
use beyong\echarts\Option;
use think\facade\Db;
use think\facade\Request;
use TCPDF;


class Generatepdf
{
    public $custType = 250;


    public function index(){
        $data = event('CreatePDF',$_POST);
        return success(0, 'success', $data);
    }
    public function index_bak(){
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、令牌和日期';
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
        $job_id = $_POST['job_id'];
        $job_type = $_POST['job_type'];
        //获取用户登录信息
        $user_token = Db::name('token')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24*30)) {
            if ($job_type==1) {
                $report_datas['basic'] = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where('j.JobID',$job_id)->field('j.JobID,j.CustomerID,j.CustomerName,j.Addr,j.ContactName,j.Mobile,j.JobDate,j.StartTime,j.FinishTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,s.ServiceName,j.Status,j.City,j.ServiceType,j.FirstJob,j.FinishDate')->find();
                $job_datas = Db::table('joborder')->where('JobID',$job_id)->find();

            }elseif($job_type==2){
                $report_datas['basic'] = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('staff uo','j.Staff02=uo.StaffID','left')->join('staff ut','j.Staff03=ut.StaffID','left')->where('j.FollowUpID',$job_id)->field('j.FollowUpID as JobID,j.CustomerID,j.CustomerName,j.Addr,j.ContactName,j.Mobile,j.JobDate,j.StartTime,j.FinishTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,s.ServiceName,j.Status,j.City,s.ServiceType')->find();
                $job_datas = Db::table('followuporder')->where('FollowUpID',$job_id)->find();
                $report_datas['basic']['FinishDate'] = $report_datas['basic']['JobDate'];
            }
            //城市和服务类型
            $office = Db::name('enums')->alias('e')->join('officecity o ','o.Office=e.EnumID ')->where('o.City', $report_datas['basic']['City'])->where('e.EnumType', 8)->find();
            $city = $office['Text'];
            $service_type = $report_datas['basic']['ServiceType'];
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
            $report_datas['basic']['Staffall'] = $report_datas['basic']['Staff01'].($report_datas['basic']['Staff02']?','.$report_datas['basic']['Staff02']:'').($report_datas['basic']['Staff03']?','.$report_datas['basic']['Staff03']:'');
            if ($job_type==1) {
                if($report_datas['basic']['FirstJob']==1){
                    $report_datas['basic']['task_type'] = "首次服务";
                }else{
                    $report_datas['basic']['task_type'] = "常规服务";
                }
            }else{
                $report_datas['basic']['task_type'] = "跟进服务";
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
            $equipment_type_ids = Db::table('lbs_service_equipments')->where($w)->group('equipment_type_id')->field('equipment_type_id')->select();

            for ($i=0; $i < count($equipment_type_ids); $i++) {
                $equipmenthz_allcount = Db::table('lbs_service_equipments')->where($w)->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])->count();
                $equipmenthz_count = Db::table('lbs_service_equipments')
                    ->where($w)
                    ->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])
                    ->whereNotNull('equipment_area')
                    ->whereNotNull('check_datas')
                    ->count();
                $equipment_type = Db::table('lbs_service_equipment_type')->where('id',$equipment_type_ids[$i]['equipment_type_id'])->field('name')->find();
                $equipmenthz_datas[$i]['title'] = $equipment_type['name']."(".$equipmenthz_count."/".$equipmenthz_allcount.")";
                $check_datas = Db::table('lbs_service_equipments')->where($w)
                    ->where('equipment_type_id',$equipment_type_ids[$i]['equipment_type_id'])
                    ->whereNotNull('equipment_area')
                    ->whereNotNull('check_datas')
                    ->order('id', 'asc')
                    ->select();
                if ($check_datas) {
                    for($j=0; $j < count($check_datas); $j++){
                        $check_data = json_decode($check_datas[$j]['check_datas'],true);

                        $equipmenthz_datas[$i]['table_title'][0] = '序号';
                        $equipmenthz_datas[$i]['content'][$j][0] = sprintf('%02s', $j+1);
                        $equipmenthz_datas[$i]['table_title'][1] = '编号';
                        $equipmenthz_datas[$i]['content'][$j][1] = $check_datas[$j]['equipment_number'];
                        $equipmenthz_datas[$i]['table_title'][1] = '区域';
                        $equipmenthz_datas[$i]['content'][$j][1] = $check_datas[$j]['equipment_area'];
                        for ($m=0; $m < count($check_data); $m++) {
                            $equipmenthz_datas[$i]['table_title'][$m+2] = $check_data[$m]['label'];
                            $equipmenthz_datas[$i]['content'][$j][$m+2] = $check_data[$m]['value'];
                        }
                        $equipmenthz_datas[$i]['table_title'][$m+2] = '检查与处理';
                        $equipmenthz_datas[$i]['content'][$j][$m+2] = $check_datas[$j]['check_handle'];
                        $equipmenthz_datas[$i]['table_title'][$m+3] = '补充说明';
                        $equipmenthz_datas[$i]['content'][$j][$m+3] = $check_datas[$j]['more_info'];
                        $equipmenthz_datas[$i]['site_photos'][$j] = $check_datas[$j]['site_photos'];
                    }
                }
            }
            $report_datas['equipment'] = $equipmenthz_datas;

            $Smarttech_list = $this->createSmarttechHtml($job_datas['CustomerID']);

            //photo
            //TODO 将类型为250的图片取10组
            $photo_num = 4;
            $customerCompanyModel = new CustomerCompany();
            if(isset($report_datas['basic']['CustomerID'])) {
                $cust_type = $customerCompanyModel->field('CustomerType')->where('CustomerID','=',$report_datas['basic']['CustomerID'])->findOrEmpty();
                if(isset($cust_type) && $cust_type->CustomerType == $this->custType){
                    $photo_num = 50;
                }
            }
            $report_datas['photo'] = Db::table('lbs_service_photos')->where($w)->limit($photo_num)->select();
            //先查询lbs_report_autograph中是否有相关数据。
            $autographModel =new AutographV2();
            $autographV2 = $autographModel->where($w)->append(['score'])->find();
            if($autographV2 !== null){
//                查出来不为空走查询图片路径的路径。
                $autograph_flag = 1;
                $autograph_data = $autographV2->toArray();

            }else{
//                否则取之前表里边的值
                $autograph_flag = 0;
                //autograph
                $report_datas['autograph']['employee01_signature'] = '';
                $report_datas['autograph']['employee02_signature'] = '';
                $report_datas['autograph']['employee03_signature'] = '';
                $report_datas['autograph']['customer_signature'] = '';
                $report_datas['autograph']['customer_grade'] = '';

            }

            //查询服务板块
            $service_sections = Db::table('lbs_service_reportsections')->where('city',$city)->where('service_type',$service_type)->find();
            if($service_sections){
                $report_datas['service_sections'] = explode(',',$service_sections['section_ids']);
            }else{
                $report_datas['service_sections'] = '';
            }
            $baseUrl_imgs = "../public";


            $company_img = "../public/pdf/company/".$city.".jpg";
            //pdf生成
            $html = <<<EOF
<!DOCTYPE html>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <title>史伟莎服务现场管理报告</title>
            <style>
            body{
                padding: 0;
                font-family: STFangsong;
            }
            .myTable {
                height: 300px;
                width: 100%;
                font-family:STFangsong;
            }
            .myTitle {
                background-color: #eeeeee;
                font-size: 17px;
                font-weight: bold;
            }
            tr:hover {
                background: #edffcf;
            }
            th {
                font-size: 17px;
                font-weight: bold;
            }
            td {
                font-size: 16px;
            }
            th,td {
                border: solid 1px #eeeeee;
                text-align: center;
            }
            p{
                font-size: 18px;
                line-height:10px;
            }
            </style>
            <body style="height: 100%;">
            <table class="myTable" cellpadding="5">
                <tr style="border: none;border-top: none;border-right:none;border-left:none;">
                    <td width="25%" style="float:left;border: none;border-top: none;">
                        <img src="../public/pdf/logo.png" width="60" height="70">
                    </td>
                    <td  align="center" width="50%" style="float:left;border: none;border-top: none;">
                        <p style="font-size: 20px;line-height:15px;">史伟莎服务现场管理报告</p>
                    </td>
                </tr>
                <tr class="myTitle">
                    <th  width="100%"  style="text-align:left" align="left">基础信息</th>
                </tr>
                <tr>
                    <td width="15%">客户名称</td>
                    <td width="35%" align="left">{$report_datas['basic']['CustomerName']}</td>
                    <td width="15%">服务日期</td>
                    <td width="35%" align="left">{$report_datas['basic']['JobDate']}</td>
                </tr>
                <tr>
                    <td width="15%">客户地址</td>
                    <td width="85%" align="left">{$report_datas['basic']['Addr']}</td>
                   
                </tr>
                <tr>
                    <td width="15%">服务类型</td>
                    <td width="35%" align="left">{$report_datas['basic']['ServiceName']}</td>
                    <td width="15%">服务项目</td>
                    <td width="35%" align="left">{$report_datas['basic']['service_projects']}</td>
                </tr>
                <tr>
                    <td width="15%">联系人员</td>
                    <td width="35%" align="left">{$report_datas['basic']['ContactName']}</td>
                    <td width="15%">联系电话</td>
                    <td width="35%" align="left">{$report_datas['basic']['Mobile']}</td>
                </tr>
                <tr>
                    <td width="15%">任务类型</td>
                    <td width="35%" align="left">{$report_datas['basic']['task_type']}</td>
                    <td width="15%">服务人员</td>
                    <td width="35%" align="left">{$report_datas['basic']['Staffall']}</td>
                </tr>
                <tr>
                    <td width="15%">监测设备</td>
                    <td width="85%" align="left">{$report_datas['basic']['equipments']}</td>
                </tr>
EOF;
            if($report_datas['briefing']!=''){
                if(($report_datas['service_sections']!='' && in_array('1',$report_datas['service_sections'])) || $report_datas['service_sections']==''){
                    $bc = $city=='MO' ? $report_datas['briefing']['content'] : mb_convert_encoding(mb_convert_encoding($report_datas['briefing']['content'], 'GB2312', 'UTF-8'), 'UTF-8', 'GB2312');
                    $bp = $city=='MO' ? $report_datas['briefing']['proposal'] : mb_convert_encoding(mb_convert_encoding($report_datas['briefing']['proposal'], 'GB2312', 'UTF-8'), 'UTF-8', 'GB2312');
                    $html .= <<<EOF
                    <tr class="myTitle">
                        <th width="100%" align="left">服务简报</th>
                    </tr>
                    <tr>
                        <td width="15%">服务内容</td>
                        <td width="85%" align="left">{$bc}</td>
                    </tr>
                    <tr v-if="report_datas.briefing.proposal!=''">
                        <td width="15%">跟进与建议</td>
                        <td width="85%" align="left">{$bp}</td>
                    </tr>
EOF;
                }
            }
            if(count($report_datas['photo'])>0){
                if(($report_datas['service_sections']!='' && in_array('5',$report_datas['service_sections'])) || $report_datas['service_sections']==''){
                    $html .= <<<EOF
                        <tr class="myTitle">
                            <th width="100%" align="left">现场工作照</th>
                        </tr>
EOF;
                    for ($p=0; $p < count($report_datas['photo']); $p++) {

                        $html .= <<<EOF
                        <tr>
                        <td width="20%" align="left">{$report_datas['photo'][$p]['remarks']}</td>
EOF;
                        $site_photos = explode(',',$report_datas['photo'][$p]['site_photos']);
                        for ($sp=0; $sp < count($site_photos); $sp++) {
                            $spa = $baseUrl_imgs.str_replace("\/",'/',trim($site_photos[$sp],'"'));
                            $html .= <<<EOF
                            <td width="20%" align="center">
                            <img src="${spa}" width="80" height="100" style="padding:20px 50px;">
                            </td>
EOF;
                        }
                        $sy_unm = 4-count($site_photos);
                        for($j=0;$j<$sy_unm;$j++){
                            $html .= <<<EOF
                            <td width="20%" align="center"></td>
EOF;
                        }
                        $html .= <<<EOF
                                </tr>  
EOF;
                    }
                }
            }
            if(count($report_datas['material'])>0){
                if(($report_datas['service_sections']!='' && in_array('2',$report_datas['service_sections'])) || $report_datas['service_sections']==''){
                    $html .= <<<EOF
                            <tr class="myTitle">
                                <th width="100%" align="left">物料使用</th>
                            </tr>  
                            <tr>
                            <td width="15%">名称</td>
                            <td width="12%">处理面积</td>
                            <td width="7%">配比</td>
                            <td width="8%">用量</td>
                            <td width="12%">使用方式</td>
                            <td width="12%">靶标</td>
                            <td width="12%">使用区域</td>
                            <td width="22%">备注</td>
                            </tr>
EOF;
                    for ($m=0; $m < count($report_datas['material']); $m++) {
                        $html .= <<<EOF
                        <tr>
                        <td width="15%">{$report_datas['material'][$m]['material_name']}</td>
                        <td width="12%">{$report_datas['material'][$m]['processing_space']}</td>
                        <td width="7%">{$report_datas['material'][$m]['material_ratio']}</td>
                        <td width="8%">{$report_datas['material'][$m]['dosage']}{$report_datas['material'][$m]['unit']}</td>
                        <td width="12%" align="left">{$report_datas['material'][$m]['use_mode']}</td>
                        <td width="12%" align="left">{$report_datas['material'][$m]['targets']}</td>
                        <td width="12%" align="left">{$report_datas['material'][$m]['use_area']}</td>
                        <td width="22%" align="left">{$report_datas['material'][$m]['matters_needing_attention']}</td>
                        </tr>  
EOF;
                    }
                }
            }
            if(count($report_datas['risk'])>0){
                if(($report_datas['service_sections']!='' && in_array('4',$report_datas['service_sections'])) || $report_datas['service_sections']==''){
                    $html .= <<<EOF
                            <tr class="myTitle">
                                <th width="100%" align="left">现场风险评估与建议</th>
                            </tr>  
                            <tr>
                            <td width="16%">风险类别</td>
                            <td width="19%">风险描述</td>
                            <td width="13%">靶标</td>
                            <td width="7%">级别</td>
                            <td width="15%">整改建议</td>
                            <td width="15%">采取措施</td>
                            <td width="15%">跟进日期</td>
                            </tr>
EOF;
                    for ($r=0; $r < count($report_datas['risk']); $r++) {
                        $c_t =  date('Y-m-d',strtotime($report_datas['risk'][$r]['creat_time']));
                        $html .= <<<EOF
                        <tr>
                        <td width="16%">{$report_datas['risk'][$r]['risk_types']}</td>
                        <td width="19%">{$report_datas['risk'][$r]['risk_description']}</td>
                        <td width="13%">{$report_datas['risk'][$r]['risk_targets']}</td>
                        <td width="7%">{$report_datas['risk'][$r]['risk_rank']}</td>
                        <td width="15%">{$report_datas['risk'][$r]['risk_proposal']}</td>
                        <td width="15%">{$report_datas['risk'][$r]['take_steps']}</td>
                        <td width="15%">{$c_t}</td>
                        </tr>
                        <tr>
                        <td width="16%">风险图片</td>
EOF;
                        $site_photos = explode(',',$report_datas['risk'][$r]['site_photos']);
                        for ($sp=0; $sp < count($site_photos); $sp++) {
                            $spa = $baseUrl_imgs.str_replace("\/",'/',trim($site_photos[$sp],'"'));
                            $html .= <<<EOF
                        <td width="21%" align="center">
                            <img src="${spa}" width="80" height="100" style="padding:20px 50px;">
                        </td>
EOF;
                        }
                        $sy_unm = 4-count($site_photos);
                        for($j=0;$j<$sy_unm;$j++){
                            $html .= <<<EOF
                            <td width="21%" align="center"></td>
EOF;
                        }
                        $html .= <<<EOF
                        </tr>  
EOF;
                    }
                }
            }
            if(count($report_datas['equipment'])>0){
                if(($report_datas['service_sections']!='' && in_array('3',$report_datas['service_sections'])) || $report_datas['service_sections']==''){
                    //设备巡查
                    $total = count($report_datas['equipment']);
                    $html .= <<<EOF
                            <tr class="myTitle">
                                <th  width="100%" align="left">设备巡查</th>
                            </tr>
EOF;
                    for ($e=0; $e < count($report_datas['equipment']); $e++) {
                        if(count($report_datas['equipment'][$e])>1){
                            $total01 = count($report_datas['equipment'][$e]['table_title']);
                            $html .= <<<EOF
                            <tr>
                                <th width="100%" align="left">{$report_datas['equipment'][$e]['title']}</th>
                            </tr>
                            <tr>
EOF;
                            $targs = (31/($total01-4))."%";
                            for ($t=0; $t < count($report_datas['equipment'][$e]['table_title']); $t++) {
                                if ($t==0) {
                                    $wi01 = '8%';
                                }else if ($t==1) {
                                    $wi01 = "11%";
                                }else if($t>1 && $t<count($report_datas['equipment'][$e]['table_title'])-2){
                                    $wi01 = $targs;
                                }else if ((($t+1)==count($report_datas['equipment'][$e]['table_title'])) || (($t+2)==count($report_datas['equipment'][$e]['table_title']))) {
                                    $wi01 = "25%";
                                }
                                $html .= <<<EOF
                                        <td width="{$wi01}">{$report_datas['equipment'][$e]['table_title'][$t]}</td>
EOF;
                            }
                            $html .= <<<EOF
                                    </tr>
EOF;
                            for ($c=0; $c < count($report_datas['equipment'][$e]['content']); $c++) {
                                $html .= <<<EOF
                                    <tr>
EOF;
                                for ($cd=0; $cd < count($report_datas['equipment'][$e]['content'][$c]); $cd++) {
                                    if ($cd==0) {
                                        $wi02 = '8%';
                                    }else if ($cd==1) {
                                        $wi02 = "11%";
                                    }else if($cd>1 && $cd<count($report_datas['equipment'][$e]['content'][$c])-2){
                                        $wi02 = $targs;
                                    }else if ((($cd+1)==count($report_datas['equipment'][$e]['content'][$c])) || (($cd+2)==count($report_datas['equipment'][$e]['content'][$c]))) {
                                        $wi02 = "25%";
                                    }

                                    $html .= <<<EOF
                                            <td width="{$wi02}">{$report_datas['equipment'][$e]['content'][$c][$cd]}</td>
EOF;
                                }
                                $html .= <<<EOF
                                   </tr>
EOF;

                            }
                        }else{
                            $html .= <<<EOF
                                    <tr>
                                        <th width="100%" align="left">{$report_datas['equipment'][$e]['title']}</th>
                                    </tr>
                                    <tr>
                                    <td width="100%">设备正常，无处理数据！</td>
                                    </tr>
EOF;
                        }
                    }
                }
            }

            if($Smarttech_list){
                $html .= $Smarttech_list;
            }

//            print_r($html);exit;
            /**
             * #############################################################
             * 很好 接下来就进入到处理小程序这边签名问题的处理了，开始---
             * #############################################################
             * */

            if($autograph_flag === 1){
                //优先处理有图片的情况
                //获取当前域名
                $sign_url = Request::instance()->domain();
                $eimageSrc01 = !empty($autograph_data['staff_id01_url']) ? $sign_url . $autograph_data['staff_id01_url'] : '';
                $eimageSrc02 = !empty($autograph_data['staff_id02_url']) ? $sign_url . $autograph_data['staff_id02_url'] : '';
                $eimageSrc03 = !empty($autograph_data['staff_id03_url']) ? $sign_url . $autograph_data['staff_id03_url'] : '';
                $cimageSrc = !empty($autograph_data['customer_signature_url']) ? $sign_url . $autograph_data['customer_signature_url'] : '';
                $cimageSrc_add = !empty($autograph_data['customer_signature_url_add']) ? $sign_url . $autograph_data['customer_signature_url_add'] : '';
                $customer_grade = !empty($autograph_data['customer_grade']) ? $autograph_data['customer_grade'] : '';
                $employee02_signature = '';
                $employee03_signature = '';
                // 如果flag == 1则需要作翻转处理

//                $imgPath = app()->getRootPath().'public'.$autograph_data['customer_signature_url'];
//                $cmd = " /usr/bin/convert -rotate -90 $imgPath  $imgPath 2>&1";
//                @exec($cmd,$output,$return_val);
                if($autograph_data['conversion_flag'] == 1){
                    $degrees = -90;      //旋转角度
//                $url = $cimageSrc;  //图片存放位置
//                    $this->pic_rotating($degrees,$cimageSrc);
//                  应用目录
                    $imgPath = app()->getRootPath().'public'.$autograph_data['customer_signature_url'];
                    $cmd = " /usr/bin/convert -rotate $degrees $imgPath  $imgPath 2>&1";
                    @exec($cmd,$output,$return_val);
                    if($return_val === 0){
                        $autographModel->where('id','=',$autographV2['id'])->update(['conversion_flag'=>0]);
                    }
                }

            }else{
                $eimageName01 = "lbs_".date("His",time())."_".rand(111,999).'.png';
                $eimageName02 = "lbs_".date("His",time())."_".rand(111,999).'.png';
                $eimageName03 = "lbs_".date("His",time())."_".rand(111,999).'.png';
                //设置图片保存路径
                $path = "../public/temp/".date("Ymd",time());
                //判断目录是否存在 不存在就创建
                if (!is_dir($path)){
                    mkdir($path,0777,true);
                }
                $employee01_signature = str_replace("data:image/jpg;base64,","",$report_datas['autograph']['employee01_signature']);
                $employee02_signature = str_replace("data:image/jpg;base64,","",$report_datas['autograph']['employee02_signature']);
                $employee03_signature = str_replace("data:image/jpg;base64,","",$report_datas['autograph']['employee03_signature']);
                //图片路径
                $eimageSrc01= $path."/". $eimageName01;
                if($employee01_signature!='') file_put_contents($eimageSrc01,base64_decode($employee01_signature));
                $eimageSrc02= $path."/". $eimageName02;
                if($employee02_signature!='') file_put_contents($eimageSrc02,base64_decode($employee02_signature));
                $eimageSrc03= $path."/". $eimageName03;
                if($employee03_signature!='') file_put_contents($eimageSrc03,base64_decode($employee03_signature));

                if($report_datas['autograph']['customer_signature']!='' && $report_datas['autograph']['customer_signature']!='undefined'){
                    $cimageName = "lbs_".unique_str().'.png';
                    $cimageSrc= $path."/". $cimageName;
                    $customer_signature = str_replace("data:image/png;base64,","",$report_datas['autograph']['customer_signature']);
                    file_put_contents($cimageSrc, base64_decode($customer_signature));
                    $degrees = 90;      //旋转角度
                    $url = $cimageSrc;  //图片存放位置
                    $this->pic_rotating($degrees,$url);
                }else{
                    $cimageSrc='';
                    $cimageSrc_add = '';
                }
                //签名
                $customer_grade = $report_datas['autograph']['customer_grade'];
            }
            /**
             * #############################################################
             * 签名处理结束
             * #############################################################
             * */


//            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
//            $pdf->SetFont('cid0cs', '');
            $html .= <<<EOF
                        <tr class="myTitle">
                            <th width="100%" align="left">客户点评</th>
                        </tr>
                        <tr>
                        <td width="100%" align="left">{$customer_grade}星(1~5)</td>
                        </tr>
                        <tr class="myTitle">
                            <th  width="100%" align="left">报告签名</th>
                        </tr>                                         
                        <tr>
                        <td width="50%" align="left">服务人员签字</td>
                        <td width="50%" align="left">客户签字</td>
                        </tr>
                        <tr>
                        <td width="50%" align="left">
                            <img src="${eimageSrc01}" width="130" height="80" style="magin:20px 50px;">
EOF;
            if ($employee02_signature != '' || isset($autograph_data['staff_id02_url']) && $autograph_data['staff_id02_url'] != ''){
                $html .= <<<EOF
                        <img src="{$eimageSrc02}" width="130" height="80" style="magin:20px 50px;">
EOF;
            }
            if ($employee03_signature != '' || isset($autograph_data['staff_id03_url']) && $autograph_data['staff_id03_url'] != ''){
                $html .= <<<EOF
                        <img src="{$eimageSrc03}" width="130" height="80" style="magin:20px 50px;">
EOF;
            }

            $html .= <<<EOF
                </td>
EOF;
            $html .= <<<EOF
                <td width="50%" align="left"><img src="{$cimageSrc}" width="130" height="80" style="magin:20px 50px;"><img src="{$cimageSrc_add}" width="130" height="80" style="magin:20px 50px;"></td>
                </tr>
EOF;
            $html .= <<<EOF
            </table>
            <img src="{$company_img}">
            </body>
</html>
EOF;


//            print_r($html);exit;

//            $result_html = $html;
//        echo $html;exit();
            $month = date('Y-m',time());
            $name = $report_datas['basic']['CustomerName'];
            $res = $this->outputHtml($month, $html, $name);
//            if ($month == '' || $cust == '' || $city = '') {
//                return error(-1, '输入参数有误', []);
//            }
            $file_path = 'report/' . $month . '/' . $name . '.pdf';
//        if (is_file($file_path)) {
            $domain = 'http://xcx.com/';
            $url = $domain . $file_path;
            //有报告就返回，没返回就
            return success(0, 'success', $url);


        }
    }

    public function outputHtml($month, $ctx, $cust)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/report/' . $month . '/';
//        $fileName= $cust.'.html';  //获取文件名
        if (!is_dir($dir)) {
            //iconv方法是为了防止中文乱码，保证可以创建识别中文目录，不用iconv方法格式的话，将无法创建中文目录,第三参数的开启递归模式，默认是关闭的
            mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
        }
        $fp = fopen($dir . $cust . '.html', "w");
        $len = fwrite($fp, $ctx);
        fclose($fp);
        $rs = $this->exec($dir, $cust, $cust, $month);
        if ($len > 0) {
            return true;
        }
        return false;
    }

    public function exec($path, $filename, $name, $month)
    {
        $ext_pdf = '.pdf';
        $ext_html = '.html';
        $html_name = $path . $filename . $ext_html;
        $pdf_name = $path . $filename . $ext_pdf;
        $cmd = "wkhtmltopdf --print-media-type --page-size A4 --margin-left 0 --margin-right 0 --enable-local-file-access $html_name $pdf_name 2>&1";
        @exec($cmd, $output, $return_val);
        if ($return_val === 0) {
//            $analyseReportModel = new AnalyseReport();
            $file_path = '/report/' . $month . '/' . $filename . $ext_pdf;
//            $res = $analyseReportModel->where('url_id', $filename)->update(['url' => $file_path, 'make_flag' => 0]);
//            if ($res) {
                return 1;
//            }
        }
    }



    function pic_rotating($degrees,$url){
        $srcImg = imagecreatefrompng($url);     //获取图片资源
        $rotate = imagerotate($srcImg, $degrees, 0);        //原图旋转

        //获取旋转后的宽高
        $srcWidth = imagesx($rotate);
        $srcHeight = imagesy($rotate);

        //创建新图
        $newImg = imagecreatetruecolor($srcWidth, $srcHeight);

        //分配颜色 + alpha，将颜色填充到新图上
        $alpha = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
        imagefill($newImg, 0, 0, $alpha);

        //将源图拷贝到新图上，并设置在保存 PNG 图像时保存完整的 alpha 通道信息
        imagecopyresampled($newImg, $rotate, 0, 0, 0, 0, $srcWidth, $srcHeight, $srcWidth, $srcHeight);
        imagesavealpha($newImg, true);

        //生成新图
        imagepng($newImg, $url);
    }

    public function createSmarttechHtml($CustomerID)
    {
        $CustomerID = 'McDonald_Star_House';
        $CustomerDeviceModel = new CustomerDeviceModel();
        $deviceCount = $CustomerDeviceModel->where('CustomerID',$CustomerID)->append(['all_trigger_count','device_cn_name'])->field('type,CustomerID,count(id) as device_count')->group('type')->select()->toArray();
        if(!empty($deviceCount)){
            $list = array_column($deviceCount,null,'type');
            $allDevice = $CustomerDeviceModel
                ->where('CustomerID',$CustomerID)
                ->append(['day_trigger_count','night_trigger_count'])
                ->field('type,Device_ID,CustomerID,Device_Name,floor,layer,others')
                ->select()
                ->toArray();
            if(!empty($allDevice)){
                foreach ($allDevice as $key=>$item){
                    if($list[$item['type']]) $list[$item['type']]['list'][] = $item;
                }
            }
            $html = '<tr class="myTitle">
                        <th width="100%" align="left">智能设备</th>
                    </tr>';
            foreach ($list  as $item){
                $html .= <<<EOF
                    <tr>
						<th width="100%" align="left"> 
                            {$item['device_cn_name']} ({$item['all_trigger_count']}/{$item['device_count']})
                        </th>
					</tr>
					<tr>
					    <td width="25%">装置名称</td>        
						<td width="20%">区域</td>         
						<td width="25%">08：00-00:00触发次数</td>          
						<td width="25%">00：00-08:00触发次数</td>  
					</tr>
EOF;
                foreach ($item['list']  as $k=>$v) {
                    $html .= <<<EOF
					<tr>       
						<td width="25%">{$v['Device_Name']}</td>  
						<td width="20%">{$v['floor']} {$v['layer']} {$v['others']}</td>   
						<td width="25%">{$v['day_trigger_count']}</td>      
						<td width="25%">{$v['night_trigger_count']}</td>      
					</tr> 
EOF;
                }
            }
            $PieHtml = $this->createPieHtml();
            return $html.$PieHtml;
        }
        return '';
    }

    public function createPieHtml()
    {
        $html = <<<EOF
                <tr class="myTitle">
                        <th width="100%" align="left">智能设备饼状图</th>
                    </tr>';
                <tr>       
					<td width="100%">
                        <div style="width: 800px;height: 100%">
                            {$this->chartPie()}
                        </div>
                    </td>      
				</tr> 
EOF;
        return $html;
    }

    public function chartPie()
    {
        $echarts = ECharts::init("#chartPie");
        $option = new Option();
        $option->animation(false);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae', '#749f83']);
        $option->title([
            "text" => '智能饼状图',
            "left" => 'center'
        ]);
//        $option->grid([
//            "top"=>"25%"
//        ]);
        $option->legend([
            "orient" => 'vertical',
            "left" => 'left',

        ]);

        $data = [['value'=>1048,'name'=>'Search Engine'],['value'=>735,'name'=>'Direct'],['value'=>580,'name'=>'Email'],['value'=>484,'name'=>'Union Ads'],['value'=>800,'name'=>'Video Ads']];

        $option->series([
//                'name' => 'Access From',
                'type' => 'pie',
                'radius' => '65%',
                'data' => $data,
                "backgroundColor" => 'white',
                'label' => [
                    'normal' => [
                        'formatter' => '{b}:{c} ({d}%)',
                        'textStyle' => [
                            'fontWeight' => 'normal',
                            'color' => 'black',
                            'fontSize' => 18
                        ]

                    ]
                ],
                'emphasis' => [
                    'itemStyle' => [
                        'shadowBlur' => 10,
                        'shadowOffsetX' => 0,
                        'shadowColor' => 'rgba(0, 0, 0, 0.5)'
                    ]
                ],
            ]
        );
//        $option->yAxis([]);
        $chart = new Pie();
//        $chart->name = "8月害虫统计";
        $chart->itemStyle = [
            'normal' => [
                'label' => [
                    'show' => true,
                    'position' => 'top',
                    'textStyle' => [
                        'color' => 'black',
                        'fontSize' => 18
                    ]
                ]
            ]
        ];
        $option->addSeries($chart);
        $echarts->option($option);
        return $echarts->render();
    }
}
