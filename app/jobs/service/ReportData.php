<?php
namespace app\jobs\service;

use app\common\model\ServiceBriefingsModel;
use app\common\model\ServiceMaterialsModel;
use app\common\model\ServicePhotosModel;
use app\technician\model\JobOrder;

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
            ->where('JobID',$param['jobid'])
            ->append(['staff','device','task_type'])
            ->with('ServiceName')
            ->find()->toArray();
        return self::serviceProjects($param['jobtype'],$data['ServiceType'],$data);
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
        return (new ServiceBriefingsModel())->where('job_id',$param['jobid'])->where('job_type',$param['jobtype'])->field('content,proposal')->find();
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
            ->where('job_id',$param['jobid'])
            ->where('job_type',$param['jobtype'])
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
            ->where('job_id',$param['jobid'])
            ->where('job_type',$param['jobtype'])
            ->field('material_name,processing_space,material_ratio,dosage,unit,use_mode,targets,use_area,matters_needing_attention')
            ->select()->toArray();
    }

    public static function getRiskInfo($param)
    {

    }
}