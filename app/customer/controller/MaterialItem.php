<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;

class MaterialItem
{
    public function index()
    {

        $id = @$_POST['id'];
        $city = @$_POST['city'];

        $item = Db::table('lbs_service_materials')->where('id',$id)->find();
        // print_r($item);
        $name = $item['material_name'];

        $url = "https://dms.lbsapps.cn/sv-prod/index.php/JsonMateriallist/index?user=admin&ac=xcx_list&text=".$name."&city=".$city;
        $output = $this->http_curl_get($url);
        $json = utf8_encode($output);
        $res = json_decode($json,true);
        // print_r($res);

        $result['code'] = 1;
        $result['msg'] = '成功';
        $result['data'] = $res['item'];

        return json($result);
    }
    // curl
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
    //...
}
