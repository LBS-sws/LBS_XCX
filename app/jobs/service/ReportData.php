<?php
namespace app\jobs\service;

use app\common\model\AutographV2;
use app\common\model\ClientDeviceModel;
use app\common\model\CustomerDeviceModel;
use app\common\model\FollowupOrder;
use app\common\model\ServiceBriefingsModel;
use app\common\model\ServiceMaterialsModel;
use app\common\model\ServicePhotosModel;
use app\common\model\TriggerDeviceModel;
use app\technician\model\JobOrder;
use app\technician\model\Risks;
use app\technician\model\ServiceEquipments;
use beyong\echarts\charts\Line;
use beyong\echarts\charts\Pie;
use beyong\echarts\ECharts;
use beyong\echarts\Option;

class ReportData
{
    /**
     * 基础信息
     * @param $param
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getBaseInfo($param)
    {
        $data = (new JobOrder())
            ->field('JobID,CustomerName,Addr,ServiceType,JobDate,ContactName,Mobile,Staff01,Staff02,Staff03,FirstJob,Item01,Item02,Item03,Item04,Item05,Item06,Item07,Item08,Item09,Item10,Item11,Item12,Item13,Item13Rmk,Item12Rmk,Item09Rmk,Item08Rmk,Item07Rmk,Item06Rmk,Item05Rmk,Item10Rmk,Item11Rmk,Item04Rmk,FirstJob')
            ->where('JobID',$param['job_id'])
            ->append(['staff','device','task_type'])
            ->with('ServiceName')
            ->find();
        if(!$data) return [];
        $data = $data->toArray();
        return self::serviceProjects($param['job_type'],$data['ServiceType'],$data);
    }

    /**
     * 服务项目
     * @param $jobtype
     * @param $service_type
     * @param $job_datas
     * @return mixed
     */
    public static function serviceProjects($jobtype,$service_type,$job_datas)
    {
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
        return $job_datas;
    }

    /**
     * 服务简报
     * @param $param
     * @return ServiceBriefingsModel|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getServiceBriefingInfo($param)
    {
        return (new ServiceBriefingsModel())->where('job_id',$param['job_id'])->where('job_type',$param['job_type'])->field('content,proposal')->find();
    }

    /**
     * 现场工作照数据
     * @param $param
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getWorkPhotosInfo($param)
    {
        $data = (new ServicePhotosModel())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->field('site_photos,remarks')
            ->select();
        if($data->isEmpty()) return [];
        $data = $data->toArray();
        foreach ($data as $key=>$item){
            $data[$key]['site_photos'] = !empty($item['site_photos']) ? explode(',',$item['site_photos']) : '' ;
        }
        return $data;
    }

    /**
     * 物料使用
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getMaterialUsageInfo($param)
    {
        $data = (new ServiceMaterialsModel())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->field('material_name,processing_space,material_ratio,dosage,unit,use_mode,targets,use_area,matters_needing_attention')
            ->select();
        if($data->isEmpty()) return [];
        return  $data->toArray();
    }

    /**
     * 现场风险评估与建议
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getRiskInfo($param)
    {
        $data = (new Risks())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->append(['ct','site_img'])
            ->field('risk_types,risk_description,risk_targets,risk_rank,risk_proposal,take_steps,creat_time,site_photos')
            ->select();
        if($data->isEmpty()) return [];
        return $data->toArray();
    }

    /**
     * 设备巡查
     * @param $param
     * @return mixed
     */
    public static function getDeviceInspectionInfo($param)
    {
        $data =  (new ServiceEquipments())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->where('equipment_type_id','<>',245)
            ->whereNotNull('equipment_area')
            ->whereNotNull('check_datas')
            ->group('equipment_type_id')
            ->field('equipment_type_id,job_id,count(id) as equipment_total_count')
            ->append(['device_info','tigger_count','equipment_list'])
            ->select();
        if($data->isEmpty()) return [];
        $DeviceInspectionData = $data = $data->toArray();
        $DeviceInspectionData = array_column($DeviceInspectionData,'device_info',null);
        $DeviceInspectionData = array_column($DeviceInspectionData,'check_targt',null);
        $max_count = 0;
        foreach ($DeviceInspectionData as $item){
            $count = count($item);
            if($count > $max_count) $max_count = $count;
        }
        return ['data'=>$data,'max_count'=>$max_count];
    }

