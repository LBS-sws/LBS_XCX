<?php
namespace app\jobs\service;

use app\common\model\CustomerDeviceModel;

class DeviceService
{
    public static function getDeviceList($type)
    {
        $queryStr = "?estate=McDonald_Star_House&floor=0&layer=0&group=Zone+1&status=active&page=1&perPage=25&orderBy=id&order=asc";
        switch ($type){
            case 'sigfox':
                $url = config('app.smarttech_mousetrap_device_api.SigfoxDevice').$queryStr.'&type=ratSense';
                break;
            case 'nbiot':
                $url = config('app.smarttech_mousetrap_device_api.NbiotDevice').$queryStr.'&type=trapSensor';
                break;
            default:
                break;
        }
        if(!isset($url)) return false;
        $json = file_get_contents($url);
        if(!$json) return false;
        $arr = json_decode($json,true);
        if(!$arr) return false;
        self::IncDeviceData($arr,$type);
    }

    public static function IncDeviceData($data,$type)
    {
        if(empty($data)) return false;
        $list=[];
        foreach ($data as $item){
            $arr['type'] = $type;
            $arr['CustomerID'] = $item['estate'];
            $arr['Client_Key'] = $item['estate'];
            $arr['Device_ID'] = $item['sigfoxId'];
            $arr['Device_Name'] = $item['name'];
            $arr['Device_Type'] = $item['type'];
            $arr['Network_Status'] = $item['rawData']['isConnected'] ? 1 : 0;
            $arr['linkQuality'] = $item['rawData']['linkQuality'] ?? '';
            $arr['Battery_Level'] = $item['rawData']['currentBattery'];
            $arr['Device_Status'] = $item['status'];
            if($item['floor']) $arr['floor'] = self::getFloor($item['estateObj'],$item['floor']);
            if($item['layer']) $arr['layer'] = self::getLayer($item['estateObj'],$item['layer']);
            if($item['group']) $arr['others'] = self::getOthers($item['estateObj'],$item['group']);
            array_push($list,$arr);
        }
        (new CustomerDeviceModel())->saveAll($list,false);
    }

    public static function getFloor($data,$id)
    {
        $cnName = '';
        $floor = $data['config']['optionListMap']['floor'];
        if(!empty($floor)){
            $floor = array_column($floor,'cnName','id');
            $cnName = $floor[$id];
        }
        return $cnName;
    }

    public static function getLayer($data,$id)
    {
        $cnName = '';
        $layer = $data['config']['optionListMap']['layer'];
        if(!empty($layer)){
            $layer = array_column($layer,'cnName','id');
            $cnName = $layer[$id];
        }
        return $cnName;
    }

    public static function getOthers($data,$id)
    {
        $cnName = '';
        $others = $data['config']['optionListMap']['others'];
        if(!empty($others)){
            $others = array_column($others,'cnName','id');
            $cnName = $others[$id];
        }
        return $cnName;
    }



}