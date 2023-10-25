<?php

namespace app\api\controller;

use app\BaseController;
use app\technician\model\CustomerCompany;
use app\technician\model\EquipmentAnalyse;
use app\common\model\JobOrder;
use app\technician\model\ServiceEquipments;
use app\technician\model\ServiceItems;
use app\technician\model\StatisticsReport;
use app\technician\model\AnalyseReport;
use beyong\echarts\charts\Bar;
use beyong\echarts\charts\Line;
use beyong\echarts\charts\Pie;
use beyong\echarts\ECharts;
use beyong\echarts\Option;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Db;
use think\Model;
use think\cache\driver\Redis;
class Crontab extends BaseController
{
    /**
     * 定义客户类型
     * */
    protected $custType = [203,249];

    protected $jobOrderModel = null;
    protected $customerCompanyModel = null;
    protected $serviceEquipments = null;
    protected $statisticsReport = null;
    protected $equipmentAnalyse = null;
    protected $analyseReport = null;

    protected $serviceItems = [];

    protected $result = [];

    protected $catch_equment = [];

    /**
     * 检查是不是工厂客户
     * @param int $job_id
     * @return array|false|mixed|Db|Model
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */

    public function __construct(App $app)
    {
        $this->jobOrderModel = new JobOrder();
        $this->customerCompanyModel = new CustomerCompany();
        $serviceItemsModel = new ServiceItems();
        $this->serviceEquipments = new ServiceEquipments();
        $this->statisticsReport = new StatisticsReport();
        $this->equipmentAnalyse = new EquipmentAnalyse();
        $this->analyseReport = new AnalyseReport();

        //加载所有items内容
        $this->serviceItems = $serviceItemsModel->items;
//        $this->result = $this->getBaseInfo();
        parent::__construct($app);
    }

    public function index()
    {
        $redis = new Redis();
        $res = $redis->clear();
        print_r($res);
    }

