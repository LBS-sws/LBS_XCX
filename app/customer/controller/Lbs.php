<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;
class Lbs
{
    /* 同步LBS员工证件 */
    public function index()
    {

        $url = "https://dms.lbsapps.cn/sv-prod/index.php/Json/index?user=admin&ac=xcx";

        $output = $this->http_curl_get($url);
        // print_r($output);exit;

        $json = utf8_encode($output);
        $res = json_decode($json,true);

        Db::query(" TRUNCATE TABLE `lbs_papersstaff` ");
        Db::query(" TRUNCATE TABLE `lbs_papersstaff_info` ");

        // 插入员工
        $data = $res['data'];

        foreach ($data as $key=>$val){
            Db::name('lbs_papersstaff')->insert(['name' => $val['name'], 'code' => $val['code'], 'city'=>$val['city'], 'create_time'=>$val['create_time'], 'update_time'=>$val['update_time']]);
        }

        // 域名
        $host = $res['host'];

        // 插入证件
        $list = $res['list'];
        foreach ($list as $key=>$val){
            $arr = explode (",", $val['imgUrl']);

            if(count($arr)==1){
                $arr = $host.$arr[0];
            }else{
                foreach($arr as $k=>$v){
                    $arr[$k] = $host.$v;
                }
                $arr =implode(",",$arr);
            }

            Db::name('lbs_papersstaff_info')->insert(['papersstaff_id' => $val['papersstaff_id'], 'StaffCode' => $val['StaffCode'], 'PapersName'=>$val['PapersName'], 'StartDate'=>$val['StartDate'], 'EndDate'=>$val['EndDate'], 'imgUrl'=>$arr]);
        }

        echo "success";
    }
    // 同步LBS公司信息
    public function company(){
        Db::query(" TRUNCATE TABLE `lbs_company` ");

        $url = "https://dms.lbsapps.cn/sv-prod/index.php/Json/index?user=admin&ac=xcx_company";

        $output = $this->http_curl_get($url);

        $json = utf8_encode($output);
        $res = json_decode($json,true);

        $listx = Db::name('lbs_company')->where('1=1')->field('id')->select()->toArray();
        $arr = array_column($listx, 'id');

        $list = $res['list'];
        foreach ($list as $key=>$val){

            $boolvalue = in_array($val['id'],$arr,false);
            if(!$boolvalue){
                $people_info = array_column($val['list'], 'img');
                $img = implode(',',$people_info);

                $people_filename = array_column($val['list'], 'phy_file_name');
                $filename = implode(',',$people_filename);

                $lcd = array_column($val['list'], 'lcd');
                $lcdStr = implode(',',$lcd);

                $lud = array_column($val['list'], 'lud');
                $ludStr = implode(',',$lud);

                Db::name('lbs_company')->insert(['id' => $val['id'], 'name' => $val['name'], 'city'=>$val['city'], 'tacitly'=>$val['tacitly'], 'list'=>$img, 'file_names'=>$filename, 'lcd'=>$lcdStr, 'lud'=>$ludStr]);
            }
            if($boolvalue){

            }
        }


        echo "success";

    }
    public function companyImg(){

        $name = trim($_REQUEST['name']);
        $city = trim($_REQUEST['city']);

        // $condition['name'] = $name;
        $condition['city'] = $city;
        $condition['tacitly'] = 1;

        $item = Db::name('lbs_company')->where($condition)->find();
        if(!$item){
            // $where['name'] = $name;
            $where['city'] = $city;
            $where['tacitly'] = 0;
            $item = Db::name('lbs_company')->where($where)->find();
        }
        // echo Db::name('lbs_company')->getLastSql();
        // if($item['imgBase'] == NULL || !$item['imgBase']){
        if(1){

            $text = $item['list'];
            $strArray = explode(',',$item['file_names']);

            $url = "https://dms.lbsapps.cn/sv-prod/index.php/Json/index?user=admin&ac=xcx_company_item&text=".$text;
            // $url = 'https://dms.lbsapps.cn/sv-uat/index.php/Json/index?user=admin&ac=xcx_company_item&text=/data/part1/docman/uat/4/0/91db4e38f557b380f327758cd6f054c2.png';

            $output = $this->http_curl_get($url);
            $json = utf8_encode($output);
            $res = json_decode($json,true);
            // print_r($res);exit;
            // $imgBaseStr = implode("|",$res);
            // dd($imgBaseStr);
            $arr = [];
            foreach ($res as $k=>$v){

                $base_img = $v;
                // //  设置文件路径和命名文件名称
                $path = "/data/lbs_xcx/public/company/";
                $prefix = "";//前缀可不写
                //$output_file = $prefix.time().rand(100,999).'.jpg';
                $output_file = $strArray[$k];
                $path = $path.$output_file;
                // dd($path);
                //  创建将数据流文件写入我们创建的文件内容中
                try {
                    $baseImg = base64_decode($base_img);
                    @file_put_contents($path,$baseImg);

                    array_push($arr,$path);
                } catch (\Exception $e) {
                    var_dump($e);
                }

            }
        }

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

