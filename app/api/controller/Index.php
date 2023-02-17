<?php
declare (strict_types = 1);
namespace app\api\controller;
use app\technician\model\AutographV2;


class Index
{
    public function index()
    {
        exit('LBS_GROUP');
    }

    public function getSignUrl($job_id ='',$job_type = ''){
        if($job_id == '' || $job_type == ''){
            return error(-1,'参数为空',[]);
        }
        $model = new AutographV2();
        $params['job_id'] = $job_id;
        $params['job_type'] = $job_type;
        $result = $model->where($params)->find();
        if(empty($result)){
            return error(-1,'返回结果为空',[]);
        }
        return success(0,'ok',$result);
    }

    /**
    $url 访问地址
    $postfields 请求参数（json字符串）
    $headers 请求头
     */
    public function httpCurl($url, $postfields = '', $headers =['Content-Type:application/json;charset=UTF-8'])
    {
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 50);
        curl_setopt($ci, CURLOPT_TIMEOUT, 600);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }

    public function index1(){
        $params = [
            'job_type'=>2,
            'job_id'=>34654,
        ];
        $params_str = http_build_query($params);
        $res = $this->httpCurl('http://xcx.com/index.php/api/index/getSignUrl?'.$params_str);
        $res_de = json_decode($res,true);
        if(isset($res_de) && $res_de['code'] == 0){
            dd($res_de);
            //有图片进行处理

        }else{
            //继续查询lbs的数据库
        }

    }
}