    /**
     * 智能设备
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getSmarttechInfo($param)
    {
        //客户编号
        $CustomerID = self::getCustomerID($param);

//        $CustomerID = 'McDonald_Star_House';
        if(!$CustomerID) return [];
        //营业时间
        $timeArr = self::getShowTime($CustomerID);
        if(!$timeArr) return [];
        $list['work_time'] = $timeArr['openingTime'] . '-' . $timeArr['closingTime'];
        $list['no_work_time'] = $timeArr['closingTime'] . '-' . $timeArr['openingTime'];
        //所有设备
        $CustomerDeviceModel = new CustomerDeviceModel();
        $allDevice = $CustomerDeviceModel
            ->where('CustomerID',$CustomerID)
            ->field('Device_ID,Device_Name,floor,layer,others')
            ->select();
        if($allDevice->isEmpty()) return [];
        $allDevice = $allDevice->toArray();
        $allDeviceIds = array_column($allDevice,'Device_ID',null);
        $allDevice = array_column($allDevice,null,'Device_ID');
        //获取触发数据查询时间段
        $timeData = self::getTimeSlot($param);
        if(!empty($timeData)){
            $startDate = $timeData['data']['JobDate'];
            $endDate = $timeData['endDate'];
            $where[] = ['triggerDate','>=',$startDate];
            $where[] = ['triggerDate','<=',$endDate];
        }else{
            $where = [];
        }
        //设备触发数据
        $tiggerData = (new TriggerDeviceModel)
            ->where($where)
            ->where('CustomerID',$CustomerID)
            ->whereIn('Device_ID',$allDeviceIds)
            ->field('Device_ID,triggerTime')
            ->select();
        if($tiggerData->isEmpty()) return [];
        $tiggerData = $tiggerData->toArray();
        $tiggerDeviceArr = [];
        $deviceList=[];
        foreach ($tiggerData as $item){
            array_push($tiggerDeviceArr,$item['Device_ID']); //触发的设备总数
            if(!empty($deviceList[$item['Device_ID']])){
                if($item['triggerTime'] >= $timeArr['openingTime'] && $item['triggerTime'] <= $timeArr['closingTime']){
                    $deviceList[$item['Device_ID']]['work_count'] += 1;
                }else{
                    $deviceList[$item['Device_ID']]['no_work_count'] += 1;
                }
            }else{
                $deviceList[$item['Device_ID']] = [
                    'Device_Name'=>$allDevice[$item['Device_ID']]['Device_Name'],
                    'area'=>$allDevice[$item['Device_ID']]['floor'].' ' . $allDevice[$item['Device_ID']]['layer'] .' '.$allDevice[$item['Device_ID']]['others'],
                    'work_count'=>0,
                    'no_work_count'=>1
                ];
            }
        }
        $tiggerDeviceArr = array_values(array_unique($tiggerDeviceArr));
        $list['device_cn_name'] = CustomerDeviceModel::SIGFOX;
        $list['device_count'] = Count($allDevice);
        $list['tigger_device_count'] = Count($tiggerDeviceArr);
        $list['list'] = $deviceList;
        return $list;
    }

    public static function getTimeSlot($param)
    {
        $jobOrderInfo = (new JobOrder())->where('JobID',$param['job_id'])->field('jobDate,ContractNumber')->find();
        $data = (new JobOrder())
            ->where('ContractNumber',$jobOrderInfo['ContractNumber'])
            ->where('ServiceType',2)
            ->where('Status',3)
            ->where('JobID','<>',$param['job_id'])
            ->where('jobDate','<',$jobOrderInfo['jobDate'])
            ->field('JobID,ContractNumber,JobDate,JobTime,JobTime2')
            ->order('JobDate desc')
            ->find();
        if(!$data) return [];
        $data = $data->toArray();
        return ['endDate'=>$jobOrderInfo['jobDate'],'data'=>$data];
    }

    /**
     * 获取工作单客户编号
     * @param $param
     * @return mixed|string
     */
    public static function getCustomerID($param)
    {
        switch ($param['job_type']){
            case 1:
                $CustomerID = (new JobOrder())->where('JobID',$param['job_id'])->value('CustomerID');
                break;
            case 2:
                $CustomerID = (new FollowupOrder())->where('FollowUpID',$param['job_id'])->value('CustomerID');
                break;
            default:
                $CustomerID = '';
                break;
        }
        return $CustomerID;
    }