    public function getStatistics($info = []){
        $catch_equment = $this->serviceEquipments->alias('e')->field('j.JobDate,job_id,check_datas,equipment_type_id,equipment_number,equipment_name,equipment_area')->join('joborder j', 'j.JobID=e.job_id')->where('equipment_type_id', '<>', '113')->where('check_datas','<>','')->where('j.ServiceType','=',2)->where('job_id', 'in', $info['job_ids'])->select()->toArray();
        $this->catch_equment = $catch_equment;
        $original_array = [];
        foreach ($catch_equment as $k => $v) {
            $original_array[][$v['equipment_number']] = $v['check_datas'];
//            $original_array[] = $v['job_id'];
        }

        $total = [];
        foreach ($original_array as $k => $array) {
            foreach ($array as $k1 => $v1) {
                $json = json_decode($v1, true);
                foreach ($json as $item) {
                    if($item['label'] == "盗食占比"){
                        if($item['value'] == '无盗食'){
                            $item['value'] = 0;
                        }else{
                            $item['value'] = 1;
                        }
                    }
                    // $total[$k1][] = $item['label'];
                    $total[$k1][$item['label']][] = $item['value'];

                    // $total[] = [$item['label'] => $item['value']];

                }
            }
        }


        $sums = [];
        foreach ($total as $category => $pests) {
            foreach ($pests as $type => $values) {
                $sums[$category][$type] = array_sum($values);
                // $sums[$category][$type]['type'] = $category;
            }
        }
        // $sums = [];
        // $nums = 0;
        // foreach($total as $item) {
        //   foreach($item as $key => $value) {
        //     if(is_numeric($value)) {
        //       if(!isset($sums[$key])) {
        //         $sums[$key] = 0;
        //       }
        //       $sums[$key] += $value;
        //     }else{
        //       $sums[$key] += $value;
        //     }

        //     // {
        //     //     if($key == "盗食占比"){
        //     //         $nums++;
        //     //         $sums[$key] = $nums;

        //     //     }
        //     // }
        //   }
        // }

        //处理线条统计图
        $de_time = strtotime($info['JobDate']);
        $year = intval(date('Y',$de_time));
        $month = intval(date('m',$de_time));
        $statistics_where = [
            'year' => $year,
            'month' => $month,
            'customer_id' => $info['CustomerID'],
            'update_flag' => 1,
            'delete_flag' => 0
        ];
        //查询到本月此客户有数据了 就不去更新表了 除非去强制更新
        $res = $this->statisticsReport->where($statistics_where)->select();
        // $equipment_type = Db::query("SELECT * FROM `lbs_service_equipment_type` WHERE `city` = 'CN'");
        // $type_data = [];
        // foreach ($equipment_type as $type_k => $type_v){
        //     $type_data[$type_v['number_code']] = $type_v['check_targt'];
        // }




        $insert_data = [];
        foreach ($sums as $k => $v) {
            foreach ($v as $k1 => $v1) {
                $insert_data[$k][$k1]['year'] = $year;
                $insert_data[$k][$k1]['month'] = $month;
                $insert_data[$k][$k1]['customer_id'] = $info['CustomerID'];
                $insert_data[$k][$k1]['type_name'] = $k1;
                $insert_data[$k][$k1]['type_value'] = $v1;
                $insert_data[$k][$k1]['type_code'] = $k;
                $insert_data[$k][$k1]['update_flag'] = 1;
                $insert_data[$k][$k1]['delete_flag'] = 0;
            }
        }
        $resArr = [];
        foreach ($insert_data as $k=>$v){
            if(count($v) > 1){
                foreach ($v as $k1 => $v1){
                    $resArr[] = $v1;
                }
            }else{
                foreach ($v as $k2 => $v2){
                    $resArr[] = $v2;
                }
            }
        }

        $result1 = [];
        $result2 = [];
        $hash1 = [];
        $hash2 = [];

        foreach ($res as $a1) {
            $key = $a1['customer_id'] . $a1['type_name'] . $a1['type_code'] . $a1['year'] . $a1['month'];
            $hash1[$key] = $a1;
        }

        foreach ($resArr as $a2) {
            $key = $a2['customer_id'] . $a2['type_name'] . $a2['type_code'] . $a2['year'] . $a2['month'];
            $hash2[$key] = $a2;
        }

        foreach ($hash1 as $key => $a1) {
            if (isset($hash2[$key])) {
                $a2 = $hash2[$key];
                $result1[] = [
                    'year' => $a1['year'],
                    'month' => $a1['month'],
                    'customer_id' => $a1['customer_id'],
                    'type_name' => $a1['type_name'],
                    'type_value' => $a1['type_value'] + $a2['type_value'],
                    'type_code' => $a1['type_code'],
                    'update_flag' => $a1['update_flag'],
                    'update_at' => date('Y-m-d H:i:s'),
                    'delete_flag' => $a1['delete_flag'],
                ];
                unset($hash2[$key]);
            } else {
                $result2[] = [
                    'year' => $a1['year'],
                    'month' => $a1['month'],
                    'customer_id' => $a1['customer_id'],
                    'type_name' => $a1['type_name'],
                    'type_value' => $a1['type_value'],
                    'type_code' => $a1['type_code'],
                    'update_flag' => $a1['update_flag'],
                    'delete_flag' => $a1['delete_flag'],
                ];
            }
        }

        foreach ($hash2 as $a2) {
            $result2[] = [
                'year' => $a2['year'],
                'month' => $a2['month'],
                'customer_id' => $a2['customer_id'],
                'type_name' => $a2['type_name'],
                'type_value' => $a2['type_value'],
                'type_code' => $a2['type_code'],
                'update_flag' => 1,
                'delete_flag' => 0,
            ];
        }
        $this->statisticsReport->where($statistics_where)->delete();
        $res1 = $this->statisticsReport->saveAll($result1);
        if(count($result2)>0){
            $res2 = $this->statisticsReport->insertAll($result2);
        }
        if($res){
            return true;
        }
    }


