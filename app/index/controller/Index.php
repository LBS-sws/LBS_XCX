<?php
declare (strict_types = 1);
namespace app\index\controller;

class Index
{
    public function index()
    {
        // dd(app()->getRootPath());
        exit('LBS_GROUP');
        
    }
    public function test(){
        $url = 'https://xcx.lbsapps.cn/technician/reportAutograph';
        do {
          $result = $this->curl_request($url);
        //   var_dump($res);exit;
        //   $result = json_decode($res,true);
        ob_flush();

        flush();
        
        sleep(5);
          var_dump($result['msg']);
          var_dump($result['data'][0]['id']);
        } while($result['code'] == 0);
    }
    
    
    public function curl_request($url,$data = null)
    {
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $output = curl_exec($curl);
        curl_close($curl);

        $res = json_decode($output,true);

        return $res;
    }
    
}