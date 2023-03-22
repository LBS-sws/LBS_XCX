<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\cache\driver\Redis;
use think\facade\Request;
use think\facade\Db;


class Getjobbyid
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名、工作单编号和工作单类型等';
        $result['data'] = null;

        $token = request()->header('token');
        if(!isset($_POST['staffid']) || !isset($token) || !isset($_POST['jobid']) || !isset($_POST['jobtype'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['jobid']) || empty($_POST['jobtype'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $jobid = $_POST['jobid'];
        $jobtype = $_POST['jobtype'];
        
       //获取用户登录信息
        $redis = new Redis();
        $token_key = 'token_' . $staffid;
        $user_token = $redis->get($token_key);
        if (!$user_token) {
            $user_token = Db::name('token')->where('StaffID',$staffid)->find();
            $redis->set($token_key,$user_token,600);
        }
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            // $job_wheres['j.Staff01'] = $staffid;
            if($jobtype==1){
                $job_wheres['j.JobID'] = $jobid;
                $job_datas = Db::table('joborder')->alias('j')->join('service s','j.ServiceType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('customercompany c','c.CustomerID=j.CustomerID')->where($job_wheres)->field('j.*,s.ServiceName,u.StaffName,c.CustomerType')->find();
                $service_type = $job_datas['ServiceType'];
                //查询技术员备注

                $servicecontract_key = 'servicecontract_'. $jobid;
                $technician_remarks = $redis->get($servicecontract_key);
                if(!$technician_remarks){
                    $technician_remarks = Db::table('servicecontract')->where('ContractNumber',$job_datas['ContractNumber'])->where('CustomerID',$job_datas['CustomerID'])->where('ServiceType',$service_type)->field('TechRemarks')->find();
                    $redis->set($servicecontract_key, $technician_remarks,3600);
                }
                if ($technician_remarks) {
                    $job_datas['TechRemarks'] = $technician_remarks['TechRemarks'];
                }else{
                    $job_datas['TechRemarks'] = '';
                }
                
                //布防图
                // $arr = array('contractid'=>$job_datas['ContractID'],'staffid'=>$staffid,'token'=>$token);
                // $xinu_data = $this->curl_post($this->curl_post(config('app.uapp_url') . '/web/remote/getAttachment.php',$arr);
                // $xinu = json_decode($xinu_data,true);
                // if($xinu['code']==1){
                //     $job_datas['set_img'] = [];
                //     for ($i = 0; $i < count($xinu['data']); $i++) {
                //         if ($xinu['data'][$i]['filetype']=='jpg' || $xinu['data'][$i]['filetype']=='jpeg' || $xinu['data'][$i]['filetype']=='png') {
                //             $set_img = 'data:image/' . $xinu['data'][$i]['filetype'] . ';base64,' . $xinu['data'][$i]['content'];
                //             array_push($job_datas['set_img'],$set_img);
                //         }
                //     }
                // }else{
                //   //返回数据
                //     $result['xinu_msg'] = $xinu['msg'];
                // }
                // $result['xinu'] = $xinu;
            }elseif ($jobtype==2) {
                $job_wheres['j.FollowUpID'] = $jobid;
                $job_datas = Db::table('followuporder')->alias('j')->join('service s','j.SType=s.ServiceType')->join('staff u','j.Staff01=u.StaffID')->join('customercompany c','c.CustomerID=j.CustomerID')->where($job_wheres)->field('j.*,s.ServiceName,u.StaffName,j.SType as ServiceType,c.CustomerType')->cache(true,60)->find();
                $service_type = $job_datas['SType'];
            }
           
            if ($job_datas) {
                //数据添加
                $job_datas['type'] = $jobtype;
                $job_datas['StaffName01'] = '';
                $job_datas['StaffName02'] = '';
                $job_datas['button'] = '';
                if ($job_datas['Staff02']) {
                    $StaffName01 = Db::table('staff')->where('StaffID',$job_datas['Staff02'])->field('StaffName')->cache(true,60)->find();
                    $job_datas['StaffName01'] = $StaffName01['StaffName'];
                }
                if ($job_datas['Staff03']) {
                    $StaffName02 = Db::table('staff')->where('StaffID',$job_datas['Staff03'])->field('StaffName')->cache(true,60)->find();
                    $job_datas['StaffName02'] = $StaffName02['StaffName'];
                }
                 
                if ($jobtype==1) {
                    $job_datas['Watchdog'] = '';
                    //查询当前设备
                    $where_dq['e.job_id'] = $jobid;
                    $where_dq['e.job_type'] = 1;
                    $dq_eqs = Db::table('lbs_service_equipments')->alias('e')->join('lbs_service_equipment_type t','e.equipment_type_id=t.id','right')->field('t.name,e.equipment_type_id')->where($where_dq)->Distinct(true)->cache(true,60)->select();
                    if (count($dq_eqs)>0) {
                                for ($i=0; $i < count($dq_eqs); $i++) { 
                                    $n['job_id'] = $jobid;
                                    $n['job_type'] = 1;
                                    $n['equipment_type_id'] = $dq_eqs[$i]['equipment_type_id'];
                                    $numbers = Db::table('lbs_service_equipments')->where($n)->cache(true,60)->count();
                                    if ($job_datas['Watchdog'] == '') {
                                        $job_datas['Watchdog'] = $dq_eqs[$i]['name'].'-'.$numbers;
                                    }else{
                                        $job_datas['Watchdog'] = $job_datas['Watchdog'].','.$dq_eqs[$i]['name'].'-'.$numbers;
                                    }   
                                } 
                        
                    }else{
                        //查询上一个设备情况
                        $last_job = Db::table('joborder')->where('ContractID',$job_datas['ContractID'])->where('ServiceType',$job_datas['ServiceType'])->where('Status',3)->order('JobDate', 'desc')->field('JobID')->cache(true,60)->find();
                         if ($last_job) {
                           $wherel['e.job_id'] = $last_job['JobID'];
                           $wherel['e.job_type'] = 1;
                           $equipments = Db::table('lbs_service_equipments')->alias('e')->join('lbs_service_equipment_type t','e.equipment_type_id=t.id','right')->field('t.name,e.equipment_type_id')->where($wherel)->Distinct(true)->cache(true,60)->select();
                           if (count($equipments)>0) {
                                for ($i=0; $i < count($equipments); $i++) { 
                                    $n['job_id'] = $last_job['JobID'];
                                    $n['job_type'] = 1;
                                    $n['equipment_type_id'] = $equipments[$i]['equipment_type_id'];
                                    $numbers = Db::table('lbs_service_equipments')->where($n)->cache(true,60)->count();
                                    if ($job_datas['Watchdog'] == '') {
                                        $job_datas['Watchdog'] = $equipments[$i]['name'].'-'.$numbers;
                                    }else{
                                        $job_datas['Watchdog'] = $job_datas['Watchdog'].','.$equipments[$i]['name'].'-'.$numbers;
                                    }   
                                }    
                           }else{
                                $job_datas['Watchdog'] = '无设备';
                           }
                        }
                    }
                    
                   
                    //服务项目
                    $service_projects = '';
                    if($jobtype==1 && $service_type==1){//洁净
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
                    }else if($jobtype==1 && $service_type==2){//灭虫
                        if ($job_datas["Item01"] > 0) $service_projects .= "老鼠,";
                        if ($job_datas["Item02"] > 0) $service_projects .= "蟑螂,";
                        if ($job_datas["Item03"] > 0) $service_projects .= "蚁,";
                        if ($job_datas["Item04"] > 0) $service_projects .= "果蝇,";
                        if ($job_datas["Item09"] > 0) $service_projects .= "苍蝇,";
                        if ($job_datas["Item06"] > 0) $service_projects .= "水剂喷机：".$job_datas["Item06"]." ".$job_datas["Item06Rmk"] . ",";
                        if ($job_datas["Item07"] > 0) $service_projects .= "罐装灭虫喷机：".$job_datas["Item07"]." ".$job_datas["Item07Rmk"] . ",";
                        if ($job_datas["Item10"] > 0) $service_projects .= "灭蝇灯：".$job_datas["Item10"]." ".$job_datas["Item10Rmk"] . ",";
                        if ($job_datas["Item08"] > 0) $service_projects .= "其他：".$job_datas["Item08"]." ".$job_datas["Item08Rmk"] . ",";
                        
                    }else if($jobtype==1 && $service_type==3){//灭虫喷焗
                        if ($job_datas["Item01"] > 0) $service_projects .= "蚊子,";
                        if ($job_datas["Item02"] > 0) $service_projects .= "苍蝇,";
                        if ($job_datas["Item03"] > 0) $service_projects .= "蟑螂,";
                        if ($job_datas["Item04"] > 0) $service_projects .= "跳蚤,";
                        if ($job_datas["Item05"] > 0) $service_projects .= "蛀虫,";
                        if ($job_datas["Item06"] > 0) $service_projects .= "白蚁,";
                        if ($job_datas["Item07"] > 0) $service_projects .= "其他：".$job_datas["Item07Rmk"] . ",";
                    }else if($jobtype==1 && $service_type==4){//租机服务
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
                    $job_datas['service_projects'] = $service_projects;
                }
                //查询历史工作单数量
                //获取城市
                $launch_date = Db::name('enums')->alias('e')->join('officecity o ','o.Office=e.EnumID')->join('lbs_service_city_launch_date l ','e.Text=l.city')->where('o.City', $job_datas['City'])->where('e.EnumType', 8)->field('l.launch_date')->cache(true,60)->find();
                if($launch_date){
                    $histroy_job = Db::table('joborder')->where('CustomerID',$job_datas['CustomerID'])->where('ServiceType',$service_type)->where('Status',3)->whereTime('JobDate', 'between', [$launch_date['launch_date'], $job_datas['JobDate']])->cache(true,60)->count();
                    $histroy_fol = Db::table('followuporder')->where('CustomerID',$job_datas['CustomerID'])->where('SType',$service_type)->where('Status',3)->whereTime('JobDate', 'between', [$launch_date['launch_date'], $job_datas['JobDate']])->cache(true,60)->count();
                }else{
                    $histroy_job = Db::table('joborder')->where('CustomerID',$job_datas['CustomerID'])->where('ServiceType',$service_type)->where('Status',3)->whereTime('JobDate', '<', $job_datas['JobDate'])->cache(true,60)->count();
                    $histroy_fol = Db::table('followuporder')->where('CustomerID',$job_datas['CustomerID'])->where('SType',$service_type)->where('Status',3)->whereTime('JobDate', '<', $job_datas['JobDate'])->cache(true,60)->count();
                }
                
                $job_datas['history'] = $histroy_job+$histroy_fol;
                
                //返回数据
                $result['code'] = 1;
                $result['msg'] = '成功';
                $result['data'] = $job_datas;
            }else{
                $result['code'] = 1;
                $result['msg'] = '成功，无数据';
                $result['data'] = null;
            }
        }else{
             $result['code'] = 0;
             $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
            $redis->delete($token_key);
        }
        return json($result);
    }
    public function curl_post($url , $data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        if($output === false)
        {
            $output = curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }
}
