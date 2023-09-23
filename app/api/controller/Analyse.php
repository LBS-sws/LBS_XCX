<?php

namespace app\api\controller;

use app\BaseController;
use app\technician\model\AnalyseReport;
use app\technician\model\CustomerCompany;
use app\technician\model\EquipmentAnalyse;
use app\technician\model\JobOrder;
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
    protected $custType = [203, 249];

    protected $jobOrderModel = null;
    protected $customerCompanyModel = null;
    protected $serviceEquipments = null;
    protected $statisticsReport = null;
    protected $equipmentAnalyse = null;
    protected $serviceItems = [];
    protected $result = [];
    protected $type_data_zh = [];
    protected $catch_equment = [];
    protected $isPest = 0;
    protected $isInsect = 0; //飞虫

    /**
     * 检查是不是工厂客户
     * @param int $job_id
     * @return array|false|mixed|Db|Model
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */

    public function __construct()
    {
        $this->type_data_zh = $this->getNameByZh();
        $this->jobOrderModel = new JobOrder();
        $this->customerCompanyModel = new CustomerCompany();
        $serviceItemsModel = new ServiceItems();
        $this->serviceEquipments = new ServiceEquipments();
        $this->statisticsReport = new StatisticsReport();
        $this->equipmentAnalyse = new EquipmentAnalyse();
        //加载所有items内容
        $this->serviceItems = $serviceItemsModel->items;
    }

    public function getNameByZh()
    {
        $equipment_type = Db::query("SELECT * FROM `lbs_service_equipment_type` WHERE `city` = 'CN' AND number_code <> 'MY'");
        $type_data = [];
        foreach ($equipment_type as $type_k => $type_v) {
            $type_data[$type_v['number_code']] = $type_v['name'];
        }
        return $type_data;
    }

    public function index(string $month = '2023-05', string $cust = 'HYLSPJGC-ZY', $city = 'ZY', $url_id = '')
    {
        $this->result = $this->getBaseInfo($month, $cust);
        $sign_pic = "https://xcx.lbsapps.cn/pdf/company/" . $city . ".jpg";
        $html = <<<EOF
<!DOCTYPE html>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html;charset=utf-8">

    <title>史伟莎有害生物控制总结及趋势分析报告</title>
    <style>
        
      * {
            page-break-inside: avoid;
            page-break-after: avoid;
            page-break-before: avoid;
        }
        body {
            max-width: 800px;
            margin: 0 auto;
        }
          @media screen{
                div.break_here {
                    page-break-after: always !important;
                }
          }

        .pest{
            margin: 50px auto;
            /*font-size: 0.9em;*/
            width: 800px;
        }
        .inline-table-none {
            /*margin-right: 20px;*/
            width: 800px;
            float: left;
            /*font-size: 0.9em;*/
            border-collapse: collapse;

          }
         .inline-table {
            /*margin-right: 20px;*/
            width: 100%;
            float: left;
            /*font-size: 0.9em;*/
            border-collapse: collapse;
             border: none
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
            /*font-size: 0.9em;*/
            width: 800px;
        }
        .echart-table{
            margin: 50px auto;
            /*font-size: 0.9em;*/
            width: 800px;
        }
        
        .echart-table-1{
            margin: 0 auto 0 auto;
            /*font-size: 0.9em;*/
            width: 800px;
        }
        
        .echart-table-2{
            margin: 0 auto 0 auto;
            /*font-size: 0.9em;*/
            width: 800px;
            /*box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);*/
        }
        .text-table-1{
            margin: 0 auto 0 auto;
            /*font-size: 0.9em;*/
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
            padding-top:30px;
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
            width: 130px;
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
            /*font-size: 0.9em;*/
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
    
EOF;
        if ($this->isPest) {
            $html .= <<<EOF
    
    <table class="echart-table-1">
        <thead>
        <tr>
            <th class="first-th mian-title" colspan="13">虫害控制情况</th>
        </tr>
        <tr>
            <th>
                <div style="width: 800px;height: 50px;border: none">
            </th>
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
    
    </div>
    
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
                if ($k == '老鼠') {
                    $k = "老鼠(捕获)";
                }
                if ($k == '盗食占比') {
                    $k = "老鼠(盗食)";
                }
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
EOF;
        }
        if ($this->isInsect == 1) {
            $html .= <<<EOF
        <div style="padding-top: 50px;"></div>
    <table class="echart-table-1">
       <thead>
        <tr>
            <th class="first-th mian-title" colspan="13">设备统计及分析</th>
        </tr>
        <tr>
            <th>
                <div style="width: 800px;height: 50px;border: none">
            </th>
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
    
    <table class="text-table-1" style="border: 1px solid">
        <tr>
            <th colspan="13">
                飞虫分析：
            </th>
        </tr>
        <tr>
            <td colspan="13">
a）本月捕获飞虫总数为（ {$this->result['pest'][0]['pest_month_total']}）只，较上月呈（{$this->result['pest'][0]['trend']}）趋势。
            </td>
        </tr> <tr>
            <td colspan="13">
b）本月以（{$this->result['pest'][0]['pest_max_data']['type_name']}）捕获量稍高，与{$this->result['pest'][0]['sub'][0]}有关。
            </td>
        </tr>
        
         <tr>
            <th colspan="13">
                建议：
            </th>
        </tr>
        <tr>
            <td colspan="13">{$this->result['pest'][0]['sub'][1]}
            </td>
        </tr>
        
         <tr>
            <th colspan="13">
                措施：
            </th>
        </tr>
        <tr>
            <td colspan="13">
            √{$this->result['pest'][0]['sub'][2]}
            </td>
        </tr>
    </table>
EOF;
        }
        $html .= <<<EOF
     <table class="echart-table-2">
        <tr>
            <th colspan="13">
                <div style="width: 800px;height: 100%;border: none">
EOF
            . $this->seSite();
        $html .= <<<EOF
                </div>
            </th>
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
EOF;
        if ((isset($this->result['se_max']) && !empty($this->result['se_max'])) || (isset($this->result['rat_max']) && !empty($this->result['rat_max']))) {
            $html .= <<<EOF

    <table class="text-table-1" style="border: 1px solid;">
        <tr>
            <th colspan="13">
                鼠类分析：
            </th>
        </tr>
EOF;
            if (!empty($this->result['se_max'])) {
                $html .= <<<EOF
        <tr>
            <td colspan="13">
a）鼠饵站：本月盗食情况较上月（{$this->result['pest'][2]['trend']}），盗食情况多发生在设备({$this->result['se_max']})。
            </td>
        </tr> 
EOF;
            }
            if (!empty($this->result['rat_max'])) {
                $html .= <<<EOF
        <tr>
            <td colspan="13">
 b）粘鼠板：本月捕获鼠类（{$this->result['pest'][1]['pest_month_total']}）只，较上月（{$this->result['pest'][1]['trend']}）。
            </td>
        </tr>
EOF;
            }
            $html .= <<<EOF
         <tr>
            <th colspan="13">
                建议：
            </th>
        </tr>
        <tr>
            <td colspan="13">
                √{$this->result['pest'][2]['sub'][1]}
            </td>
        </tr>
        
         <tr>
            <th colspan="13">
                措施：
            </th>
        </tr>
        <tr>
            <td colspan="13">
                √{$this->result['pest'][2]['sub'][2]}
            </td>
        </tr>
        
        <tr>
            <td>

            </td>
        </tr>
    </table>
EOF;
        }


//        -------------
        $html .= '<table class="inline-table">';
        $html .= '<tbody>';

        foreach ($this->result['pest_grouped_data'] as $k => $v) {
            $leftData = array();
            $rightData = array();
            $isLeft = true; // 初始化$isLeft变量

            foreach ($v as $k1 => $v1) {
                foreach ($v1 as $k2 => $v2) {
                    if ($isLeft) {
                        $leftData[] = $v2;
                    } else {
                        $rightData[] = $v2;
                    }

                    // 切换左右数据
                    $isLeft = !$isLeft;
                }
            }

            $html .= '<tr class="third-th">';
            $html .= '<td class="third-td td-title" colspan="8">' . $k . '(前三指标性数据)</td>';
            $html .= '</tr>';

            $html .= '<tr class="third-th">';
            $html .= '<td class="third-td td-title">日期</td>';
            $html .= '<td class="third-td">设备编号</td>';
            $html .= '<td class="third-td">数量</td>';
            $html .= '<td class="third-td">区域</td>';
            $html .= '<td class="third-td td-title">日期</td>';
            $html .= '<td class="third-td">设备编号</td>';
            $html .= '<td class="third-td">数量</td>';
            $html .= '<td class="third-td">区域</td>';
            $html .= '</tr>';

            $rowCount = max(count($leftData), count($rightData));

            for ($i = 0; $i < $rowCount; $i++) {
                $html .= '<tr class="third-th">';
                if ($i < count($leftData)) {
                    $html .= '<td class="third-td td-title">' . $leftData[$i]['job_date'] . '</td>';
                    $html .= '<td class="third-td">' . $leftData[$i]['equ_type_num'] . '0' . $leftData[$i]['number'] . '</td>';
                    $html .= '<td class="third-td">' . $leftData[$i]['pest_num'] . '</td>';
                    $html .= '<td class="third-td">' . $leftData[$i]['equ_area'] . '</td>';
                } else {
                    $html .= '<td class="third-td td-title"></td>';
                    $html .= '<td class="third-td"></td>';
                    $html .= '<td class="third-td"></td>';
                    $html .= '<td class="third-td"></td>';
                }

                if ($i < count($rightData)) {
                    $html .= '<td class="third-td td-title">' . $rightData[$i]['job_date'] . '</td>';
                    $html .= '<td class="third-td">' . $rightData[$i]['equ_type_num'] . '0' . $rightData[$i]['number'] . '</td>';
                    $html .= '<td class="third-td">' . $rightData[$i]['pest_num'] . '</td>';
                    $html .= '<td class="third-td">' . $rightData[$i]['equ_area'] . '</td>';
                } else {
                    $html .= '<td class="third-td td-title"></td>';
                    $html .= '<td class="third-td"></td>';
                    $html .= '<td class="third-td"></td>';
                    $html .= '<td class="third-td"></td>';
                }

                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';

//        -------------

        $html .= <<<EOF
<!--    表格2-->
   <!--    表格2-->
    <table class="style-table-content">
        <tr class="footer-th">
            <td class="footer-td" colspan="14">以上报告说明希望得到您的支持和认可，如有疑问请与我们联系。</td>
        </tr>
        <tr class="footer-th">
            <td class="footer-td" colspan="14">
                <img src={$sign_pic} style="width: 100%" alt="公司logo">
            </td>
        </tr>
    </table>
    <!--    表格2-->
</div>
</body>
</html>
EOF;
//        echo $html;exit();
        $res = $this->outputHtml($month, $html, $url_id);
        if ($month == '' || $cust == '' || $city = '') {
            return error(-1, '输入参数有误', []);
        }
        $file_path = 'analyse/' . $month . '/' . $url_id . '.pdf';
//        if (is_file($file_path)) {
        $domain = 'http://xcx.com/';
        $url = $domain . $file_path;
        //有报告就返回，没返回就
        return success(0, 'success', $url);
    }


    public function checkCustInfo(string $customer_id)
    {
        $where = ['CustomerID' => $customer_id, 'j.ServiceType' => 2];
        $cust = $this->jobOrderModel->alias('j')
            ->join('service s', 'j.ServiceType=s.ServiceType')->join('staff u', 'j.Staff01=u.StaffID')
            ->join('staff uo', 'j.Staff02=uo.StaffID', 'left')->join('staff ut', 'j.Staff03=ut.StaffID', 'left')
            ->join('officecity oc', 'oc.City=u.City', 'left')
            ->join('officesettings os', 'os.Office=oc.Office', 'left')
            ->where($where)
            ->field('j.CustomerID,j.Mobile,j.JobDate,j.StartTime,j.FinishTime,u.StaffName as Staff01,uo.StaffName as Staff02,ut.StaffName as Staff03,s.ServiceName,j.Status,j.City,j.ServiceType,j.FirstJob,j.FinishDate,os.Tel')
            ->order('JobID DESC')->findOrEmpty()->toArray();
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
            ];
            //查询是工厂客户才会继续走接下来的流程
            $cust_c = $this->customerCompanyModel->field('NameZH,CustomerID,Addr')->whereIn('CustomerType', $this->custType)->where($where_c)->find()->toArray();
            if ($cust_c) {
                $data['cust_details'] = $cust_c;
                return $data;
            }
        }

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

    public function getBaseInfo(string $month = '2023-03', string $customer_id = 'JJXTC01-ZY')
    {
        $mian_info = [];
        $cust = $this->checkCustInfo($customer_id);
        $where = [
            'j.CustomerID' => $cust['cust_details']['CustomerID'],
            'j.Status' => 3,
            'j.ServiceType' => 2,
//            'DATE_FORMAT(jobDate,"%Y-%m")' => $cust['cust_details']['CustomerID'],
        ];

        $where_sub = [
            'CustomerID' => $cust['cust_details']['CustomerID'],
            'Status' => 3,
            'ServiceType' => 2,
//            'DATE_FORMAT(jobDate,"%Y-%m")' => $cust['cust_details']['CustomerID'],
        ];
        //查看有哪些订单和日期
        $job_orders = $this->jobOrderModel->alias('j')->join('customercompany c', "c.CustomerID=j.CustomerID")->field('MAX(jobDate) as jobDate,MAX(JobID) as JobID,GROUP_CONCAT( distinct JobDate) as jobdate')->whereIn('c.CustomerType', $this->custType)->where($where)->where('DATE_FORMAT(jobDate,"%Y-%m")="' . $month . '"')->order('jobDate', 'DESC')->find();
        //查询有哪些 服务项目
        $job_items = $this->jobOrderModel->field('Item01, Item01Rmk, Item02, Item02Rmk, Item03, Item03Rmk, Item04, Item04Rmk, Item05, Item05Rmk, Item06, Item06Rmk, Item07, Item07Rmk, Item08, Item08Rmk, Item09, Item09Rmk, Item10, Item10Rmk, Item11, Item11Rmk, Item12, Item12Rmk, Item13, Item13Rmk, Remarks')->where($where_sub)->where('DATE_FORMAT(jobDate,"%Y-%m")="' . $month . '"')->findOrEmpty()->toArray();
        $service_subject = '';
        if (!empty($job_items)) {
            foreach ($this->serviceItems as $key => $val) {
                if ($key == $cust['custInfo']['ServiceType']) {
                    $result = $val;
                    break;
                }
            }
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
        }
        //获取所有的设备情况 【】
        $equpments = '';
        $equpment_nums = $this->serviceEquipments->alias('e')->join('lbs_service_equipment_type t', 'e.equipment_type_id=t.id', 'left')->field('t.name,e.equipment_type_id,COUNT(1) as num')->where('e.job_id', '=', $job_orders['JobID'])->where('e.job_type', 1)->group('equipment_type_id')->select()->toArray();

        foreach ($equpment_nums as $k => $v) {
            $equpments .= $v['name'] . '-' . $v['num'] . '、';
        }
        $equpments = rtrim($equpments, '、');
        // dd($equpments);
        //查询捕捉到的设备数据
        //$catch_equment = $this->serviceEquipments->alias('e')->field('j.JobDate,job_id,check_datas,equipment_type_id,equipment_number,equipment_name,equipment_area')->join('joborder j', 'j.JobID=e.job_id')->where('equipment_type_id', '<>', '113')->where('job_id', '=', $job_orders['JobID'])->select()->toArray();

        /*$job_datas['Watchdog'] = '';
        //查询当前设备
        $where_dq['e.job_id'] = $job_orders['JobID'];
        $where_dq['e.job_type'] = 1;
        $dq_eqs = Db::table('lbs_service_equipments')->alias('e')->join('lbs_service_equipment_type t','e.equipment_type_id=t.id','right')->field('t.name,e.equipment_type_id')->where($where_dq)->Distinct(true)->cache(true,60)->select();
        if (count($dq_eqs)>0) {
            for ($i = 0; $i < count($dq_eqs); $i++) {
                $n['job_id'] = $job_orders['JobID'];
                $n['job_type'] = 1;
                $n['equipment_type_id'] = $dq_eqs[$i]['equipment_type_id'];
                $numbers = Db::table('lbs_service_equipments')->where($n)->cache(true, 60)->count();
                if ($job_datas['Watchdog'] == '') {
                    $job_datas['Watchdog'] = $dq_eqs[$i]['name'] . '-' . $numbers;
                } else {
                    $job_datas['Watchdog'] = $job_datas['Watchdog'] . ',' . $dq_eqs[$i]['name'] . '-' . $numbers;
                }
            }
        }


        $equpments = $job_datas['Watchdog'];*/

        // $equpments = $catch_equment;

        $statistics_str = explode('-', $month);
        $year = intval($statistics_str[0]);
        $singal_month = intval($statistics_str[1]);
        $where_statistic = [
            'year' => $year,
            'month' => $singal_month,
            'customer_id' => $customer_id
        ];
        $res = $this->statisticsReport->field('sum(type_value) as type_value,type_name')->where('type_code', 'not in', 'SE')->where($where_statistic)->group('year,month,type_name')->orderRaw('field(type_name,"蟑螂","苍蝇","蚊子","卫生性飞虫","绿化飞虫","仓储害虫","老鼠","其他") ASC')->select()->toArray();
        $type_values = array_column($res, 'type_value');
        $type_names = array_column($res, 'type_name');
        // var_dump($type_values);
        // var_dump($type_names);
        //条形统计图的内容填充
        $line['keys'] = $type_names;
        $line['values'] = $type_values;
//        dd($line);

        foreach ($line['keys'] as $key => $value) {

            if ($value === '老鼠') {
                $line['keys'][$key] = '老鼠(捕获)';
            }
            if ($value === '盗食占比') {
                $line['keys'][$key] = '老鼠(盗食)';
            }
        }
        if (!empty($line['keys']) && !empty($line['values'])) {
            // $line['key'] 和 $line['value'] 都不为空
            $this->isPest = 1;
        } else {
            // $line['key'] 或 $line['value'] 其中之一为空
            $this->isPest = 0;
        }

        $mian_info['line'] = $line;
        //处理线条统计图

        $statistics_where = [
            'year' => $year,
            'month' => $singal_month,
            'customer_id' => $cust['cust_details']['CustomerID'],
            'update_flag' => 1,
            'delete_flag' => 0
        ];
        //查询到本月此客户有数据了 就不去更新表了 除非去强制更新

        //接下来的数据就直接查询该表中的数据就行
        $has_data = $this->statisticsReport->where($statistics_where)
            ->where('type_code', 'not in', 'SE')
            ->field('distinct type_name, type_code')
            ->orderRaw("FIELD(type_code, 'MY', 'XW', 'BY', 'DJ') ASC")
            ->orderRaw("FIELD(type_name, '苍蝇', '蚊子', '绿化飞虫', '仓储害虫', '卫生性飞虫') ASC")
            ->select()
            ->toArray();
//        $has_data = [];
//        foreach ($has_data1 as $k =>$v){
//            $has_data[][$v['type_code']] = $v['type_name'];
//        }
//        dd($has_data);
        //类型名称arr
        // 先查询灭蝇灯的数据（飞虫）
        $type_name1 = [];
        foreach ($has_data as $id => $value) {
            if ($value['type_name'] === '老鼠') {
                $value['type_name'] = '老鼠(捕获)';
            }
            if ($value['type_name'] === '其他') {
                $value['type_code'] = 'OTH';
            }
            $type_name1[] = $value['type_name'];
        }
        $uniqueArray = array_unique($type_name1);
        $firstOccurrences = array_keys($uniqueArray);

        $resultArray = [];
        foreach ($firstOccurrences as $index) {
            $resultArray[$index] = $type_name1[$index];
        }
        $type_name = array_values($resultArray);
        $data_line1 = [];
        $data_line2 = [];
        foreach ($has_data as $k1 => $v1) {
            $data_line1[][$v1['type_code']][$v1['type_name']] = Db::query("SELECT GROUP_CONCAT(total_data_list) as k1 from(
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
LEFT JOIN lbs_statistics_report lsr ON months.month = lsr.month AND lsr.year = ? AND lsr.type_name = ? AND lsr.type_code = ? AND lsr.customer_id = ? AND lsr.delete_flag = 0  AND lsr.year <= '{$year}' AND lsr.month <= '{$singal_month}'
GROUP BY months.month) as k", [$year, $v1['type_name'], $v1['type_code'], $customer_id]);

            $data_line2[][$v1['type_code']][$v1['type_name']] = Db::query("SELECT GROUP_CONCAT(total_data_list) as k1 from(
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
LEFT JOIN lbs_statistics_report lsr ON months.month = lsr.month AND lsr.year = ? AND lsr.type_name = ? AND lsr.customer_id = ? AND lsr.delete_flag = 0 AND lsr.year <= '{$year}' AND lsr.month <= '{$singal_month}'
GROUP BY months.month) as k", [$year, $v1['type_name'], $customer_id]);
        }

        $arr = [];
        $data_line = [];
        foreach ($data_line1 as $k => $v) {
            foreach ($v as $k1 => $v1) {
                foreach ($v1 as $k2 => $v2) {
                    if ($k2 == "老鼠") {
                        $k2 = "老鼠(捕获)";
                    }
                    $data_ret = explode(",", $v2[0]['k1']);
                    // array_walk($data_ret, function(&$value) {
                    //     if ($value == "0") {
                    //         $value = null;
                    //     }
                    // });

                    $arr[] = [
                        //线条的title
                        'name' => $k1 . '-' . $k2,
                        'type' => 'line',
//                        'stack' => 'Total',
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
                    $data_line[$k2] = $v2;
                }
            }
        }
        $arr_sb = [];
        $data_line_sb = [];
//        dd($data_line2);
        foreach ($data_line2 as $k => $v) {
            foreach ($v as $k1 => $v1) {
                foreach ($v1 as $k2 => $v2) {
                    if ($k2 == "老鼠") {
                        $k2 = "老鼠(捕获)";
                    }
                    $data_ret = explode(",", $v2[0]['k1']);
                    // array_walk($data_ret, function(&$value) {
                    //     if ($value == "0") {
                    //         $value = null;
                    //     }
                    // });
                    $arr_sb[] = [
                        //线条的title
                        'name' => $k2,
                        'type' => 'line',
//                        'stack' => 'Total',
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
                    $data_line_sb[$k2] = $v2;
                }
            }
        }
//        dd($data_line_sb);
//        $arr_sb = array_map("unserialize", array_unique(array_map("serialize", $arr_sb)));
//        dd($arr_sb);
        // 删除指定键为空的数组项

// 使用 array_filter() 函数过滤掉空键名元素

        //查询飞虫的数据
        $data_insect_bar = [];
        $data_rodent_bar = [];
        foreach ($data_line1 as $k => $v) {
            foreach ($v as $k1 => $v1) {
                if (!empty($k1)) {
                    foreach ($v1 as $k2 => $v2) {
                        if ($k1 == 'MY') {
                            $data_insect_bar['灭蝇灯' . '-' . $k2] = explode(',', $v2[0]['k1']);
                        } elseif ($k1 == 'XW') {
                            $data_insect_bar['吸蚊灯' . '-' . $k2] = explode(',', $v2[0]['k1']);
                        } elseif ($k1 == 'BY') {
                            $data_insect_bar['捕蝇笼' . '-' . $k2] = explode(',', $v2[0]['k1']);
                        } elseif ($k1 == 'DJ') {
                            $data_insect_bar['室外电击式灭蝇灯' . '-' . $k2] = explode(',', $v2[0]['k1']);
                        } else {
                            $data_rodent_bar[$this->type_data_zh[$k1] . '-' . $k2] = explode(',', $v2[0]['k1']);
                            // dd($data_rodent_bar);
                        }
                    }
                }
            }
        }
//        //查询飞虫的数据
//        $data_rodent_bar = [];
//        foreach ($data_line2 as $k => $v) {
//            foreach ($v as $k1 =>$v1){
//                foreach ($v1 as $k2 =>$v2){
//                    if ($k1 == 'MY') {
//                    }elseif($k1 == 'SE'){
//                        //不处理
//                    } else {
//                        $data_rodent_bar[$k2.'-'.$k1] = explode(',', $v2[0]['k1']);
//                    }
//                }
//            }
//        }
        //、当有以下任一设备灭蝇灯、吸蚊灯、室外点击式灭蝇灯、捕蝇笼时，则需要显示该板块
        $equment_type = $this->statisticsReport->field('type_name as name,type_code,type_value as value')
            ->where('type_code', 'in', 'MY,XW,BY,DJ')
            ->where('customer_id', '=', $cust['cust_details']['CustomerID'])
            ->where($where_statistic)
            ->orderRaw("FIELD(type_code, 'MY', 'XW', 'BY', 'DJ') ASC")
            ->select()
            ->toArray();
        // 创建一个空数组用于存储结果
        $result = [];

        // 遍历$equment_type数组
        foreach ($equment_type as $item) {
            $name = $item['name'];
            $value = $item['value'];

            // 如果结果数组中已经存在相同的name，则将value相加
            if (isset($result[$name])) {
                $result[$name] += $value;
            } else {
                // 否则，将name和value添加到结果数组中
                $result[$name] = $value;
            }
        }

        // 将结果数组重新赋值给$equment_type
        $equment_type = array_map(function ($name, $value) {
            return [
                'name' => $name,
                'value' => $value
            ];
        }, array_keys($result), $result);

        // 输出结果

        if (!empty($equment_type)) {
            // $line['key'] 和 $line['value'] 都不为空
            $this->isInsect = 1;
        } else {
            // $line['key'] 或 $line['value'] 其中之一为空
            $this->isInsect = 0;
        }
        //查询飞虫的数据
        // $data_insect_bar = [];
        // $data_rodent_bar = [];


        //查询某个设备捕捉到的虫害数量最多的统计
//        $equment_type1 = $this->serviceEquipments->field('equipment_area,equipment_type_id,equipment_name as name,count(1) as value')->where('equipment_type_id', '<>', '113')->where('job_id', 'in', $job_orders['joborders'])->group('equipment_type_id')->select()->toArray();


        // 查询每个月设备捕捉数量最多的设备（只展示每个种类的前3条数据）

        $pest_res = Db::query("SELECT
    t1.equ_type_num,
    t1.equ_type_name,
    t1.job_month,
    t1.pest_num,
    t1.equ_area,
    t1.number,
    t1.job_date,
    t1.customer_id
FROM
    (
        SELECT
            equ_type_num,
            equ_type_name,
            DATE_FORMAT(job_date, '%Y-%m') AS job_month,
            pest_num,
            equ_area,
            number,
            job_date,
            customer_id,
            @rn := IF(@prev_month = DATE_FORMAT(job_date, '%Y-%m') AND @prev_type = equ_type_num, @rn + 1, 1) AS rn,
            @prev_month := DATE_FORMAT(job_date, '%Y-%m') AS prev_month,
            @prev_type := equ_type_num AS prev_type
        FROM
            lbs_service_equipment_analyse,
            (SELECT @prev_month := NULL, @prev_type := NULL, @rn := 0) AS vars
        WHERE
            customer_id = ?
            AND DATE_FORMAT(job_date, '%Y-%m') <= '{$month}'
        ORDER BY
            equ_type_num,
            DATE_FORMAT(job_date, '%Y-%m'),
            pest_num DESC
    ) AS t1
WHERE
    t1.rn <= 3
ORDER BY
    t1.equ_type_num DESC,
		    t1.job_month DESC,
    t1.pest_num DESC
", [$cust['cust_details']['CustomerID']]);

        $pest_grouped_data = array_reduce($pest_res, function ($result, $item) {
            $result[$item['job_month']][$item['equ_type_num']][] = $item;
            return $result;
        }, []);
        // 单独统计当月鼠饵站鼠的设备情况
        $pest_se = Db::query("SELECT equ_type_num,number, SUM(pest_num) AS count
FROM lbs_service_equipment_analyse
WHERE equ_type_num = 'SE' AND DATE_FORMAT(job_date, '%Y-%m') = '{$month}' AND customer_id = ?
GROUP BY number;", [$cust['cust_details']['CustomerID']]);

        $se_arr = [];
        $se_ret = [];
        if (!empty($pest_se)) {
            foreach ($pest_se as $k => $v) {
                $se_arr[] = $v['equ_type_num'] . '0' . $v['number'];
                $se_ret[] = $v['count'];
            }
        }

        // 单独统计当老鼠最多的设备数量
        $pest_rat_data = Db::query("SELECT equ_type_num,number, SUM(pest_num) AS count FROM lbs_service_equipment_analyse WHERE equ_type_num IN('LB','SL') AND DATE_FORMAT(job_date, '%Y-%m') = '{$month}' AND customer_id = ? GROUP BY number ORDER BY count DESC LIMIT 1;", [$cust['cust_details']['CustomerID']]);

        // 单独统计当月鼠饵站鼠捕捉量最多的设备
        $pest_se_data = Db::query("SELECT equ_type_num,number, SUM(pest_num) AS count FROM lbs_service_equipment_analyse WHERE equ_type_num = 'SE' AND DATE_FORMAT(job_date, '%Y-%m') = '{$month}' AND customer_id = ? GROUP BY number ORDER BY count DESC LIMIT 1;", [$cust['cust_details']['CustomerID']]);
//        $pest_se_max = $pest_se_data[0]['equ_type_num'].'0'.$pest_se_data[0]['number'];
        $pest_se_max = '';
        if (isset($pest_se_data[0]['equ_type_num']) && isset($pest_se_data[0]['number'])) {
            $pest_se_max = $pest_se_data[0]['equ_type_num'] . '0' . $pest_se_data[0]['number'];
        }
        $pest_ret = [];
        // 1、飞虫 2、老鼠、3、鼠饵站
        $pest_ret[] = $this->getPestData($cust, $year, $singal_month, $month, $type = 1);
        $pest_ret[] = $this->getPestData($cust, $year, $singal_month, $month, $type = 2);
        $pest_ret[] = $this->getPestData($cust, $year, $singal_month, $month, $type = 3);
        $mian_info['pest'] = $pest_ret ?? '无数据';
        $mian_info['pest_grouped_data'] = $pest_grouped_data;
        // dd($pest_grouped_data);
        $mian_info['data_insect_bar'] = $data_insect_bar;
        $mian_info['data_rodent_bar'] = $data_rodent_bar;
        $mian_info['se_max'] = $pest_se_max;
        $mian_info['rat_max'] = $pest_rat_data;
        $mian_info['pie'] = $equment_type;
        $mian_info['lion_title'] = $type_name;
        $mian_info['lion_origin'] = $data_line_sb;
        $mian_info['lion_content'] = $arr_sb;        //先去查询构造表里边有没有数据
        $mian_info['cust'] = $cust;
        $mian_info['joborder'] = $job_orders;
        $mian_info['service_subject'] = $service_subject;
        $mian_info['equpments'] = $equpments;
        $mian_info['se_site_data'] = $se_ret;
        $mian_info['se_site_title'] = $se_arr;
        $mian_info['month'] = date('Y年m月', strtotime($month));
        $mian_info['month_de'] = date('m月', strtotime($month));
        return $this->result = $mian_info;
    }

    public function seSite()
    {
        $se_site_data = $this->result['se_site_data'];
        if ($se_site_data) {
            $se_site_title = $this->result['se_site_title'];
            $se_reult = $this->createEcharsBar('鼠饵站_' . 1, '鼠饵站-老鼠', ["#e81010"], $se_site_data, $se_site_title, $x = "设备编号", $y = "盗食次数");
            return $se_reult;
        }
    }

    /**
     * type = 1为老鼠，type = 2为苍蝇
     * */
    public function getPestData($cust, $year, $singal_month, $month, $type = 1)
    {
        //查询
        $city_id = 0;
        $customer = Db::query("select City from customercompany WHERE CustomerID = ?;", [$cust['cust_details']['CustomerID']]);
        $city_id = $customer[0]['City'];

        /*$city_en = Db::query("select e.Text from enums as e left join officecity as o on o.Office=e.EnumID where o.City= ? and e.EnumType=8
;",[$city_id]);*/
        $authkey = 'TFJTR1JPVVBfd2FpdDk3Mw==';
        // 使用示例
        $sec_data = ['data' => 'CN', 'authkey' => $authkey];
        $res = curl_post('https://dms.lbsapps.cn/sv-prod/index.php/pestdict/api', $sec_data);
        $pest_sbj = json_decode($res, true);
        //得到飞虫的相关数据
        //查询本月捕获的飞虫总数 没有老鼠的就是飞虫
        if ($type == 1) {
            $pest_where = [
                ['year', '=', $year],
                ['month', '=', $singal_month],
                ['type_code', 'IN', ['MY','XW','BY','DJ']]
            ];
        } elseif ($type == 2) {
            $pest_where = [
                ['year', '=', $year],
                ['month', '=', $singal_month],
                ['type_name', '=', '老鼠']
            ];
        } elseif ($type == 3) {
            $pest_where = [
                ['year', '=', $year],
                ['month', '=', $singal_month],
                ['type_code', '=', 'SE']
            ];
        }
        //总数
        $custWhere[] = ['customer_id', '=', $cust['cust_details']['CustomerID']];
        $pest_month_total = $this->statisticsReport->where($custWhere)->where($pest_where)->sum('type_value');
        //某种类型的飞虫
        $pest_max_data = $this->statisticsReport->field('SUM(type_value) AS type_value, type_name')->where($custWhere)->where($pest_where)->group('type_name')->order('type_value DESC')->findOrEmpty()->toArray();
        $pest_result = [];
        // $pest_month_total = 0;
        // $pest_max_data = 0;
        // $pest_trend = 0;
        if (!empty($pest_max_data)) {
            //获取上一个月的数据
            $last_month = date('m', strtotime($month . " -1 month"));
            //查询本月捕获的飞虫总数 没有老鼠的就是飞虫

            if ($type == 1) {
                $pest_where_last = [
                    ['year', '=', $year],
                    ['month', '=', $last_month],
                    ['type_code', '=', 'MY']
                ];
            } else {
                $pest_where_last = [
                    ['year', '=', $year],
                    ['month', '=', $last_month],
                    ['type_name', '=', '老鼠']
                ];
            }
            $pest_month_total_last = $this->statisticsReport->where($custWhere)->where($pest_where_last)->sum('type_value');
            if ($pest_month_total > $pest_month_total_last) {
                $pest_trend = '上升';
            } elseif ($pest_month_total < $pest_month_total_last) {
                $pest_trend = '下降';
            } else {
                $pest_trend = '平稳';
            }
            foreach ($pest_sbj as $k => $v) {
                if($pest_max_data['type_name'] == "盗食占比"){
                    $pest_max_data['type_name']="老鼠";
                }
                if ($v['insect_name'] == $pest_max_data['type_name']) {
                    $pest_result['sub'][] = $v['analysis_result'];
                    $pest_result['sub'][] = $v['suggestion'];
                    $pest_result['sub'][] = $v['measure'];
                }
            }
        }
//        $pest_result['pest_month_total'] = $pest_month_total ?? 0;
//        $pest_result['pest_max_data'] = $pest_max_data ?? 0;
//        if(isset($pest_result['pest_max_data']) && $pest_result['pest_max_data'] == 0){
//            $pest_result['pest_max_data']['type_name'] = '';
//            $pest_result['pest_max_data']['sub'][0] = '';
//            $pest_result['pest_max_data']['sub'][1] = '';
//        }
//        $pest_result['trend'] = $pest_trend ?? '';
//        return $pest_result;

        $pest_result['pest_month_total'] = $pest_month_total ?? 0;
        $pest_result['pest_max_data'] = $pest_max_data ?? null;
        if (empty($pest_result['pest_max_data']) || !is_array($pest_result['pest_max_data'])) {
            $pest_result['pest_max_data'] = ['type_name' => '',];
        }
        if (!isset($pest_result['sub'])) {
            $pest_result['sub'][0] = '暂无数据';
            $pest_result['sub'][1] = '暂无数据';
            $pest_result['sub'][2] = '暂无数据';
        }
        $pest_result['trend'] = $pest_trend ?? '';
        return $pest_result;
    }


    public function echars(): string
    {
        $echarts = ECharts::init("#myChart");
        $option = new Option();
        $option->animation(false);
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae', '#749f83']);
        $option->xAxis([
            "data" => $this->result['line']['keys'],
            "axisLabel" => [
                "rotate" => 45  // 调整标题的旋转角度
            ]
        ]);
        $option->yAxis([]);
        $option->title([
            "text" => $this->result['month'] . "虫害统计图",
            "left" => 'center'
        ]);
        $chart = new Bar();
        $chart->data = $this->result['line']['values'];

        $chart->name = $this->result['month'] . "害虫统计";
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
        $option->color(['#4587E7', '#2f4554', '#61a0a8', '#d48265', '#91c7ae', '#749f83', '#35AB33', '#F5AD1D', '#ff7f50', '#da70d6', '#32cd32', '#6495ed']);
        $option->xAxis([
            'name' => '月份',
            "type" => "category",
            "boundaryGap" => true, // 设置为 true，留有空隙
//            "boundaryGap" => false,
            "data" => [
                '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'
            ],
        ]);
//        设置Y轴
        $option->yAxis([
            'name' => '数量',
            'type' => 'value',
            'min' => 1,
//            'max' =>10000 ,
            'splitNumber' => 10,
            'splitLine' => false
        ]);

        $option->grid([
                'top' => '20%',
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true
            ]
        );
        // print_r($this->result['lion_content']);exit();
        $option->legend([
            "data" => $this->result['lion_title'],
            "backgroundColor" => 'white',
            "top" => '8%',
        ]);

        $option->series($this->result['lion_content']);
        $chart = new Line();
        $chart->name = $this->result['month'] . "害虫统计";
        $chart->itemStyle = [
            'normal' => [
                'label' => [
                    'show' => true,
                    'position' => 'inside',
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
            "text" => $this->result['month'] . '飞虫占比图',
            "left" => 'center'
        ]);
//        $option->grid([
//            "top"=>"25%"
//        ]);
        $option->legend([
            "orient" => 'vertical',
            "left" => 'left',

        ]);
        $option->series([
//                'name' => 'Access From',
                'type' => 'pie',
                'radius' => '65%',
                'data' => $this->result['pie'],
                "backgroundColor" => 'white',
                'label' => [
                    'normal' => [
                        'formatter' => '{b}:{c} ({d}%)',
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
    public function createEcharsBar(string $id = '0', string $title = '柱状图', array $color = ['#4587E7'], array $data = [], array $month = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'], $x = "月份", $y = "数量"): string
    {
        $echarts = ECharts::init("#myChart" . $id);
        $option = new Option();
        $option->animation(false);
        $option->color($color);
        $option->xAxis(["data" => $month, 'name' => $x,]);
        $option->yAxis([
            'name' => $y,
            'type' => 'value',
            'min' => 0,
//            'max' =>10000 ,
            'splitNumber' => 5
        ]);
        $option->title([
            "text" => $title,
            "left" => 'center'
        ]);
        $chart = new Bar();
        $chart->data = $data;
        $chart->name = $this->result['month'] ?? "害虫统计";
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
        $chart->barWidth = 30;
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
            if ($k != "蟑螂监测站-蟑螂") {
                $result[] = $this->createEcharsBar($k . '_' . $id, strval($k), $color, $v);
            }
            $id++;
        }
        return $result;
    }

    public function outputHtml($month, $ctx, $cust)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/analyse/' . $month . '/';
//        $fileName= $cust.'.html';  //获取文件名
        if (!is_dir($dir)) {   //判断目录是否存在
            //不存在则创建
            //   mkdir($pathcurr,0777))
            mkdir(iconv("UTF-8", "GBK", $dir), 0777, true); //iconv方法是为了防止中文乱码，保证可以创建识别中文目录，不用iconv方法格式的话，将无法创建中文目录,第三参数的开启递归模式，默认是关闭的
        }
        $fp = fopen($dir . $cust . '.html', "w");
        $len = fwrite($fp, $ctx);
        fclose($fp);
        $rs = $this->exec($dir, $cust, $cust, $month);
        if ($len > 0) {
            return true;
        }
        return false;
    }

    public function exec($path, $filename, $name, $month)
    {
        $ext_pdf = '.pdf';
        $ext_html = '.html';
        $html_name = $path . $filename . $ext_html;
        $pdf_name = $path . $filename . $ext_pdf;
        $cmd = "wkhtmltopdf --print-media-type --page-size A4 --margin-left 0 --margin-right 0  $html_name $pdf_name 2>&1";
        @exec($cmd, $output, $return_val);
        if ($return_val === 0) {
            $analyseReportModel = new AnalyseReport();
            $file_path = '/analyse/' . $month . '/' . $filename . $ext_pdf;
            // $url_orain = 'https://xcx.lbsapps.com/';
            // $url = $url_orain.$file_path;
            $res = $analyseReportModel->where('url_id', $filename)->update(['url' => $file_path, 'make_flag' => 0]);
            if ($res) {
                return 1;
            }
        }
    }
}
