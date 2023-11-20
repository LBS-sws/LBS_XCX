<?php
namespace app\jobs\service;

use app\common\model\ClientDeviceModel;
use app\common\model\CustomerDeviceModel;
use app\common\model\TriggerDeviceModel;

class SmarttechClientService
{
    /**
     * @return false|void
     * @throws \Exception
     */
    public static function getClientList()
    {
        $queryStr = "?page=1&perPage=1000&orderBy=key&order=asc";
        $url = config('app.smarttech_mousetrap_client_api.client').$queryStr;
        if(!isset($url)) return false;
        $json = file_get_contents($url);
        if(!$json) return false;
        $arr = json_decode($json,true);
        if(!$arr) return false;
        self::IncClientData($arr);
    }

    /**
     * @param $data
     * @return false|void
     * @throws \Exception
     */
    public static function IncClientData($data)
    {
        if(empty($data)) return false;
        $list=[];
        foreach ($data as $item){
            $arr['Client_Name'] = $item['name'];
            $arr['closingTime'] = $item['closingTime'];
            $arr['openingTime'] = $item['openingTime'];
            if(!empty($item['config'])){
                $arr['Client_Key'] = $item['config']['key'];
                if(!empty($item['config']['optionListMap'])) $arr['optionListMap'] = json_encode($item['config']['optionListMap'],true);
            }
            array_push($list,$arr);
        }
        (new ClientDeviceModel())->saveAll($list,false);
    }

}