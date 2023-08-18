<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;

class Papers
{
    public function index()
    {
        exit;
    }
    // 小程序证件详情
    public function item(){

        $key = trim($_POST['code']);

        $data = Db::table('lbs_papersstaff_info')->where('StaffCode','=',$key)->select()->toArray();

        foreach ($data as $key=>$val){
            $arrImg = [];
            $arr = explode (",", $val['imgUrl']);

            if(count($arr)==1){
                $arrImg[0] = $arr[0];
            }else if(count($arr)>1){
                foreach($arr as $k=>$v){
                    $arr[$k] = $v;
                }
                // $arrImg =implode(",",$arr);
                // explode
                $arrImg = $arr;
            }
            $data[$key]['a'] = $arrImg;
        }

        return json($data);
    }
    // 小程序公司资质详情
    public function company(){

        $name = trim($_REQUEST['name']);
        $city = trim($_REQUEST['city']);

        $item = Db::name('lbs_company')->where([['name','=',$name],['city','=',$city]])->find();
        // dd($item);

        $arr = explode(',',$item['file_names']);
        // dd($arr);

        foreach ($arr as $key=>$val){
            $arr[$key] = 'https://xcx.lbsapps.cn/company/'.$val;
        }

        // print_r($arr);exit;
        $data['item'] = $item;
        $data['list'] = $arr;
        return json($data);
    }
    public function http_curl_get($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}


