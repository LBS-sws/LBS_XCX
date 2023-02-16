<?php

namespace app\technician\controller;

use app\technician\model\Autograph;
use app\technician\model\AutographV2;
use app\technician\model\JobOrder;
use app\technician\model\FollowupOrder;
use think\facade\Db;

class reportAutograph
{
    public function index()
    {


        $model = new Autograph();
        $modelV2 =  new AutographV2();
//        $followupOrderModel =  new FollowupOrder();
//        $total = $model->count();
//        $size = 10;
//        $page = request()->get('page');
        //参数 page 页数  listRows 每页数量
        $list = $model->where('conversion_flag','=',0)->page(1, 10)->select()->toArray();
        if(empty($list)){
            return error(-1, '没有可执行的数据', []);
        }
        //创建的日期
        $create_date = (date('Ymd', strtotime($list[0]['creat_time'])));
        $staff_dir = 'signature/staff/' . $create_date . '/';
        $customer_dir = 'signature/customer/' . $create_date . '/';
        $data = [];
        $model->startTrans();
        try {
            foreach ($list as $k => $value) {
                $data_x[$k]['id'] = $value['id'];
                $data[$k]['id'] = $value['id'];
                $data_x[$k]['job_id'] = $value['job_id'];
                $data_x[$k]['job_type'] = $value['job_type'];
                $data_x[$k]['staff_id01_url'] = $this->conversionToImg($value['employee01_signature'], $staff_dir);
                $data_x[$k]['staff_id02_url'] = $this->conversionToImg($value['employee02_signature'], $staff_dir);
                $data_x[$k]['staff_id03_url'] = $this->conversionToImg($value['employee03_signature'], $staff_dir);
                //$data[$k]['employee01_signature'] = '';
                //$data[$k]['employee02_signature'] = '';
                //$data[$k]['employee04_signature'] = '';
                $data[$k]['conversion_flag'] = 1;
                $data_x[$k]['customer_grade'] =  $value['customer_grade'];
                $data_x[$k]['creat_time'] =  $value['creat_time'];
                if ($value['customer_signature'] == 'undefined' || $value['customer_signature'] == '') {
                    $value['customer_signature'] = '';
                }
                $data_x[$k]['customer_signature_url'] = $this->conversionToImg($value['customer_signature'], $customer_dir);;
            }
            $res = $model->saveAll($data);
            $res1 = $modelV2->insertAll($data_x);
            sleep(2);
            $model->commit();
            return success(0, 'ok', $res);
        } catch (\Exception $exception) {
            $model->rollback();
            return error(-1, 'error', $exception->getMessage());
        }
    }

    public function conversionToImg($base64_image_content, $path)
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            if (!is_dir($path)) {
                mkdir($path, 0700, true);
            }
            if (!file_exists($path)) {
                mkdir($path, 0777, true);//0777表示文件夹权限，windows默认已无效，但这里因为用到第三个参数，得填写；true/false表示是否可以递归创建文件夹
            }
            //害怕重复  生成唯一的id
            $new_file = $path . $this->unique_str() . ".{$type}";

            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return '/' . $new_file;
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * 生成唯一的图片编号
     * */
    public function unique_str()
    {
        $charid = strtolower(md5(uniqid(mt_rand(), true)));
        return substr($charid, 0, 8) . substr($charid, 8, 4) . substr($charid, 12, 4) . substr($charid, 16, 4) . substr($charid, 20, 12);
    }

}