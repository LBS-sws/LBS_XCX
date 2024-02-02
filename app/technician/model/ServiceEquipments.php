<?php

namespace app\technician\model;

use think\model;
class ServiceEquipments extends Model
{
    protected $table = 'lbs_service_equipments';

    public function getEqNumberAttr($value,$data)
    {
        return !empty($value) ? $value : '' ;
    }
    /**
     * 报告设备巡查-设备信息
     * @param $value
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeviceInfoAttr($value,$data)
    {
        $data =  (new EquipmentType())->where('id',$data['equipment_type_id'])->field('name,check_targt')->find()->toArray();
        return ['name'=>$data['name'],'check_targt'=> !empty($data['check_targt']) ? explode(',',$data['check_targt']) : []];
    }

    /**
     * 报告设备巡查-触发设备
     * @param $value
     * @param $data
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getTiggerCountAttr($value,$data)
    {
        return self::where('equipment_type_id',$data['equipment_type_id'])
            ->whereNotNull('equipment_area')
            ->where('job_id',$data['job_id'])
            ->whereNotNull('check_datas')
            ->count();
    }

    /**
     * 报告设备巡查-设备列表
     * @param $value
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getEquipmentListAttr($value,$data)
    {
        return self::where('equipment_type_id',$data['equipment_type_id'])
            ->whereNotNull('equipment_area')
            ->whereNotNull('check_datas')
            ->where('job_id',$data['job_id'])
            ->field('id,number,equipment_area,check_handle,check_datas,more_info')
            ->select()->toArray();
    }
}