<?php

namespace app\technician\controller;

use app\technician\model\Autograph;
use app\technician\model\AutographV2;
use app\technician\model\JobOrder;
use app\technician\model\FollowupOrder;
use think\facade\Db;

class ReportAutograph
{
    public function index()
    {
        $model = new Autograph();
        $modelV2 = new AutographV2();
//        $followupOrderModel =  new FollowupOrder();
//        $total = $model->count();
//        $size = 10;
//        $page = request()->get('page');
        //参数 page 页数  listRows 每页数量

        //读取json文件数据
        $num = file_get_contents('now.json');
        // dd($num);
//        $num=38200;
//        调整基数 默认为10
        $base_num = 1;

        $list = $model->where('id','>=',$num)->page(1, $base_num)->order('id asc')->select()->toArray();
        // dd($list[49]['id']);

//        SELECT * FROM `lbs_report_autograph` WHERE `id`>=38200 AND `conversion_flag` = 0 LIMIT 100
        if (empty($list)) {
            return error(-1, '没有可执行的数据', []);
        }
        //创建的日期
        $create_date = (date('Ymd', strtotime($list[0]['creat_time'])));
        $staff_dir = 'signature/staff/' . $create_date . '/';
        $customer_dir = 'signature/customer/' . $create_date . '/';
        $data = [];
        $modelV2->startTrans();
        try {
            foreach ($list as $k => $value) {
                $data_x[$k]['pid'] = $value['id'];
                $data_x[$k]['job_id'] = $value['job_id'];
                $data_x[$k]['job_type'] = $value['job_type'];
                $data_x[$k]['staff_id01_url'] = $this->conversionToImg($value['employee01_signature'], $staff_dir);
                $data_x[$k]['staff_id02_url'] = $this->conversionToImg($value['employee02_signature'], $staff_dir);
                $data_x[$k]['staff_id03_url'] = $this->conversionToImg($value['employee03_signature'], $staff_dir);
                //$data[$k]['employee01_signature'] = '';
                //$data[$k]['employee02_signature'] = '';
                //$data[$k]['employee04_signature'] = '';
                $data_x[$k]['conversion_flag'] = 1;
                $data_x[$k]['customer_grade'] = $value['customer_grade'];
                $data_x[$k]['creat_time'] = $value['creat_time'];
                $data_x[$k]['customer_signature_url'] = $this->conversionToImg($value['customer_signature'], $customer_dir);;
                if ($value['customer_signature'] == 'undefined' || $value['customer_signature'] == '' ||  $value['customer_signature'] == null) {
                    $value['customer_signature'] = '';
                    $data_x[$k]['customer_signature_url'] = '';
                }
            }
            file_put_contents('now.json', $num+$base_num);
            $res1 = $modelV2->insertAll($data_x);
            $modelV2->commit();
            return success(0, 'ok', $res1);
        } catch (\Exception $exception) {
            file_put_contents('now.json', $num);
            $modelV2->rollback();
            return error(-1, 'error', $exception->getMessage());
        }
    }
    public function cron_task(){
        if (ob_get_level() == 0) ob_start();
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        ini_set('default_socket_timeout', -1);

        set_time_limit(0);
        ob_end_clean();
        ob_implicit_flush();
        header('X-Accel-Buffering: no'); // 关键是加了这一行。
        //为了方便测试，这里逐单条添加入表
        for ($i=0; $i<20;$i++) {
            //   flush(); //ob_flush()定要组合使用 ，否则不起作用
            //   ob_flush();
            $this->index();
            echo ($i+1)."\r\n";  //必须要在循环中 打印哦 ，不然flush就不起作用了
            //当前apache通过浏览器访问
            if (strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'apache') !== false) {
                echo str_pad('',1)."\n";
            }
            sleep(2); //停留一秒观看浏览器 弹出信息
        }
        //   ob_end_flush();
        exit('ok');

    }

    public function conversionToImg($base64_image_content, $path)
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
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