    /**
     * 获取数据筛选时间
     * @param $CustomerID
     * @return ClientDeviceModel|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getShowTime($CustomerID)
    {
        if(!$CustomerID) return [];
        $data = (new ClientDeviceModel())->where('Client_Key',$CustomerID)->field('Client_Key,closingTime,openingTime')->find();
        if(!$data) return [];
        return $data->toArray();
    }

    /**
     * 智能设备饼状图数据
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getCakeData($param)
    {
        $CustomerID = self::getCustomerID($param);
//        $CustomerID = 'McDonald_Star_House';
        //所有设备
        $CustomerDeviceModel = new CustomerDeviceModel();
        $allDevice = $CustomerDeviceModel
            ->where('CustomerID',$CustomerID)
            ->field('Device_ID,floor,layer,others')
            ->select();
        if($allDevice->isEmpty()) return [];
        $allDevice = $allDevice->toArray();
        $allDeviceIds = array_column($allDevice,'Device_ID',null);
        $allDevice = array_column($allDevice,null,'Device_ID');
        //获取触发数据查询时间段
        $timeData = self::getTimeSlot($param);
        if(!empty($timeData)){
            $startDate = $timeData['data']['JobDate'];
            $endDate = $timeData['endDate'];
            $where[] = ['triggerDate','>=',$startDate];
            $where[] = ['triggerDate','<=',$endDate];
        }else{
            $where = [];
        }
        //设备触发数据
        $tiggerData = (new TriggerDeviceModel)
            ->where($where)
            ->where('CustomerID',$CustomerID)
            ->whereIn('Device_ID',$allDeviceIds)
            ->field('Device_ID')
            ->select();
        if($tiggerData->isEmpty()) return [];
        $tiggerData = $tiggerData->toArray();
        foreach ($tiggerData as $key=>$item){
            $tiggerData[$key]['area'] = $allDevice[$item['Device_ID']]['floor'] . '-' .$allDevice[$item['Device_ID']]['layer']. '-' .$allDevice[$item['Device_ID']]['others'];
        }
        $deviceList=[];
        foreach ($tiggerData as $item) {
            if(!empty($deviceList[$item['area']])){
                $deviceList[$item['area']] += 1;
            }else{
                $deviceList[$item['area']] = 1;
            }
        }
        $count = array_sum($deviceList);
        $list=[];
        foreach ($deviceList as $key=>$item){
//            $percentage = bcmul(bcdiv(strval($item),strval($count),2),'100');
            array_push($list,['value'=>$item,'name'=>$key]);
        }
        return $list;
    }

    /**
     * 智能设备饼状图
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getSmarttechCakeInfo($param)
    {
        $data = self::getCakeData($param);
        if(!$data) return [];
        $echarts = ECharts::init("#SmarttechCake");
        $option = new Option();
        $option->animation(false);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae','#3300ff', '#339933','#660099','#336600','#330000','#749f83','#458a77','#660000','#6633ff','#990099','#996600','#996699','#ff9900','#ffcc66']);
        $option->legend([
            "left" => 'center',
        ]);
        $option->series([
                'type' => 'pie',
                'radius' => '45%',
                'data' => $data,
                "backgroundColor" => 'white',
                'label' => [
                    'normal' => [
                        'formatter' => '{b}:{c} ({d}%)',
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
        $chart = new Pie();
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

    /**
     * 智能设备折线图（侦测趋势 (按时间)）数据
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getLineTimeData($param)
    {
        $CustomerID = self::getCustomerID($param);
//        $CustomerID = 'McDonald_Star_House';
        //所有设备
        $CustomerDeviceModel = new CustomerDeviceModel();
        $allDevice = $CustomerDeviceModel
            ->where('CustomerID',$CustomerID)
            ->field('Device_ID,floor,layer,others')
            ->select();
        if($allDevice->isEmpty()) return [];
        $allDevice = $allDevice->toArray();
        $allDeviceIds = array_column($allDevice,'Device_ID',null);
        $allDevice = array_column($allDevice,null,'Device_ID');
        //获取触发数据查询时间段
        $timeData = self::getTimeSlot($param);
        if(!empty($timeData)){
            $startDate = $timeData['data']['JobDate'];
            $endDate = $timeData['endDate'];
            $where[] = ['triggerDate','>=',$startDate];
            $where[] = ['triggerDate','<=',$endDate];
        }else{
            $where = [];
        }
        //设备触发数据
        $tiggerData = (new TriggerDeviceModel)
            ->where($where)
            ->where('CustomerID',$CustomerID)
            ->whereIn('Device_ID',$allDeviceIds)
            ->field('Device_ID,triggerTime')
            ->select();
        if($tiggerData->isEmpty()) return [];
        $tiggerData = $tiggerData->toArray();

        $area = [];
        $deviceList=[];
        foreach ($tiggerData as $key=>$item){
            $flo = $allDevice[$item['Device_ID']]['floor'] . '-' .$allDevice[$item['Device_ID']]['layer']. '-' .$allDevice[$item['Device_ID']]['others'];
            $tiggerData[$key]['area'] = $flo;
            $area[] = $flo;
            if(!empty($deviceList[$flo])){
                $deviceList[$flo][] = $item;
            }else{
                $deviceList[$flo][] = $item;
            }
        }
        $area = array_values(array_unique($area));

        if(!$deviceList) return [];
        $list = [];
        foreach ($deviceList as $key=>$item){
            $hourArr = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,];
            foreach ($item as $v){
                $H = intval(date('H',strtotime($v['triggerTime'])));
                $hourArr[$H] +=1;
            }
            $arr['name'] = $key;
            $arr['type'] = 'line';
            $arr['data'] = $hourArr;
            array_push($list,$arr);
        }
        return ['area'=>$area,'list'=>$list];
    }

    /**
     * 智能设备折线图（侦测趋势 (按时间)）
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getSmarttechLineTimeInfo($param)
    {
        $data = self::getLineTimeData($param);
        if(!$data) return [];
        $echarts = ECharts::init("#chartLine");
        $option = new Option();
        $option->animation(false);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae','#3300ff', '#339933','#660099','#336600','#330000','#749f83','#458a77','#660000','#6633ff','#990099','#996600','#996699','#ff9900','#ffcc66']);
        $option->xAxis([
            "type" => "category",
            "boundaryGap" => false,
            "data" => [
                "0000-0100", "0100-0200", "0200-0300", "0300-0400", "0400-0500", "0500-0600", "0600-0700", "0700-0800", "0800-0900", "0900-1000", "1000-1100", "1100-1200","1200-1300","1300-1400","1400-1500","1500-1600","1600-1700","1700-1800","1800-1900","1900-2000","2000-2100","2100-2200","2200-2300","2300-2400"
            ],
            "axisLabel"=>[
                "interval"=>0,
                "rotate"=>40,
                "margin"=>10
            ]
        ]);
        $option->yAxis([
            'type' => 'value',
            'min' => 0,
            'splitNumber' => 5
        ]);
        $option->grid([
                'left' => '4%',
                'right' => '4%',
                'bottom' => '3%',
                'top' => '17%',
                'containLabel' => true
            ]
        );
        $option->legend([
            "data" => $data['area'],
            "backgroundColor" => 'white',
            "top" => '0',
        ]);
        $option->label = [
            'show' => true
        ];
        $option->series($data['list']);
        $chart = new Line();
        $option->addSeries($chart);
        $echarts->option($option);
        return $echarts->render();
    }

    /**
     * 智能设备折线图（侦测趋势 (按日期)）数据
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getLineDateData($param)
    {
        $CustomerID = self::getCustomerID($param);
//        $CustomerID = 'McDonald_Star_House';
        //获取触发数据查询时间段
        $timeData = self::getTimeSlot($param);
        if(!empty($timeData)){
            $startDate = $timeData['data']['JobDate'];
            $endDate = $timeData['endDate'];
            $where[] = ['triggerDate','>=',$startDate];
            $where[] = ['triggerDate','<=',$endDate];
        }else{
            $where = [];
        }
        if(!$where) return [];
        //所有设备
        $CustomerDeviceModel = new CustomerDeviceModel();
        $allDevice = $CustomerDeviceModel
            ->where('CustomerID',$CustomerID)
            ->field('Device_ID,floor,layer,others')
            ->select();
        if($allDevice->isEmpty()) return [];
        $allDevice = $allDevice->toArray();
        $allDeviceIds = array_column($allDevice,'Device_ID',null);
        $allDevice = array_column($allDevice,null,'Device_ID');
        //设备触发数据
        $tiggerData = (new TriggerDeviceModel)
            ->where($where)
            ->where('CustomerID',$CustomerID)
            ->whereIn('Device_ID',$allDeviceIds)
            ->field('Device_ID,triggerDate')
            ->select();
        if($tiggerData->isEmpty()) return [];
        $tiggerData = $tiggerData->toArray();
        $allDate = createDateRange($startDate,$endDate);
        $area = [];
        $deviceList=[];
        foreach ($tiggerData as $key=>$item){
            $flo = $allDevice[$item['Device_ID']]['floor'] . '-' .$allDevice[$item['Device_ID']]['layer']. '-' .$allDevice[$item['Device_ID']]['others'];
            $tiggerData[$key]['area'] = $flo;
            $area[] = $flo;
            if(!empty($deviceList[$flo])){
                $deviceList[$flo][] = $item;
            }else{
                $deviceList[$flo][] = $item;
            }
        }
        $area = array_values(array_unique($area));
        if(!$deviceList) return [];
        $list = [];
        foreach ($deviceList as $key=>$item){
            $DateArr = [];
            for($i=0;$i<count($allDate);$i++){
                $DateArr[$i] = 0;
            }
            foreach ($item as $v){
                $DateArr_key = array_search($v['triggerDate'], $allDate);
                $DateArr[$DateArr_key] +=1;
            }
            $arr['name'] = $key;
            $arr['type'] = 'line';
            $arr['data'] = $DateArr;
            array_push($list,$arr);
        }
        return ['area'=>$area,'list'=>$list,'allDate'=>$allDate];
    }

    /**
     * 智能设备折线图（侦测趋势 (按日期)）
     * @param $param
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getSmarttechLineDateInfo($param)
    {
        $data = self::getLineDateData($param);
        if(!$data) return [];
        $echarts = ECharts::init("#chartLineDate");
        $option = new Option();
        $option->animation(false);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae','#3300ff', '#339933','#660099','#336600','#330000','#749f83','#458a77','#660000','#6633ff','#990099','#996600','#996699','#ff9900','#ffcc66']);
        $option->xAxis([
            "type" => "category",
            "boundaryGap" => false,
            "data" => $data['allDate'],
            "axisLabel"=>[
                "interval"=>0,
                "rotate"=>40,
                "margin"=>10
            ]
        ]);
        $option->yAxis([
            'type' => 'value',
            'min' => 0,
            'splitNumber' => 5
        ]);
        $option->grid([
                'left' => '4%',
                'right' => '4%',
                'bottom' => '3%',
                'top' => '17%',
                'containLabel' => true
            ]
        );
        $option->legend([
            "data" => $data['area'],
            "backgroundColor" => 'white',
            "top" => '0',
        ]);
        $option->label = [
            'show' => true
        ];
        $option->series($data['list']);
        $chart = new Line();
        $option->addSeries($chart);
        $echarts->option($option);
        return $echarts->render();
    }

    /**
     * 客户点评
     * @param $param
     * @return mixed
     */
    public static function getCustomerCommentsInfo($param)
    {
        return (new AutographV2())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->value('customer_grade');
    }

    /**
     * 报告签名
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getReportSignatureInfo($param)
    {
        $data = (new AutographV2())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->field('customer_signature_url,customer_signature_url_add,staff_id01_url,staff_id02_url,staff_id03_url')
            ->find();
        if(!$data) return [];
        $data->toArray();
        return [
            'customer_signature'=>[$data['customer_signature_url'],$data['customer_signature_url_add']],
            'staff'=>[$data['staff_id01_url'],$data['staff_id02_url'],$data['staff_id03_url']]
        ];
    }
}