    /**
     *查询今天有哪些工厂客户 例如 2022-04-29 然后查找当天下面有哪些客户信息
     *
     * */
    public function getAllJobInfo($date = '',$customer_id = ''){
        if(empty($date)){
            exit('错误！');
        }
        // $date = '2023-03-24';//date('Y-m-d')    ;
        $cc_where = [
            'j.JobDate' => $date,
            'j.Status' => 3,
            'j.ServiceType' => 2,
        ];
        if (isset($customer_id) && $customer_id != '') {
            $cc_where['j.CustomerID'] = $customer_id;
        }
        //查询今天有哪些工厂客户
        $cust = $this->customerCompanyModel->field('j.CustomerID,j.CustomerName,j.City,GROUP_CONCAT(j.JobID) as job_ids,j.JobDate')->alias('cc')->join('joborder j','j.CustomerID = cc.CustomerID')->whereIn('cc.CustomerType',$this->custType)->where($cc_where)->group('cc.CustomerID')->select()->toArray();
        //已取得所有客户下的 订单信息
        $get_today_statistics = [];
        foreach ($cust as $k => $v){
            $get_today_statistics[$k] = $this->getStatistics($v);
            $get_today_statistics[$k] = $this->getCatch($v);
            $condition = [
                'customer_id'=>$v['CustomerID'],
                'date'=>date('Y-m',strtotime($date)),
            ];
            //存在这个记录了
            $exits_flag = AnalyseReport::where($condition)->count();
            $city_ret = Db::query("select e.Text from enums as e left join officecity as o on o.Office=e.EnumID where o.City= ? and e.EnumType=8
;",[$v['City']]);
            if($exits_flag > 0){
                //有记录 忽略
            }else{
                try {
                    Db::startTrans();
                    $city = $city_ret[0]['Text'];
                    $data = [
                        'customer_id'=>$v['CustomerID'],
                        'customer_name'=>$v['CustomerName'],
                        'city'=>$city,
                        'date'=>date('Y-m',strtotime($date)),
                        'url_id'=>$this->uuid_str(),
                        'make_flag'=>-1,
                    ];
                    AnalyseReport::insert($data);
                    // 提交事务
                    Db::commit();
                }catch (\Exception $exception){
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
        echo json_encode($get_today_statistics);
    }
    public function uuid_str() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }



    /**
     * 查询所有设备捕捉到的数据
     * */
    public function getCatch($info = []){
        //查询捕捉到的设备数据

        $catch_equment = $this->serviceEquipments->alias('e')->field('j.JobDate,job_id,check_datas,number,equipment_type_id,equipment_number,equipment_name,equipment_area')->join('joborder j', 'j.JobID=e.job_id')->where('j.ServiceType','=',2)->where('equipment_type_id', '<>', '113')->where('equipment_number','not null')->where('job_id', 'in', $info['job_ids'])->select()->toArray();
        $this->catch_equment = $catch_equment;
        $month_data = [];
        foreach ($this->catch_equment as $k => $v) {
            if ($v['check_datas']) {
                $data = json_decode($v['check_datas'], true);
                $month_data[$k] = $v;
                $total = 0;
                // var_dump($data);
                if ($data != '') {
                    foreach ($data as $item) {

                        if($item['label'] == "盗食占比"){
                            if($item['value'] == '无盗食'){
                                $result  = 0;
                            }else{
                                $result = 1;
                            }
                        }else{
                            $result = $item['value'];
                            $result = gettype($result) == 'string' ? 0 : $result;
                        }
                        $total += $result;

                        // if($item['value'] == '无盗食'){
                        //     $item['value'] = 0;
                        // }else{
                        //     $item['value'] = 1;
                        // }

                        // var_dump($item['value']);
                        // if (is_numeric($item['value'])) {
                        //     $result = $item['value'];
                        // } else {
                        //     $result = 0;
                        // }
                        // // var_dump($result);
                        // $total += $result;

                    }
                    $month_data[$k]['total'] = $total;
                }
            }
        }
        $force_update = 1;
        $equipment_analyse_data = [];
        if ($force_update == 1) {
            foreach ($month_data as $k => $v) {
                $equipment_analyse_data[$k]['job_id'] = $v['job_id'];
                $equipment_analyse_data[$k]['job_date'] = $v['JobDate'];;
                $equipment_analyse_data[$k]['customer_id'] = $info['CustomerID'];
                $equipment_analyse_data[$k]['equ_type_id'] = $v['equipment_type_id'];
                $equipment_analyse_data[$k]['equ_type_num'] = $v['equipment_number'];
                $equipment_analyse_data[$k]['equ_area'] = $v['equipment_area'];
                $equipment_analyse_data[$k]['equ_type_name'] = $v['equipment_name'];
                $equipment_analyse_data[$k]['number'] = $v['number']??'';
                $equipment_analyse_data[$k]['pest_num'] = $v['total'];
                $equipment_analyse_data[$k]['created_at'] = date('Y-m-d H:i:s');
            }
            $res = $this->equipmentAnalyse->insertAll($equipment_analyse_data);
        }
    }

    public function checkCustInfo(int $job_id)
    {
        if (!empty($job_id)) {
            //->where('j.ServiceType','=',2)
            $where = ['JobID' => $job_id,'j.ServiceType' => 2];
            $cust = $this->jobOrderModel->alias('j')
                ->join('service s', 'j.ServiceType=s.ServiceType')->join('staff u', 'j.Staff01=u.StaffID')
                ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
                ->join('officecity oc', 'oc.City=u.City', 'left')
                ->join('officesettings os', 'os.Office=oc.Office', 'left')
                ->where($where)
                ->field('j.CustomerID,j.Mobile,j.JobDate,j.StartTime,j.FinishTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,s.ServiceName,j.Status,j.City,j.ServiceType,j.FirstJob,j.FinishDate,os.Tel')
                ->find()->toArray();
            $cust_name = '';
            if ($cust['Staff01'] != '') {
                $cust_name .= $cust['Staff01'];
            }
            if ($cust['Staff02'] != '') {
                $cust_name .= '、' . $cust['Staff02'];
            }
            if ($cust['Staff03'] != '') {
                $cust_name .= '、' . $cust['Staff03'];
            }
            $data['custInfo'] = $cust;
            $data['custInfo']['staffs'] = $cust_name;
            if (!empty($data['custInfo'])) {
                $where_c = [
                    'CustomerID' => $cust['CustomerID'],
//                    'CustomerType' =>  ['in', $this->custType],
                ];
                //查询是工厂客户才会继续走接下来的流程
                $cust_c = $this->customerCompanyModel->field('NameZH,CustomerID,Addr')->whereIn('CustomerType',$this->custType)->where($where_c)->find()->toArray();
                if ($cust_c) {
                    $data['cust_details'] = $cust_c;
                    return $data;
                }
            }
        }
        return false;
    }


    /**
     * 获取基本信息资料
     * @param string $month
     * @param int $job_id
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
//    PDYGR001-SH

    public function getBaseInfo(string $month = '2023-03', int $job_id = 1685139)
    {
        $mian_info = [];
        $cust = $this->checkCustInfo($job_id);
        $where = [
            'CustomerID' => $cust['cust_details']['CustomerID'],
            'ServiceType' => 2,
//            'DATE_FORMAT(jobDate,"%Y-%m")' => $cust['cust_details']['CustomerID'],
        ];
        //查看有哪些订单和日期
        $job_orders = $this->jobOrderModel->field('MAX(JobID) as JobID,GROUP_CONCAT(JobID) as joborders,GROUP_CONCAT(DISTINCT JobDate) as jobdate')->where($where)->where('DATE_FORMAT(jobDate,"%Y-%m")="' . $month . '"')->find();
        //查询有哪些 服务项目
        $job_items = $this->jobOrderModel->field('Item01, Item01Rmk, Item02, Item02Rmk, Item03, Item03Rmk, Item04, Item04Rmk, Item05, Item05Rmk, Item06, Item06Rmk, Item07, Item07Rmk, Item08, Item08Rmk, Item09, Item09Rmk, Item10, Item10Rmk, Item11, Item11Rmk, Item12, Item12Rmk, Item13, Item13Rmk, Remarks')->where($where)->where('DATE_FORMAT(jobDate,"%Y-%m")="' . $month . '"')->find()->toArray();
        foreach ($this->serviceItems as $key => $val) {
            if ($key == $cust['custInfo']['ServiceType']) {
                $result = $val;
                break;
            }
        }
        $service_subject = '';
        foreach ($result as $k => $v) {
            if ($job_items[$k] > 0) {
                if ($v[1] > 0) {
                    $service_subject .= $v[0] . ' ' . $job_items[$k . 'Rmk'] . '、';
                } else {
                    $service_subject .= $v[0] . '、';
                }
            }
        }
        //拼接 服务项目
        $service_subject = rtrim($service_subject, '、');
        //获取所有的设备情况
        $equpments = '';
        $equpment_nums = $this->serviceEquipments->alias('e')->join('lbs_service_equipment_type t', 'e.equipment_type_id=t.id', 'left')->field('t.name,e.equipment_type_id,COUNT(1) as num')->where('e.job_id', 'in', $job_orders['joborders'])->where('t.city', 'CN')->where('e.job_type', 1)->group('equipment_type_id')->select()->toArray();

        foreach ($equpment_nums as $k => $v) {
            $equpments .= $v['name'] . '-' . $v['num'] . '、';
        }
        $equpments = rtrim($equpments, '、');

        /** 虫害情况  只查询捕捉到的数据 113是【驱虫喷机】 */
        //->where('equipment_type_id', '<>', '113') 暂时不管
        $catch_equment = $this->serviceEquipments->alias('e')->field('j.JobDate,job_id,check_datas,equipment_type_id,equipment_number,equipment_name,equipment_area')->join('joborder j', 'j.JobID=e.job_id')->where('equipment_type_id', '<>', '113')->where('job_id', 'in', $job_orders['joborders'])->where('j.ServiceType','=',2)->select()->toArray();
        $this->catch_equment = $catch_equment;

        $original_array = [];
        foreach ($catch_equment as $k => $v) {
            $original_array[][$v['equipment_type_id']] = $v['check_datas'];
//            $original_array[] = $v['job_id'];
        }
//        $original_array = array_filter($original_array);
        $original_array = array_filter($original_array, function($innerArray) {
            return !empty(array_filter($innerArray));
        });
        // 首先，我们需要定义一个新的关联数组来存储结果
        $sums = [];
        // 然后，我们需要遍历输入数组中的每个元素
        foreach ($original_array as $main_key => $element) {
            // 将JSON字符串解码为关联数组
            $data = json_decode($element[array_key_first($element)], true);
            // 遍历解码后的关联数组中的每个元素
            foreach ($data as $key => $item) {
                // 如果结果数组中已经存在具有相同label的元素，则将它们的value相加
                if (array_key_exists($item['label'], $sums)) {
                    $sums[$item['label']] += $item['value'];
                }
                // 否则，将该元素添加到结果数组中
                else {
                    $sums[$item['label']] = $item['value'];
                }
            }
        }
        $line_keys = array_keys($sums);
        $line_keys_im = implode(',', $line_keys);
        $line['keys'] = explode(",", $line_keys_im);
        $line_values = array_values($sums);
        $line_values_im = implode(',', $line_values);
        $line_values_arr = explode(",", $line_values_im);

        $new_array = [];
        foreach ($line_values_arr as $value) {
            if ($value !== null) {
                $new_array[] = [
                    'value' => $value
                ];
            }
        }
        $line['values'] = $new_array;
        $mian_info['line'] = $line;
        //处理线条统计图
        $statistics_str = explode('-', $month);
        $year = intval($statistics_str[0]);
        $month = intval($statistics_str[1]);
        $statistics_where = [
            'year' => $year,
            'month' => $month,
            'customer_id' => $cust['cust_details']['CustomerID'],
            'update_flag' => 1,
            'delete_flag' => 0
        ];
        //查询到本月此客户有数据了 就不去更新表了 除非去强制更新
        $has_value = $this->statisticsReport->where($statistics_where)->count();

        $force_update = 0;
        $insert_data = [];
        if ($has_value <= 0 || $force_update == 1) {
            foreach ($sums as $k => $v) {
                $insert_data[$k]['year'] = $year;
                $insert_data[$k]['month'] = $month;
                $insert_data[$k]['customer_id'] = $cust['cust_details']['CustomerID'];
                $insert_data[$k]['type_name'] = $k;
                $insert_data[$k]['type_value'] = $v;
                $insert_data[$k]['update_flag'] = 1;
                $insert_data[$k]['delete_flag'] = 0;
            }
            $res = $this->statisticsReport->insertAll($insert_data);
        }
        //接下来的数据就直接查询该表中的数据就行
        $has_data = $this->statisticsReport->where($statistics_where)->select()->toArray();

        //类型名称arr
        $type_name = [];
        // foreach ($has_data as $id => $value) {
        //     $type_name[] = $value['type_name'];
        // }

        $data_line = [];
        foreach ($type_name as $k1 => $v1) {
            $data_line[$v1] = Db::query("SELECT GROUP_CONCAT(total_data_list) as k1 from(
SELECT COALESCE(SUM(type_value), 0) AS total_data_list
FROM (
  SELECT 1 AS month
  UNION SELECT 2 AS month
  UNION SELECT 3 AS month
  UNION SELECT 4 AS month
  UNION SELECT 5 AS month
  UNION SELECT 6 AS month
  UNION SELECT 7 AS month
  UNION SELECT 8 AS month
  UNION SELECT 9 AS month
  UNION SELECT 10 AS month
  UNION SELECT 11 AS month
  UNION SELECT 12 AS month
) AS months
LEFT JOIN lbs_statistics_report ON months.month = lbs_statistics_report.month AND lbs_statistics_report.year = ? AND lbs_statistics_report.type_name = ? AND lbs_statistics_report.delete_flag = 0
GROUP BY months.month) as k", [$year, $v1]);
        }
        $arr = [];
        foreach ($data_line as $k => $v) {
            $data_ret = explode(",", $v[0]['k1']);
            $arr[] = [
                //线条的title
                'name' => $k,
                'type' => 'line',
                'stack' => 'Total',
                'data' => $data_ret,
                'itemStyle' => [
                    'normal' => [
                        'label' => [
                            'show' => true,
                            'position' => 'top',
                            'textStyle' => [
                                'color' => 'black',
                                'fontSize' => 8
                            ]
                        ]
                    ]
                ],

            ];
        }
        //防止没设置group by炸裂

        // Db::execute("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        // $equment_type = $this->serviceEquipments->field('equipment_type_id,equipment_name as name,count(1) as value')->where('equipment_type_id', '<>', '113')->where('job_id', 'in', $job_orders['joborders'])->group('equipment_type_id')->select()->toArray();

        // dd($this->serviceEquipments->getLastSql());
        //查询飞虫的数据
        $data_insect_bar = [];
        $data_rodent_bar = [];
        foreach ($data_line as $k => $v) {
            if ($k == '老鼠') {
                $data_rodent_bar['鼠饵站' . '-' . $k] = explode(',', $v[0]['k1']);
            } else {
                $data_insect_bar['灭蝇灯' . '-' . $k] = explode(',', $v[0]['k1']);
            }
        }

        //查询某个设备捕捉到的虫害数量最多的统计
//        $equment_type1 = $this->serviceEquipments->field('equipment_area,equipment_type_id,equipment_name as name,count(1) as value')->where('equipment_type_id', '<>', '113')->where('job_id', 'in', $job_orders['joborders'])->group('equipment_type_id')->select()->toArray();

        $month_data = [];
        foreach ($this->catch_equment as $k => $v) {
            if ($v['check_datas']) {
                $data = json_decode($v['check_datas'], true);
                $month_data[$k] = $v;
                $total = 0;
                if ($data != '') {
                    foreach ($data as $item) {
                        $total += $item['value'];
                    }
                    $month_data[$k]['total'] = $total;
                }
            }
        }
        $force_update = 0;
        if ($force_update == 1) {
            $equipment_analyse_data = [];
            foreach ($month_data as $k => $v) {
                $equipment_analyse_data[$k]['job_id'] = $v['job_id'];
                $equipment_analyse_data[$k]['job_date'] = '2023-5-20';;
                $equipment_analyse_data[$k]['customer_id'] = $cust['cust_details']['CustomerID'];
                $equipment_analyse_data[$k]['equ_type_id'] = $v['equipment_type_id'];
                $equipment_analyse_data[$k]['equ_type_num'] = $v['equipment_number'];
                $equipment_analyse_data[$k]['equ_area'] = $v['equipment_area'];
                $equipment_analyse_data[$k]['equ_type_name'] = $v['equipment_name'];
                $equipment_analyse_data[$k]['pest_num'] = $v['total'];
                $equipment_analyse_data[$k]['created_at'] = date('Y-m-d H:i:s');
            }
            $res = $this->equipmentAnalyse->insertAll($equipment_analyse_data);
        }

        // 查询每个月设备捕捉数量最多的设备（只展示每个种类的前3条数据）

        $pest_res = Db::query("  SELECT
	t1.job_month,
  t1.equ_type_id,
  t1.pest_num,t1.equ_type_name,t1.equ_area,t1.job_date,t1.equ_type_num
FROM (
  SELECT
    DATE_FORMAT(job_date, '%Y-%m') AS job_month,
    equ_type_id,
    pest_num,
		customer_id,
		equ_type_name,
		equ_type_num,
		job_date,
		equ_area,
    (
      SELECT COUNT(DISTINCT t2.pest_num)
      FROM lbs_service_equipment_analyse t2
      WHERE t2.equ_type_id = t1.equ_type_id
        AND t2.pest_num > t1.pest_num
        AND DATE_FORMAT(t2.job_date, '%Y-%m') = DATE_FORMAT(t1.job_date, '%Y-%m')
    ) AS rank
  FROM lbs_service_equipment_analyse t1
) t1
WHERE t1.rank < 3
AND t1.customer_id = ?
GROUP BY t1.job_month, t1.equ_type_id, t1.pest_num
ORDER BY t1.job_month, t1.equ_type_id, t1.pest_num DESC;",[$cust['cust_details']['CustomerID']]);

        $pest_grouped_data = array_reduce($pest_res, function($result, $item) {
            $result[$item['job_month']][$item['equ_type_id']][] = $item;
            return $result;
        }, []);
        $mian_info['pest_grouped_data'] = $pest_grouped_data;
        $mian_info['data_insect_bar'] = $data_insect_bar;
        $mian_info['data_rodent_bar'] = $data_rodent_bar;
        $mian_info['pie'] = $equment_type??[];
        $mian_info['lion_title'] = $type_name;
        $mian_info['lion_origin'] = $data_line;
        $mian_info['lion_content'] = $arr;        //先去查询构造表里边有没有数据
        $mian_info['cust'] = $cust;
        $mian_info['joborder'] = $job_orders;
        $mian_info['service_subject'] = $service_subject;
        $mian_info['equpments'] = $equpments;
        $mian_info['month'] = date('Y年m月', strtotime($month));
        return $this->result = $mian_info;
    }


    public function createAnalyse($year,$month){
        $analyse_res = Db::query("SELECT DISTINCT customer_id FROM lbs_statistics_report WHERE `year` = ? AND `month` = ?",[$year,$month]);
        var_dump($analyse_res);
    }

    public function makeAnalyseReport($date = '2023-05'){
        $where = [
            'date'=>$date,
            'make_flag'=>-1,
        ];
        $res = $this->analyseReport::where($where)->findOrEmpty();
        if(empty($res)){
            return error();
        }
        $empty_equ = $this->equipmentAnalyse::where('customer_id','=',$res->customer_id)->count();
        if($empty_equ == 0){
            try {
                $equ_ret = $this->analyseReport->update(['id'=>$res->id,'make_flag'=>9]); // 9 就是已经噶了
            }catch (Exception $e){
                return error(0,$e->getMessage());
            }
            return error(0,$equ_ret);
        }else{
            $file_path = 'analyse/'.$date.'/'.$res->url_id.'.pdf';
            $this->analyseReport->update(['id'=>$res->id,'make_flag'=>0]);
        }

        if (is_file($file_path)) {
//            $domain = $this->request->domain().'/';
            $url = '/'.$file_path;
            //有报告就返回，没返回就
            return success(0,'success',$url);
        } else {
            $analyse_ret=  new Analyse();
            $report = $analyse_ret->index($date,$res->customer_id,$res->city,$res->url_id);
            if($report){
                $domain = $this->request->domain().'/';
                $url = $domain.$file_path;
                //有报告就返回，没返回就
                return success(0,'success',$url);
            }
        }
    }


}
