<?php

namespace app\common\model;

use think\model;

class CustomerDeviceModel extends Model
{
    protected $table = 'lbs_customer_device';
    protected $pk = 'id';

    public function getAllTriggerCountAttr($value,$data)
    {
        return (new TriggerDeviceModel)->where('type',$data['type'])->where('CustomerID',$data['CustomerID'])->count();
    }

    public function getDeviceCnNameAttr($value,$data)
    {
        $arr = ['sigfox'=>'老鼠动能感应器','nbiot'=>'智能老鼠夹'];
        return $arr[$data['type']];
    }

    public function getDayTriggerCountAttr($value,$data)
    {
        $Date = date('Y-m-d',time());
        $startTime = '08:00:00';
        $endTime = '00:00:00';
        return (new TriggerDeviceModel)
            ->where('type',$data['type'])
            ->where('Device_ID',$data['Device_ID'])
            ->where('triggerDate','<=',$Date)
            ->where('triggerTime','>=',$startTime)
            ->where('triggerTime','<=',$endTime)
            ->count();
    }

    public function getNightTriggerCountAttr($value,$data)
    {
        $Date = date('Y-m-d',time());
        $startTime = '00:00:00';
        $endTime = '08:00:00';
        return (new TriggerDeviceModel)
            ->where('type',$data['type'])
            ->where('Device_ID',$data['Device_ID'])
            ->where('triggerDate','<=',$Date)
            ->where('triggerTime','>=',$startTime)
            ->where('triggerTime','<=',$endTime)
            ->count();
    }

    public function getAreaTiggerCountAttr($value,$data)
    {
        $deviceIds = self::where('CustomerID',$data['CustomerID'])->where('type',$data['type'])->where('area_group',$data['area_group'])->column('Device_ID','id');
        return (new TriggerDeviceModel)->where('type',$data['type'])->where('CustomerID',$data['CustomerID'])->whereIn('Device_ID',$deviceIds)->count();
    }

    public function getAreaNameGroupAttr($value,$data)
    {
        return $data['floor'].'-'.$data['layer'].'-'.$data['others'];
    }

    public function getAreaTirggetGroupAttr($value,$data)
    {
        $res = (new TriggerDeviceModel)
            ->field('triggerTime')
            ->where('type',$data['type'])
            ->where('CustomerID',$data['CustomerID'])
            ->where('floor',$data['floor_id'])
            ->where('layer',$data['layer_id'])
            ->where('group',$data['others_id'])
            ->select()
            ->toArray();
        if(!$res) return [];
        $hourArr = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,];
        foreach ($res as $key=>$item){
            $H = intval(date('H',strtotime($item['triggerTime'])));
            $hourArr[$H] +=1;
        }
        return $hourArr;
    }

    public function getDateTirggetGroupAttr($value,$data)
    {
        $res = (new TriggerDeviceModel)
            ->field('triggerDate')
            ->where('type',$data['type'])
            ->where('CustomerID',$data['CustomerID'])
            ->where('floor',$data['floor_id'])
            ->where('layer',$data['layer_id'])
            ->where('group',$data['others_id'])
            ->select()
            ->toArray();
        if(!$res) return [];
        $tArr = array_column($res,'triggerDate',null);
        $list = [];
        foreach ($tArr as $key=>$item){
            if(!isset($list[$item])){
                $list[$item] = 1;
            }else{
                $list[$item] += 1;
            }
        }
        return $list;
    }
}
