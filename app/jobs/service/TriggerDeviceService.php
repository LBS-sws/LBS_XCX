<?php
namespace app\jobs\service;

use app\common\model\CustomerDeviceModel;
use app\common\model\TriggerDeviceModel;

class TriggerDeviceService
{
    public static function getDeviceList($type)
    {
        $eventTimeFrom = '';
        $eventTimeTo = '';
        $queryStr = "?estate=kpkp-ZY&perPage=20000&orderBy=id&order=desc";
        switch ($type){
            case 'sigfox':
                $url = config('app.smarttech_mousetrap_trigger_api.Sigfox_trigger').$queryStr;
                break;
            case 'nbiot':
                $url = config('app.smarttech_mousetrap_trigger_api.Nbiot_trigger').$queryStr.'&type=trapSensor';
                break;
            default:
                break;
        }
        if(!isset($url)) return false;
        $json = file_get_contents($url);
        if(!$json) return false;
        $arr = json_decode($json,true);
        if(!$arr) return false;
        self::IncDeviceTriggerData($arr,$type);
    }

    public static function IncDeviceTriggerData($data,$type)
    {
        if(empty($data)) return false;
        $list=[];
        foreach ($data as $item){
            $arr['type'] = $type;
            $arr['CustomerID'] = $item['sigfoxDevice']['estate'];
            $arr['Device_ID'] = $item['deviceId'];
            $arr['triggerDate'] = $item['triggerDate'];
            $arr['triggerTime'] = $item['triggerTime'];
            $arr['eventTime'] = $item['eventTime'];
            $arr['floor'] = $item['sigfoxDevice']['floor'];
            $arr['layer'] = $item['sigfoxDevice']['layer'];
            $arr['group'] = $item['sigfoxDevice']['group'];
            $arr['status'] = $item['sigfoxDevice']['status'];
//            $arr['Network_Status'] = $item['sigfoxDevice']['rawData']['isConnected'] ? 1 : 0;
//            $arr['linkQuality'] = $item['sigfoxDevice']['rawData']['linkQuality'] ?? '';
//            $arr['Battery_Level'] = $item['sigfoxDevice']['rawData']['currentBattery'];
            array_push($list,$arr);
        }
        (new TriggerDeviceModel())->saveAll($list,false);
    }

}