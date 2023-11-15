<?php
namespace app\jobs\service;

use app\common\model\AutographV2;
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
    protected static $jobOrderModel = null;

    public function __construct()
    {
        $this->jobOrderModel = new JobOrder();
//        $this->customerCompanyModel = new CustomerCompany();
//        $serviceItemsModel = new ServiceItems();
//        $this->serviceEquipments = new ServiceEquipments();
//        $this->statisticsReport = new StatisticsReport();
//        $this->equipmentAnalyse = new EquipmentAnalyse();
//        //加载所有items内容
//        $this->serviceItems = $serviceItemsModel->items;
    }

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
            ->field('JobID,CustomerName,Addr,ServiceType,JobDate,ContactName,Mobile,Staff01,Staff02,Staff03,FirstJob,Item01,Item02,Item03,Item04,Item05,Item06,Item07,Item08,Item09,Item10,Item11,Item12,Item13,Item13Rmk,Item12Rmk,Item09Rmk,Item08Rmk,Item07Rmk,Item06Rmk,Item05Rmk,Item10Rmk,Item11Rmk,Item04Rmk')
            ->where('JobID',$param['job_id'])
            ->append(['staff','device','task_type'])
            ->with('ServiceName')
            ->find()->toArray();
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
            ->select()->toArray();
        if(!empty($data)){
            foreach ($data as $key=>$item){
                $data[$key]['site_photos'] = !empty($item['site_photos']) ? explode(',',$item['site_photos']) : '' ;
            }
            return $data;
        }
        return '';
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
       return (new ServiceMaterialsModel())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->field('material_name,processing_space,material_ratio,dosage,unit,use_mode,targets,use_area,matters_needing_attention')
            ->select()->toArray();
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
        return (new Risks())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->append(['ct','site_img'])
            ->field('risk_types,risk_description,risk_targets,risk_rank,risk_proposal,take_steps,creat_time,site_photos')
            ->select()->toArray();
    }

    /**
     * 设备巡查
     * @param $param
     * @return mixed
     */
    public static function getDeviceInspectionInfo($param)
    {
        return (new ServiceEquipments())
            ->where('job_id',$param['job_id'])
            ->where('job_type',$param['job_type'])
            ->whereNotNull('equipment_area')
            ->whereNotNull('check_datas')
            ->group('equipment_type_id')
            ->field('equipment_type_id,job_id,count(id) as equipment_total_count')
            ->append(['device_info','tigger_count','equipment_list'])
            ->select()
            ->toArray();
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
        $CustomerID = self::getCustomerID($param);
        $CustomerID = 'McDonald_Star_House';
        if(!$CustomerID) return [];
        $CustomerDeviceModel = new CustomerDeviceModel();
        $deviceCount = $CustomerDeviceModel
            ->where('CustomerID',$CustomerID)
            ->append(['all_trigger_count','device_cn_name'])
            ->field('type,CustomerID,count(id) as device_count')
            ->group('type')
            ->select()
            ->toArray();
        if(!$deviceCount) return [];
        $list = array_column($deviceCount,null,'type');
        $allDevice = $CustomerDeviceModel
            ->where('CustomerID',$CustomerID)
            ->append(['day_trigger_count','night_trigger_count'])
            ->field('type,Device_ID,CustomerID,Device_Name,floor,layer,others')
            ->select()
            ->toArray();
        if(!$allDevice) return [];
        foreach ($allDevice as $key=>$item){
            if($list[$item['type']]) $list[$item['type']]['list'][] = $item;
        }
        return $list;
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
                $CustomerID = (new \app\common\model\JobOrder())->where('JobID',$param['job_id'])->value('CustomerID');
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

    public static function getCakeData($param)
    {
        $CustomerID = self::getCustomerID($param);
        $CustomerID = 'McDonald_Star_House';
        $data = (new CustomerDeviceModel())
            ->where('CustomerID',$CustomerID)
            ->group('area_group')
            ->field('CustomerID,type,area_group,type,floor,layer,others')
            ->append(['area_tigger_count','area_name_group'])
            ->select()
            ->toArray();
        if(!$data) return ['count'=>0,'data'=>[]];;
        $count = array_sum(array_column($data,'area_tigger_count'));
        $list=[];
        foreach ($data as $item){
            $percentage = bcmul(bcdiv(strval($item['area_tigger_count']),strval($count),2),'100'); //($item['area_tigger_count'] / $count) * 100;
            array_push($list,['value'=>$percentage,'name'=>$item['area_name_group']]);
        }
        return $list;
    }
    /**
     * 智能设备饼状图
     * @param $param
     * @return mixed
     */
    public static function getSmarttechCakeInfo($param)
    {
        $data = self::getCakeData($param);
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

    public static function getLineTimeData($param)
    {
        $CustomerID = self::getCustomerID($param);
        $CustomerID = 'McDonald_Star_House';
        $data = (new CustomerDeviceModel())
            ->where('CustomerID',$CustomerID)
            ->group('area_group')
            ->field('CustomerID,type,area_group,floor_id,layer_id,others_id,type,floor,layer,others')
            ->append(['area_name_group','area_tirgget_group'])
            ->select()
            ->toArray();
        if(!$data) return [];
        $list=[];
        $area=[];
        foreach ($data as $item){
            array_push($area,$item['area_name_group']);
            $arr = ['name'=>$item['area_name_group'],'type'=>'line','stack'=>'Total','data'=>$item['area_tirgget_group']];
            array_push($list,$arr);
        }
        return ['area'=>$area,'list'=>$list];
    }
    /**
     * 智能设备折线图（侦测趋势 (按时间)）
     * @param $param
     * @return mixed
     */
    public static function getSmarttechLineTimeInfo($param)
    {
        $data = self::getLineTimeData($param);
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
        $CustomerID = 'McDonald_Star_House';
        $data = (new CustomerDeviceModel())
            ->where('CustomerID',$CustomerID)
            ->group('area_group')
            ->field('CustomerID,type,area_group,floor_id,layer_id,others_id,type,floor,layer,others')
            ->append(['area_name_group','date_tirgget_group'])
            ->select()
            ->toArray();
        if(!$data) return [];
        $dateArr = (new TriggerDeviceModel)
            ->field('max(triggerDate) as max_date,min(triggerDate) as min_date')
//            ->where('type',$data['type'])
            ->where('CustomerID',$CustomerID)
            ->find()
            ->toArray();
        $allDate = createDateRange($dateArr['min_date'],$dateArr['max_date']);
        $Arr = [];
        foreach ($allDate as $key=>$item){
            $Arr[$item] = 0;
        }
        $list=[];
        $area=[];
        foreach ($data as $item){
            array_push($area,$item['area_name_group']);
            if(!empty($item['date_tirgget_group'])){
                foreach ($item['date_tirgget_group'] as $k=>$v){
                    $Arr[$k] += 1;
                }
            }
            $arr = ['name'=>$item['area_name_group'],'type'=>'line','stack'=>'Total','data'=>array_values($Arr)];
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
            ->field('customer_signature_url,staff_id01_url,staff_id02_url,staff_id03_url')
            ->find()->toArray();
        return [
            'customer_signature_url'=>$data['customer_signature_url'],
            'staff'=>[$data['staff_id01_url'],$data['staff_id02_url'],$data['staff_id03_url']]
        ];
    }
}