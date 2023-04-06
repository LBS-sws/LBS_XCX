<?php

namespace app\technician\controller;

use app\BaseController;
use app\technician\model\CustomerCompany;
use app\technician\model\JobOrder;
use app\technician\model\ServiceEquipments;
use app\technician\model\ServiceItems;
use beyong\echarts\charts\Line;
use beyong\echarts\charts\Pie;
use beyong\echarts\charts\Bar;
use beyong\echarts\ECharts;
use beyong\echarts\Option;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Model;

class Analyse extends BaseController
{
    /**
     * 定义客户类型
     * */
    protected $custType = '101';

    protected $jobOrderModel = null;
    protected $customerCompanyModel = null;
    protected $serviceEquipments = null;
    protected $serviceItems = [];

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
        //加载所有items内容
        $this->serviceItems = $serviceItemsModel->items;
        parent::__construct($app);
    }

    public function checkCustInfo(int $job_id)
    {
        if (!empty($job_id)) {
            $where = ['JobID' => $job_id];
            $data = [];
            $cust = $this->jobOrderModel->alias('j')
                ->join('service s', 'j.ServiceType=s.ServiceType')->join('staff u', 'j.Staff01=u.StaffID')
                ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
                ->join('officecity oc', 'oc.City=u.City', 'left')
                ->join('officesettings os', 'os.Office=oc.Office', 'left')
                ->where($where)
                ->field('j.CustomerID,j.Mobile,j.JobDate,j.StartTime,j.FinishTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,s.ServiceName,j.Status,j.City,j.ServiceType,j.FirstJob,j.FinishDate,os.Tel')
                ->find()->toArray();
            $cust_name = '';
            if($cust['Staff01'] != ''){
                $cust['Staff01'];
            }
            $cust_name = $cust['Staff01'].'、'.$cust['Staff02'].'、'.$cust['Staff03'];
            dd($cust_name);
            exit();
            $data['custInfo'] = $cust;
            if (!empty($data['custInfo'])) {
                $where_c = [
                    'CustomerID' => $cust['CustomerID'],
//                    'CustomerType' => $this->custType,
                ];
                //查询是工厂客户才会继续走接下来的流程
                $cust_c = $this->customerCompanyModel->field('NameZH,CustomerID,Addr')->where($where_c)->find()->toArray();
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
     */
//    PDYGR001-SH
    public function getBaseInfo(string $month = '2023-03', int $job_id = 1685128)
    {
        $mian_info = [];
        $cust = $this->checkCustInfo($job_id);

        $where = [
            'CustomerID' => $cust['cust_details']['CustomerID'],
//            'DATE_FORMAT(jobDate,"%Y-%m")' => $cust['cust_details']['CustomerID'],
        ];
        //查看有哪些订单和日期
        $job_orders = $this->jobOrderModel->field('MAX(JobID) as JobID,GROUP_CONCAT(JobID) as joborders,GROUP_CONCAT(JobDate) as jobdate')->where($where)->where('DATE_FORMAT(jobDate,"%Y-%m")="' . $month . '"')->find();
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
        $equpment_nums = $this->serviceEquipments->alias('e')->join('lbs_service_equipment_type t', 'e.equipment_type_id=t.id', 'left')->field('t.name,e.equipment_type_id,COUNT(1) as num')->where('e.job_id', 'in', $job_orders['joborders'])->where('e.job_type', 1)->group('equipment_type_id')->select()->toArray();
        foreach ($equpment_nums as $k => $v) {
            $equpments .= $v['name'] . '-' . $v['num'] . '、';
        }
        $equpments = rtrim($equpments, '、');
        $mian_info['cust'] = $cust;
        $mian_info['service_subject'] = $service_subject;
        $mian_info['equpments'] = $equpments;
        return json($mian_info);
    }


    public function echars(): string
    {
        $echarts = ECharts::init("#myChart");
        $option = new Option();
        $option->animation(false);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae', '#749f83']);
        $option->xAxis(["data" => ['鼠(捕获)', '鼠(盗食)', '蟑螂', '苍蝇', '蚊子', '其他']]);
        $option->yAxis([]);
        $option->title([
            "text" => '8月虫害统计图',
            "left" => 'center'
        ]);
        $chart = new Bar();
        $chart->data = [[
            'value' => 200,
            'itemStyle' => [
                'color' => '#4587E7'
            ]],
            ['value' => 270,
                'itemStyle' => [
                    'color' => '#2f4554'
                ]],
            ['value' => 866,
                'itemStyle' => [
                    'color' => '#61a0a8'
                ]],
            ['value' => 220,
                'itemStyle' => [
                    'color' => '#d48265'
                ]],
            ['value' => 210,
                'itemStyle' => [
                    'color' => '#91c7ae'
                ]],
            ['value' => 620,
                'itemStyle' => [
                    'color' => '#749f83'
                ],
            ]];
        $chart->name = "8月害虫统计";
        $chart->itemStyle = [
            'normal' => [
                'label' => [
                    'show' => true,
                    'position' => 'top',
                    'textStyle' => [
                        'color' => 'black',
                        'fontSize' => 18
                    ]
                ]
            ]
        ];
        $option->addSeries($chart);
        $echarts->option($option);
        return $echarts->render();
    }


    public function chartLine()
    {
        $echarts = ECharts::init("#chartLine");
        $option = new Option();
        $option->animation(false);
        $option->title([
            "text" => '虫害趋势分析图',
            "left" => 'center',
            "borderWidth" => 0
        ]);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae', '#749f83']);
        $option->xAxis([
            "type" => "category",
//            "boundaryGap" => false,
            "data" => [
                '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'
            ],
        ]);
//        设置Y轴
        $option->yAxis([
            'name' => '数量(相对)',
            'type' => 'value',
            'min' => 0,
//            'max' =>10000 ,
            'splitNumber' => 5
        ]);


        $option->grid([
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true
            ]
        );

        $option->legend([
            "data" => [
                '鼠(捕获)', '鼠(盗食)', '蟑螂', '苍蝇', '蚊子', '其他'
            ],
            "backgroundColor" => 'white',
            "top" => '8%',
        ]);

        $option->series([
            [
                'name' => '鼠(捕获)',
                'type' => 'line',
                'stack' => 'Total',
                'data' => [2210, 1321, 1301, 134, 1123, 2320, 210, 2134, 1690, 1230, 2170, 330],
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

            ],
            [
                'name' => '鼠(盗食)',
                'type' => 'line',
                'stack' => 'Total',
                'data' => [5111, 1821, 1491, 2134, 2490, 3330, 3110, 1354, 3190, 2310, 1210, 3430],
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
            ],
            [
                'name' => '蟑螂',
                'type' => 'line',
                'stack' => 'Total',
                'data' => [1520, 2312, 2301, 4154, 4190, 4330, 4104, 1434, 4490, 2530, 2140, 4330],
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
            ],
            [
                'name' => '苍蝇',
                'type' => 'line',
                'stack' => 'Total',
                'data' => [3260, 3362, 3601, 3374, 3790, 3380, 3260, 1343, 903, 6230, 4210, 3301],
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
            ]
        ]);
        $chart = new Line();
        $chart->name = "8月害虫统计";
        $chart->itemStyle = [
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
        ];

        $option->addSeries($chart);
        $echarts->option($option);
        return $echarts->render();
    }


    public function chartPie()
    {
        $echarts = ECharts::init("#chartPie");
        $option = new Option();
        $option->animation(false);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae', '#749f83']);
        $option->title([
            "text" => '设备占比图',
            "left" => 'center'
        ]);
        $option->legend([
            "orient" => 'vertical',
            "left" => 'left',

        ]);
        $option->series([
//                'name' => 'Access From',
                'type' => 'pie',
                'radius' => '70%',
                'data' => [
                    ['value' => 1048, 'name' => '灭蝇灯'],
                    ['value' => 735, 'name' => '鼠饵站'],
                    ['value' => 580, 'name' => '粘鼠板'],
                ],
                "backgroundColor" => 'white',
                'label' => [
                    'normal' => [
                        'formatter' => '{b}:{c}: ({d}%)',
                        'textStyle' => [
                            'fontWeight' => 'normal',
                            'color' => 'black',
                            'fontSize' => 18
                        ]

                    ]
                ],
                'emphasis' => [
                    'itemStyle' => [
                        'shadowBlur' => 10,
                        'shadowOffsetX' => 0,
                        'shadowColor' => 'rgba(0, 0, 0, 0.5)'
                    ]
                ],
            ]
        );
//        $option->yAxis([]);
        $chart = new Pie();
//        $chart->name = "8月害虫统计";
        $chart->itemStyle = [
            'normal' => [
                'label' => [
                    'show' => true,
                    'position' => 'top',
                    'textStyle' => [
                        'color' => 'black',
                        'fontSize' => 18
                    ]
                ]
            ]
        ];
        $option->addSeries($chart);
        $echarts->option($option);
        return $echarts->render();
    }

    /**
     * 昆虫图表绘制
     * @return array
     **/
    public function moreInsectCharsBar(): array
    {
        $id = 1;
        $color = ['#4587E7'];
        $data = [5, 20, 36, 10, 10, 20, 3, 123, 41, 41, 45, 12];
        for ($i = 0; $i <= 2; $i++) {
            $result[] = $this->createEcharsBar($id . $i, $color, $data);
        }
        return $result;
    }

    /**
     * 鼠类图表绘制
     * @return array
     **/
    public function moreRodentEcharsBar(): array
    {
        $id = 1;
        $color = ['#e81010'];
        $data = [5, 213, 52, 5, 10, 20, 3, 123, 41, 41, 45, 12];
        for ($i = 0; $i <= 1; $i++) {
            $result[] = $this->createEcharsBar($id . '_1_' . $i, $color, $data);
        }
        return $result;
    }


    /**
     * 生成更多的bar 自定义
     * @param int $id
     * @param string[] $color
     * @param int[] $data
     * @return mixed
     * */
    public function createEcharsBar(string $id = '0', array $color = ['#4587E7'], array $data = []): string
    {
        $echarts = ECharts::init("#myChart" . $id);
        $option = new Option();
        $option->animation(false);
        $option->color($color);
        $option->xAxis(["data" => ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月']]);
        $option->yAxis([]);
        $option->title([
            "text" => '各数据库占有数据源情况柱状图',
            "left" => 'center'
        ]);
        $chart = new Bar();
        $chart->data = $data;
        $chart->name = "8月害虫统计";
        $chart->itemStyle = [
            'normal' => [
                'label' => [
                    'show' => true,
                    'position' => 'top',
                    'textStyle' => [
                        'color' => 'black',
                        'fontSize' => 18
                    ]
                ]
            ]
        ];
        $option->addSeries($chart);
        $echarts->option($option);
        return $echarts->render();
    }


    public function index()
    {
        $html = <<<EOF
<!DOCTYPE html>
<html lang="zh" data-color-mode="auto" data-light-theme="light_high_contrast" data-dark-theme="dark"
      data-a11y-animated-images="system">
<head>
    <meta charset="utf-8">
    <title>史伟莎有害生物控制总结及趋势分析报告</title>
    <style>
        .style-table {
            border-collapse:collapse;
            margin: 50px auto;
            font-size: 0.9em;
            width: 800px;
        }
        .echart-table{
            margin: 50px auto;
            font-size: 0.9em;
            min-width: 400px;
        }
        
        .echart-table-1{
            margin: 0 auto 0 auto;
            font-size: 0.9em;
            min-width: 400px;
            width: 800px;
        }
        
        .echart-table-2{
            margin: 0 auto 0 auto;
            font-size: 0.9em;
            min-width: 400px;
            /*box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);*/
        }
        .text-table-1{
            margin: 0 auto 0 auto;
            font-size: 0.9em;
            min-width: 400px;
            width: 800px;
            /*box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);*/
        }
        .text-table-1 th{
            font-size: 1.4em;
            font-weight: bold;
            float: left;
            padding-top: 30px;
           
        }
        .text-table-1 td{
            font-size: 1.2em;
            padding:15px 0 15px 40px
        }
        
        .echart-table thead tr {
            background-color: rgb(220, 230, 242);
            color: #ffffff;
            text-align: left;
        }
        
        .table-responsive {
            overflow-x: visible !important;
        }

        @page {
            margin-bottom: 10px;
        }

        .logo {
            width: 120px;
            height: 100px;
        }

        .big-title {
            font-size: 30px;
            font-weight: bold;
        }

        .title-right {
            float: right;
            font-size: 20px;
            font-weight: lighter;
        }

        .style-table thead tr {
            background-color: rgb(220, 230, 242);
            color: #ffffff;
            text-align: left;
        }

        .style-table th,
        .style-table td {
            padding: 12px 15px;
        }

        .style-table tbody tr {
            border: 1px solid #dddddd;
        }

        .style-table tbody tr:nth-of-type(even) {
            background-color: #ffffff;
        }

        .style-table tbody tr:last-of-type {
            border: 1px solid #5f7288;
        }

        .style-table tbody tr.active-row {
            font-weight: bold;
            /*color: #0398dd;*/
        }

        .first-th {
            font-size: x-large;
            border: 1px solid #cad9ea;
            color: #0c0c0c;
            height: 30px;
            padding: 12px 0 12px 20px;
            text-align: left;
        }

        .first-td {
            font-size: x-large;
            border: 1px solid #dddddd;
            color: #0c0c0c;
            height: 30px;
            padding: 5px 0 5px 0;
        }

        .secend-th {
            border: #ffffff;
            color: #0c0c0c;
            height: 30px;
            padding: 12px 0 12px 30px;
            text-align: left;
        }

        .secend-td {
            border: 1px solid #cad9ea;
            color: #0c0c0c;
            width: 32px;
            padding: 10px 10px 5px 0;
        }
        
        .third-th {
            border: #ffffff;
            color: #0c0c0c;
            height: 30px;
            padding: 12px 0 12px 30px;
            text-align: left;
        }
        .third-th .title{
            font-size:26px;
            font-weight: bold;
        }
        
        .third-th .td-title{
            font-weight: bold;
        }

        .third-td {
            border: 1px solid #cad9ea;
            color: #0c0c0c;
            width: 32px;
            padding: 10px 10px 5px 0;
        }
        .style-table-content {
            margin: 50px auto;
            font-size: 0.9em;
            width: 800px;
            /*box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);*/
        }
        
        .footer-td {
            color: #0c0c0c;
            width: 32px;
            padding: 10px 10px 5px 0;
        }
        
        .footer-td {
            color: #0c0c0c;
            width: 32px;
            padding: 10px 10px 5px 0;
        }


        .title-header {
            border-collapse: collapse;
            margin: 0 auto;
            text-align: center;
        }

        thead, tfoot {
            display: table-row-group;
        }

        .mian-title {
            background-color: rgb(220, 230, 242);
        }
    </style>
</head>

<body>
<div>


    <table class="title-header">
        <tr>
            <td rowspan="2">
                <img class="logo" src="https://files.wfnxs.cc/images/logo.png" alt="史伟莎LOGO">
            </td>
        </tr>
        <tr>
            <td>
                <div class="big-title">史伟莎有害生物控制总结及趋势分析报告</div>
                <div class="title-right">2023年8月份</div>
            </td>
    </table>
    <table class="style-table">
        <thead>
        <tr>
            <th class="first-th mian-title" colspan="13">基础信息</th>
        </tr>
        </thead>
        <tr>
            <th class="first-th">客户名称</th>
            <td class="first-td " colspan="12">史伟莎有害生物控制工厂史伟莎有害生史伟莎有害生物控制总结及趋势分析报告物控制总结及趋势分析报告</td>
        </tr>
        <tr>
            <th class="first-th">客户地址</th>
            <td class="first-td" colspan="12">成都市青羊区北大街正成财富领地</td>
        </tr>
        <tr>
            <th class="first-th" colspan="1">服务类型</th>
            <td class="first-td" colspan="6">灭虫</td>
            <th class="first-th" colspan="1">服务项目</th>
            <td class="first-td" colspan="6">老鼠、蟑螂、果蝇、</td>
        </tr>
        <tr>
            <th class="first-th">服务人员</th>
            <td class="first-td" colspan="6">李华</td>
            <th class="first-th">联系电话</th>
            <td class="first-td" colspan="7">4008649998</td>
        </tr>
        <tr>
            <th class="first-th">监测设备</th>
            <td class="first-td" colspan="12">灭蝇灯-10、鼠饵站-20、粘鼠板-10</td>
        </tr>
        <tr>
            <th class="first-th">服务日期安排</th>
            <td class="first-td" colspan="12">8月6日、8月15日、8月23日、8月29日</td>
        </tr>
    </table>
    <table class="echart-table-1">
        <thead>
        <tr>
            <th class="first-th mian-title" colspan="13">虫害控制情况</th>
        </tr>
        </thead>
        <tr>
            <th colspan="13">
                <div style="width: 800px;height: 100%">
                    {$this->echars()}
                </div>
            </th>
        </tr>
        
    </table>
    
    <table class="echart-table">
       
        <tr>
            <th colspan="13">
                <div style="width: 800px;height: 100%;border: none">
                    {$this->chartLine()}
                </div>
            </th>
        </tr>
    </table>
    <table class="style-table">
        <tr class="secend-th">
            <td class="secend-td">类型</td>
            <td class="secend-td">1月</td>
            <td class="secend-td">2月</td>
            <td class="secend-td">3月</td>
            <td class="secend-td">4月</td>
            <td class="secend-td">5月</td>
            <td class="secend-td">6月</td>
            <td class="secend-td">7月</td>
            <td class="secend-td">8月</td>
            <td class="secend-td">9月</td>
            <td class="secend-td">10月</td>
            <td class="secend-td">11月</td>
            <td class="secend-td">12月</td>
        </tr>
        <tr class="secend-th">
            <td class="secend-td">老鼠1</td>
            <td class="secend-td">0</td>
            <td class="secend-td">1</td>
            <td class="secend-td">3</td>
            <td class="secend-td">4</td>
            <td class="secend-td">4</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">6</td>
            <td class="secend-td">6</td>
            <td class="secend-td">7</td>
        </tr>
        <tr class="secend-th">
            <td class="secend-td">老鼠1123</td>
            <td class="secend-td">0</td>
            <td class="secend-td">1</td>
            <td class="secend-td">3</td>
            <td class="secend-td">4</td>
            <td class="secend-td">4</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">6</td>
            <td class="secend-td">6</td>
            <td class="secend-td">7</td>
        </tr>
        <tr class="secend-th">
            <td class="secend-td">老鼠1123</td>
            <td class="secend-td">0</td>
            <td class="secend-td">1</td>
            <td class="secend-td">3</td>
            <td class="secend-td">4</td>
            <td class="secend-td">4</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">6</td>
            <td class="secend-td">6</td>
            <td class="secend-td">7</td>
        </tr>
        <tr class="secend-th">
            <td class="secend-td">老鼠1</td>
            <td class="secend-td">0</td>
            <td class="secend-td">1</td>
            <td class="secend-td">3</td>
            <td class="secend-td">4</td>
            <td class="secend-td">4</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">5</td>
            <td class="secend-td">6</td>
            <td class="secend-td">6</td>
            <td class="secend-td">7</td>
        </tr>
    </table>
    <table class="echart-table-1">
       <thead>
        <tr>
            <th class="first-th mian-title" colspan="13">设备统计及分析</th>
        </tr>
       </thead>
        <tr>
            <th colspan="13" style="padding-top: 30px">
                <div style="width: 800px;height: 100%;border: none">
                    {$this->chartPie()}
                </div>
            </th>
        </tr>
    </table>
    
    <table class="echart-table-2">
        <tr>
            <th colspan="13">
                <div style="width: 800px;height: 100%;border: none">
EOF
            . implode('', $this->moreInsectCharsBar());
        $html .= <<<EOF
                </div>
            </th>
        </tr>
    </table>
    
    <table class="text-table-1" style="border: 1px solid;margin-top: 200px">
       
        <tr>
            <th colspan="13">
                飞虫分析：
            </th>
        </tr>
        <tr>
            <td colspan="13">
a）本月捕获飞虫总数为（ ）只，较上月呈（下降/上升）趋势，这与冬
 季和初春气温持续处于较低水平有关：环境气温降低对外围飞虫抑制
 作用，其密度和活跃度下降，导致捕获量下降。
            </td>
        </tr> <tr>
            <td colspan="13">
b）本月以（蚊子）捕获量稍高，与（ ）有关；（ ）捕获量较少，整体
 情况良好，请注意保持以上洁净程度较高区域门体管理，做到随开随
 关，以减少飞虫入侵。
            </td>
        </tr>
        
         <tr>
            <th colspan="13">
                建议：
            </th>
        </tr>
        <tr>
            <td colspan="13">
√进入春季后，环境温度将逐渐恢复到较高水平，外围飞虫密度和活跃
 度都将逐渐上升，其入侵风险也将随之升高。请注意各类对外门窗的
 管理，避免长时间开启导致的飞虫入侵；请注意各类密封措施的有效
 性，避免因密封不佳到的的飞虫入侵；请注意风幕机、胶帘等防虫设
 施的正确应用，以起到相应的防制效果。
            </td>
        </tr>
        
         <tr>
            <th colspan="13">
                措施：
            </th>
        </tr>
        <tr>
            <td colspan="13">
            √史伟莎将持续进行飞虫风险排查以帮助控制飞虫密度。
            </td>
        </tr>
    </table>
    
    <table class="echart-table-2">
        <tr>
            <th colspan="13">
                <div style="width: 800px;height: 100%;border: none">
EOF
            . implode('', $this->moreRodentEcharsBar());
        $html .= <<<EOF
                </div>
            </th>
        </tr>
    </table>
    
    <table class="text-table-1" style="border: 1px solid;margin-top: 200px">
       
        <tr>
            <th colspan="13">
                鼠类分析：
            </th>
        </tr>
        <tr>
            <td colspan="13">
a）鼠饵站：本月未发现显著聚集情况，盗食情况多为（ ）；本月检查
厂区围堵鼠洞情况，发现（ ),已反馈给厂方。
            </td>
        </tr> <tr>
            <td colspan="13">
b）粘鼠板：常规服务未发现鼠类捕获，情况良好。
            </td>
        </tr>
        
         <tr>
            <th colspan="13">
                建议：
            </th>
        </tr>
        <tr>
            <td colspan="13">
√进入春季后，鼠类将迎来全年第一个繁殖高峰期，其密度和活跃度都
将处于较高水平，鼠类入 侵风险仍然存在；能多洁将对厂区围墙及厂
房进行勘察，对于发现的鼠洞将进行药剂填埋封堵 工作，对于发现的
其它的风险点将配合厂方进行相应处理。
            </td>
        </tr>
        
         <tr>
            <th colspan="13">
                措施：
            </th>
        </tr>
        <tr>
            <td colspan="13">
√针对发生盗食的鼠饵站，史伟莎将持续对周边进行风险排查，并和厂
方一道及时处理以降低风险。
√常规服务时进行厂房密封性排查，以降低鼠类入侵风险。
            </td>
        </tr>
    </table>
    
    
<!--    表格2-->
<table class="style-table">
        <tr class="third-th">
            <td class="third-td title" sty colspan="14">1月份</td>
        </tr>
        
        <tr class="third-th">
            <td class="third-td td-title">日期</td>
            <td class="third-td">序号</td>
            <td class="third-td" colspan="2">灭蝇灯编号</td>
            <td class="third-td">数量</td>
            <td class="third-td" colspan="2">区域</td>
            <td class="third-td td-title">日期</td>
            <td class="third-td">序号</td>
            <td class="third-td" colspan="2">灭蝇灯编号</td>
            <td class="third-td">数量</td>
            <td class="third-td" colspan="2">区域</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        
        <tr class="third-th">
            <td class="third-td" colspan="14">
            本月检查虫害设备时，发现（ ）只蚊子尸体，（ ）老鼠尸体，已全部清理。经过上述统计分析，
虫鼠害发生情况（上升□ 下降□）趋势，建议采取（常规□ 定期清洁ÿ□ 集中灭鼠□）控制措
施。
</td>
        </tr>
    </table>
<!--    表格2-->
    
    
      
<!--    表格2-->
<table class="style-table">
        <tr class="third-th">
            <td class="third-td title" sty colspan="14">2月份</td>
        </tr>
        
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        
        <tr class="third-th">
            <td class="third-td" colspan="14">
            本月检查虫害设备时，发现（ ）只蚊子尸体，（ ）老鼠尸体，已全部清理。经过上述统计分析，
虫鼠害发生情况（上升□ 下降□）趋势，建议采取（常规□ 定期清洁ÿ□ 集中灭鼠□）控制措
施。
</td>
        </tr>
    </table>
<!--    表格2-->

  
<!--    表格2-->
<table class="style-table">
        <tr class="third-th">
            <td class="third-td title" sty colspan="14">3月份</td>
        </tr>
        
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
            <td class="third-td td-title">2023-3-30</td>
            <td class="third-td">0001</td>
            <td class="third-td" colspan="2">EPS-12309-123</td>
            <td class="third-td">2</td>
            <td class="third-td" colspan="2">公司的饮水机上5</td>
        </tr>
        <tr class="third-th">
            <td class="third-td" colspan="14">
            本月检查虫害设备时，发现（ ）只蚊子尸体，（ ）老鼠尸体，已全部清理。经过上述统计分析，
虫鼠害发生情况（上升□ 下降□）趋势，建议采取（常规□ 定期清洁ÿ□ 集中灭鼠□）控制措
施。
</td>
        </tr>
    </table>
<!--    表格2-->
   <!--    表格2-->
    <table class="style-table-content">
        <tr class="footer-th">
            <td class="footer-td" colspan="14">以上报告说明希望得到您的支持和认可，如有疑问请与我们联系。</td>
        </tr>
        <tr class="footer-th">
            <td class="footer-td" colspan="14">
                <img src="https://files.wfnxs.cc/images/company.jpg" style="width: 100%" alt="公司logo">
            </td>
        </tr>
    </table>
    <!--    表格2-->

</div>
</body>
</html>
EOF;
//        echo $html;exit();
        $res = $this->outputHtml($html);
        var_dump($res);
    }

    public function outputHtml($ctx)
    {
        $filename = './demo1.html';
        $fp = fopen($filename, "w");
        $len = fwrite($fp, $ctx);
        fclose($fp);
        if ($len > 0) {
            return true;
        }
        return false;
    }

    public function exec()
    {
        $cmd = "wkhtmltopdf demo1.html demo.pdf 2>&1";
        @exec($cmd, $output, $return_val);
        if ($return_val === 0) {
            print_r($output);
        }

    }


}