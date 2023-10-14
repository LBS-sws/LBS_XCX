<?php

namespace app\technician\controller;

use app\BaseController;
use app\technician\model\CustomerCompany;
use app\technician\model\EquipmentAnalyse;
use app\common\model\JobOrder;
use app\technician\model\ServiceEquipments;
use app\technician\model\ServiceItems;
use app\technician\model\StatisticsReport;
use beyong\echarts\charts\Bar;
use beyong\echarts\charts\Line;
use beyong\echarts\charts\Pie;
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
    protected $statisticsReport = null;
    protected $equipmentAnalyse = null;
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
        $this->statistics_report = new StatisticsReport();
        $this->equipment_analyse = new EquipmentAnalyse();
        //加载所有items内容
        $this->serviceItems = $serviceItemsModel->items;
        $this->result = $this->getBaseInfo();
        parent::__construct($app);
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
        .pest{
            margin: 50px auto;
            /*font-size: 0.9em;*/
            width: 800px;
            background-color: #a1efbf;
        }
         .inline-table {
            /*margin-right: 20px;*/
            width: 50%;
            float: left;
            /*font-size: 0.9em;*/
            border-collapse: collapse;

          }
          
          
          .inline-table thead tr {
            background-color: rgb(220, 230, 242);
            color: #ffffff;
            text-align: left;
            border-collapse: collapse;

        }
        
        .inline-table th,
        .inline-table td {
            padding: 12px 4px;
            height: 30px;

        }
        
        .inline-table tbody tr {
            border: 1px solid #dddddd;
        }
        
        .inline-table tbody tr:nth-of-type(even) {
            background-color: #ffffff;
        }
        
        .inline-table tbody tr:last-of-type {
            border: 1px solid #5f7288;
        }
        
        .inline-table tbody tr.active-row {
            font-weight: bold;
            /*color: #0398dd;*/
        }
          /*******************这里是个分隔符 前端写不明白******************/
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
            padding: 12px 8px;
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
            font-size: 20px;
            border: 1px solid #cad9ea;
            color: #0c0c0c;
            height: 30px;
            padding: 12px 0 12px 20px;
            text-align: left;
        }

        .first-td {
            font-size: 18px;
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
            padding: 10px 10px 5px 10px;!important;
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
            /*width: 32px;*/
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
                <div class="title-right">{$this->result['month']}份</div>
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
            <td class="first-td " colspan="12">{$this->result['cust']['cust_details']['NameZH']}</td>
        </tr>
        <tr>
            <th class="first-th">客户地址</th>
            <td class="first-td" colspan="12">{$this->result['cust']['cust_details']['Addr']}</td>
        </tr>
        <tr>
            <th class="first-th" colspan="1">服务类型</th>
            <td class="first-td" colspan="6">{$this->result['cust']['custInfo']['ServiceName']}</td>
            <th class="first-th" colspan="1">服务项目</th>
            <td class="first-td" colspan="6">{$this->result['service_subject']}</td>
        </tr>
        <tr>
            <th class="first-th">服务人员</th>
            <td class="first-td" colspan="6">{$this->result['cust']['custInfo']['staffs']}</td>
            <th class="first-th">联系电话</th>
            <td class="first-td" colspan="7">{$this->result['cust']['custInfo']['Tel']}</td>
        </tr>
        <tr>
            <th class="first-th">监测设备</th>
            <td class="first-td" colspan="12">{$this->result['equpments']}</td>
        </tr>
        <tr>
            <th class="first-th">服务日期安排</th>
            <td class="first-td" colspan="12">{$this->result['joborder']['jobdate']}</td>
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

EOF;
        $html .= <<<EOF
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
EOF;
        $month_data = $this->result['lion_origin'];
        foreach ($month_data as $k => $v) {
            $data_ret = explode(",", $v[0]['k1']);
            $html .= <<<EOF
        <tr class="secend-th">
            <td class="secend-td">{$k}</td>
            <td class="secend-td">{$data_ret[0]}</td>
            <td class="secend-td">{$data_ret[1]}</td>
            <td class="secend-td">{$data_ret[2]}</td>
            <td class="secend-td">{$data_ret[3]}</td>
            <td class="secend-td">{$data_ret[4]}</td>
            <td class="secend-td">{$data_ret[5]}</td>
            <td class="secend-td">{$data_ret[6]}</td>
            <td class="secend-td">{$data_ret[7]}</td>
            <td class="secend-td">{$data_ret[8]}</td>
            <td class="secend-td">{$data_ret[9]}</td>
            <td class="secend-td">{$data_ret[10]}</td>
            <td class="secend-td">{$data_ret[11]}</td>
        </tr>
EOF;
        }
        $html .= <<<EOF

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
EOF;
        foreach ($this->result['pest_grouped_data'] as $k =>$v){
            $html .= <<<EOF
    <p style="width: 800px;margin: 50px auto;">{$k}</p>
    <div class="pest">
EOF;
            foreach ($v as $k1 => $v1){
            if($k1 == '14'){

                $html .= <<<EOF
        <table class="inline-table" >
            <tr class="third-th">
                <td class="third-td td-title">日期</td>
                <td class="third-td">灭蝇灯编号</td>
                <td class="third-td">数量</td>
                <td class="third-td">区域</td>
            </tr>
EOF;
                if(count($v1)>=1){
                    foreach ($v1 as $k2 => $v2){
                        $html .= <<<EOF
          <tr class="third-th">
                <td class="third-td td-title" >{$v2['job_date']}</td>
                <td class="third-td" >{$v2['equ_type_num']}</td>
                <td class="third-td">{$v2['pest_num']}</td>
                <td class="third-td">{$v2['equ_area']}</td>
            </tr>
EOF;
                    }
                    for ($x = 3;$x>count($v1);$x--){
                        $html .= <<<EOF
          <tr class="third-th">
                <td class="third-td td-title"></td>
                <td class="third-td"></td>
                <td class="third-td"></td>
                <td class="third-td"></td>
            </tr>
EOF;
                    }
                }else{
                    for ($x = 0;$x<3;$x++){
                        $html .= <<<EOF
          <tr class="third-th">
                <td class="third-td td-title"></td>
                <td class="third-td"></td>
                <td class="third-td"></td>
                <td class="third-td"></td>
            </tr>
EOF;
                    }
                }
                $html .= <<<EOF
                </table>   
EOF;
            }elseif($k1 == '15'){
                $html .= <<<EOF
        <table class="inline-table" >
            <tr class="third-th">
                <td class="third-td td-title">日期</td>
                <td class="third-td">鼠饵站编号</td>
                <td class="third-td">数量</td>
                <td class="third-td">区域</td>
            </tr>
EOF;
                if(count($v1)>=1){
                    foreach ($v1 as $k2 => $v2){
                        $html .= <<<EOF
          <tr class="third-th">
                 <td class="third-td td-title">{$v2['job_date']}</td>
                <td class="third-td">{$v2['equ_type_num']}</td>
                <td class="third-td">{$v2['pest_num']}</td>
                <td class="third-td">{$v2['equ_area']}</td>
            </tr>
EOF;
                    }
                    for ($x = 3;$x>count($v1);$x--){
                        $html .= <<<EOF
          <tr class="third-th">
                <td class="third-td td-title"></td>
                <td class="third-td"></td>
                <td class="third-td"></td>
                <td class="third-td"></td>
            </tr>
EOF;
                    }
                }else{
                    for ($x = 0;$x<3;$x++){

                        $html .= <<<EOF
          <tr class="third-th">
                <td class="third-td td-title"></td>
                <td class="third-td"></td>
                <td class="third-td"></td>
                <td class="third-td"></td>
            </tr>
EOF;
                    }
                }
                $html .= <<<EOF
                </table>   
EOF;
            }
}
        $html .= <<<EOF
</div>
    <hr style="FILTER:alpha(opacity=100,finishopacity=0,style=2)" width="800px" color=#cad9ea SIZE=0>

EOF;
            }
        $html .= <<<EOF
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


    public function checkCustInfo(int $job_id)
    {
        if (!empty($job_id)) {
            $where = ['JobID' => $job_id];
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

        /** 虫害情况  只查询捕捉到的数据 113是【驱虫喷机】 */
        //->where('equipment_type_id', '<>', '113') 暂时不管
        $catch_equment = $this->serviceEquipments->alias('e')->field('j.JobDate,job_id,check_datas,equipment_type_id,equipment_number,equipment_name,equipment_area')->join('joborder j', 'j.JobID=e.job_id')->where('equipment_type_id', '<>', '113')->where('job_id', 'in', $job_orders['joborders'])->select()->toArray();
        $this->catch_equment = $catch_equment;


        $original_array = [];
        foreach ($catch_equment as $k => $v) {
            $original_array[] = $v['check_datas'];
//            $original_array[] = $v['job_id'];
        }
        $original_array = array_filter($original_array);
        $total = [];
        foreach ($original_array as $k => $array) {
            $json = json_decode($array, true);
            foreach ($json as $item) {
                $total[][$item['label']] = $item['value'];
            }
        }

        $sums = [];
        foreach ($total as $subarray) {
            foreach ($subarray as $key => $value) {
                if (isset($sums[$key])) {
                    $sums["$key"] += $value;
                } else {
                    $sums["$key"] = $value;
                }
            }
        }
//        dd($sums);

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
        $has_value = $this->statistics_report->where($statistics_where)->count();
//        dd($has_value);exit();

        $force_update = 0;
        if ($has_value <= 0 || $force_update == 1) {
            $insert_data = [];
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
        foreach ($has_data as $id => $value) {
            $type_name[] = $value['type_name'];
        }

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

        Db::execute("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $equment_type = $this->serviceEquipments->field('equipment_type_id,equipment_name as name,count(1) as value')->where('equipment_type_id', '<>', '113')->where('job_id', 'in', $job_orders['joborders'])->group('equipment_type_id')->select()->toArray();

//        dd($equment_type);
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
        $mian_info['pie'] = $equment_type;
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


    public function echars(): string
    {
        $echarts = ECharts::init("#myChart");
        $option = new Option();
        $option->animation(false);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae', '#749f83']);
        $option->xAxis(["data" => $this->result['line']['keys']]);
        $option->yAxis([]);
        $option->title([
            "text" => '8月虫害统计图',
            "left" => 'center'
        ]);
        $chart = new Bar();
        $chart->data = $this->result['line']['values'];

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
            "data" => $this->result['lion_title'],
            "backgroundColor" => 'white',
            "top" => '8%',
        ]);

        $option->series($this->result['lion_content']);
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
                'data' => $this->result['pie'],
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
        $color = ['#4587E7'];
        $data_bar = $this->result['data_insect_bar'];
        $id = 1;
        $result = [];
        foreach ($data_bar as $k => $v) {
            $result[] = $this->createEcharsBar($k . '_' . $id, strval($k), $color, $v);
            $id++;
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
    public function createEcharsBar(string $id = '0', string $title = '柱状图', array $color = ['#4587E7'], array $data = []): string
    {
        $echarts = ECharts::init("#myChart" . $id);
        $option = new Option();
        $option->animation(false);
        $option->color($color);
        $option->xAxis(["data" => ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月']]);
        $option->yAxis([]);
        $option->title([
            "text" => $title,
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

    /**
     * 鼠类图表绘制
     * @return array
     **/
    public function moreRodentEcharsBar(): array
    {
        $color = ['#e81010'];
        $data_bar = $this->result['data_rodent_bar'];
        $id = 1;
        $result = [];
        foreach ($data_bar as $k => $v) {
            $result[] = $this->createEcharsBar($k . '_' . $id, strval($k), $color, $v);
            $id++;
        }
        return $result;
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
        header('Content-Type:text/html;charset=utf-8');

        $cmd = "wkhtmltopdf demo1.html demo.pdf 2>&1";
        @exec($cmd, $output, $return_val);
        if ($return_val === 0) {
            print_r($output);
        }
    }


}