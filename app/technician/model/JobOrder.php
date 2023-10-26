<?php

namespace app\technician\model;

use app\common\model\ServiceModer;
use app\common\model\StaffModer;
use think\model;
class JobOrder extends Model
{
    protected $table = 'joborder';

    /**
     * 获取所有员工的名称
     * @param $value
     * @param $data
     * @return string
     */
    public function getStaffAttr($value,$data)
    {
        $StaffArr = array_filter([$data['Staff01'],$data['Staff02'],$data['Staff03']]);
        if(!empty($StaffArr)){
            $data = (new StaffModer())->whereIn('StaffID',$StaffArr)->column('StaffName','StaffID');
            return !empty($data) ? implode(',',$data) : '';
        }
        return '';
    }

    /**
     * 获取所有的设备
     * @param $value
     * @param $data
     * @return string
     */
    public function getDeviceAttr($value,$data)
    {
        $result = (new ServiceEquipments())
            ->where('job_id',$data['JobID'])
            ->field('equipment_name,COUNT(equipment_type_id) as count')
            ->group('equipment_type_id')
            ->select()
            ->toArray();
        $device = '';
        if(!empty($result)){
             foreach ($result as $key=>$item){
                 $device .= $item['equipment_name'] . '-' . $item['count'] . ' ';
             }
        }
        return $device;
    }

    /**
     * 任务类型  首次服务或者常规服务
     * @param $value
     * @param $data
     * @return string
     */
    public function getTaskTypeAttr($value,$data)
    {
        return $data['FirstJob'] == 1 ? '首次服务' : '常规服务';
    }

    /**
     * 服务关联
     * @return model\relation\HasOne
     */
    public function ServiceName()
    {
        return $this->hasOne(ServiceModer::class,'ServiceType','ServiceType')->field('ServiceName,ServiceType');
    }

}