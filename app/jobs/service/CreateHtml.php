<?php
namespace app\jobs\service;


class CreateHtml
{
    protected static $startBody = '<!DOCTYPE html>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html;charset=utf-8">

    <title>史伟莎服务现场管理报告</title>
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
                <div class="big-title">史伟莎服务现场管理报告</div>
            </td>
			</tr>
    </table>';

    protected static $endBody = '<table class="style-table-content">
        <tr class="footer-th">
            <td class="footer-td" colspan="14">以上报告说明希望得到您的支持和认可，如有疑问请与我们联系。</td>
        </tr>
        <tr class="footer-th">
            <td class="footer-td" colspan="14">
                <img src=https://xcx.lbsapps.cn/pdf/company/ZY.jpg style="width: 100%" alt="">
            </td>
        </tr>
    </table></div></body></html>';

    public static function CreateHtml($param)
    {
        //基础信息
        $BaseData = self::CreateBaseHtml($param);
        //服务简报
        $ServiceBriefingHtml = self::CreateServiceBriefing($param);
        //现场工作照
        $WorkPhotosHtml = self::CreateWorkPhotosHtml($param);
        //物料使用
        $MaterialUsageHtml = self::CreateMaterialUsageHtml($param);
        //现场风险评估与建议
        $RiskHtml = self::CreateRiskHtml($param);
        //设备巡查
        $DeviceInspectionHtml = self::CreateDeviceInspectionHtml($param);
        //智能设备
        $SmarttechHtml = self::createSmarttechHtml($param);
        //智能设备饼状图（最常侦测区域）
        $SmarttechCakeHtml = self::createSmarttechCakeHtml($param);
        //智能设备折线图（侦测趋势 (按时间)）
        $SmarttechLineTimeHtml = self::createSmarttechLineTimeHtml($param);
        //智能设备折线图（侦测趋势 (按日期)）
        $SmarttechLineDateHtml = self::createSmarttechLineDateHtml($param);
        //客户点评
        $CustomerCommentsHtml = self::createCustomerCommentsHtml($param);
        //报告签名
        $ReportSignatureHtml = self::createReportSignatureHtml($param);

        $html =  self::$startBody . $BaseData['baseInfoHtml'] . $ServiceBriefingHtml . $WorkPhotosHtml . $MaterialUsageHtml . $RiskHtml . $DeviceInspectionHtml . $SmarttechHtml . $SmarttechCakeHtml . $SmarttechLineTimeHtml . $SmarttechLineDateHtml . $CustomerCommentsHtml . $ReportSignatureHtml.self::$endBody;
        $CustomerName = $BaseData['CustomerName'];
        return ['html'=>$html,'CustomerName'=>$CustomerName];
    }

    /**
     * 基础信息
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function CreateBaseHtml($param)
    {
        $baseInfoData = ReportData::getBaseInfo($param);
        $baseInfoHtml = '';
        if(!empty($baseInfoData)){
            $baseInfoHtml .= <<<EOD
            <table class="style-table">
                <thead>
                <tr>
                    <th class="first-th mian-title" colspan="13">基础信息</th>
                </tr>
                </thead>
                <tr>
                    <th class="first-th">客户名称</th>
                    <td class="first-td " colspan="10">{$baseInfoData['CustomerName']}</td>
                    <th class="first-th" colspan="1">服务日期</th>
                    <td class="first-td" colspan="6">{$baseInfoData['JobDate']}</td>
                </tr>
                <tr>
                    <th class="first-th">客户地址</th>
                    <td class="first-td" colspan="12">{$baseInfoData['Addr']}</td>
                </tr>
                <tr>
                    <th class="first-th" colspan="1">服务类型</th>
                    <td class="first-td" colspan="6">{$baseInfoData['ServiceName']['ServiceName']}</td>
                    <th class="first-th" colspan="1">服务项目</th>
                    <td class="first-td" colspan="6">{$baseInfoData['service_projects']}</td>
                </tr>
                <tr>
                    <th class="first-th">服务人员</th>
                    <td class="first-td" colspan="6">{$baseInfoData['ContactName']}</td>
                    <th class="first-th">联系电话</th>
                    <td class="first-td" colspan="7">{$baseInfoData['Mobile']}</td>
                </tr>
                <tr>
                    <th class="first-th">任务类型</th>
                    <td class="first-td" colspan="6">{$baseInfoData['task_type']}</td>
                    <th class="first-th">服务人员</th>
                    <td class="first-td" colspan="7">{$baseInfoData['staff']}</td>
                </tr>
                <tr>
                    <th class="first-th">监测设备</th>
                    <td class="first-td" colspan="12">{$baseInfoData['device']}</td>
                </tr>
            </table>
EOD;
            return ['baseInfoHtml'=>$baseInfoHtml,'CustomerName'=>$baseInfoData['CustomerName']];
        }
        return ['baseInfoHtml'=>$baseInfoHtml,'CustomerName'=>''];
    }

    /**
     * 服务简报
     * @param $param
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function CreateServiceBriefing($param)
    {
        $ServiceBriefingData = ReportData::getServiceBriefingInfo($param);
        $ServiceBriefingHtml = '';
        if(!empty($ServiceBriefingData)){
            $ServiceBriefingHtml = <<<EOD
                <table class="style-table">
                    <thead>
                    <tr>
                        <th class="first-th mian-title" colspan="13">服务简报</th>
                    </tr>
                    </thead>
                    <tr>
                        <th class="first-th">服务内容</th>
                        <td class="first-td" colspan="12">{$ServiceBriefingData['content']}</td>
                    </tr>
                    <tr>
                        <th class="first-th">跟进与建议</th>
                        <td class="first-td" colspan="12">{$ServiceBriefingData['proposal']}</td>
                    </tr>
                </table>
EOD;
        }
        return $ServiceBriefingHtml;
    }

    /**
     * 现场工作照
     * @param $param
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function CreateWorkPhotosHtml($param)
    {
        $WorkPhotosData = ReportData::getWorkPhotosInfo($param);
        $WorkPhotosHtml = '';
        if(!empty($WorkPhotosData)){
            $WorkPhotosHtml .= <<<EOD
                    <table class="style-table">
                    <thead>
                        <tr>
                            <th class="first-th mian-title" colspan="13">现场工作照</th>
                        </tr>
                    </thead>
EOD;
            foreach ($WorkPhotosData as $item){
                $WorkPhotosHtml .= <<<EOD
                    <tr>
                        <th class="first-th">{$item['remarks']}</th>
                        <td class="first-td" colspan="12">
EOD;
                foreach ($item['site_photos'] as $v) {
//                    $img = base64EncodeImage('https://xcx.lbsapps.cn/storage/img/20231024/231024175402496183355.jpg');
                    $img = 'https://lbsxcx.com/'.$v;
                    $WorkPhotosHtml .= <<<EOD
                    <img class="logo" src="{$img}" alt="">
EOD;
                }
                    $WorkPhotosHtml .= <<<EOD
                        </td>
                    </tr>
                    </table>
EOD;
            }
        }
        return $WorkPhotosHtml;
    }

    /**
     * 物料使用
     * @param $param
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function CreateMaterialUsageHtml($param)
    {
        $MaterialUsageData = ReportData::getMaterialUsageInfo($param);
        $MaterialUsageHtml = '';
        if(!empty($MaterialUsageData)){
            $MaterialUsageHtml .= <<<EOD
                    <table class="style-table">
                    <thead>
                        <tr>
                            <th class="first-th mian-title" colspan="13">物料使用</th>
                        </tr>
                    </thead>
                    <tr>
                        <th class="first-th">名称</th>
                        <th class="first-th">处理面积</th>
                        <th class="first-th">配比</th>
                        <th class="first-th">用量</th>
                        <th class="first-th">使用方式</th>
                        <th class="first-th">靶标</th>
                        <th class="first-th">使用区域</th>
                        <th class="first-th">备注</th>
                    </tr>
EOD;
            foreach ($MaterialUsageData as $item){
                $MaterialUsageHtml .= <<<EOD
                        <tr>
                            <td class="first-td">{$item['material_name']}</td>
                            <td class="first-td">{$item['processing_space']}</td>
                            <td class="first-td">{$item['material_ratio']}</td>
                            <td class="first-td">{$item['dosage']} {$item['unit']}</td>
                            <td class="first-td">{$item['use_mode']}</td>
                            <td class="first-td">{$item['targets']}</td>
                            <td class="first-td">{$item['use_area']}</td>
                            <td class="first-td">{$item['matters_needing_attention']}</td>
                        </tr>
EOD;
            }
            $MaterialUsageHtml .= <<<EOD
                    </table>
EOD;

        }
        return $MaterialUsageHtml;
    }

    /**
     * 现场风险评估与建议
     * @param $param
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function CreateRiskHtml($param)
    {
        $RiskData = ReportData::getRiskInfo($param);
        $RiskHtml = '';
        if(!empty($RiskData)){
            $RiskHtml .= <<<EOD
                    <table class="style-table">
                    <thead>
                        <tr>
                            <th class="first-th mian-title" colspan="13">现场风险评估与建议</th>
                        </tr>
                    </thead>
                    <tr>
                        <th class="first-th">风险类别</th>
                        <th class="first-th">风险描述</th>
                        <th class="first-th">靶标</th>
                        <th class="first-th">级别</th>
                        <th class="first-th">整改建议</th>
                        <th class="first-th">采取措施</th>
                        <th class="first-th">跟进日期</th>
                    </tr>
EOD;
            foreach ($RiskData as $item){
                $RiskHtml .= <<<EOD
                        <tr>
                            <td class="first-td">{$item['risk_types']}</td>
                            <td class="first-td">{$item['risk_description']}</td>
                            <td class="first-td">{$item['risk_targets']}</td>
                            <td class="first-td">{$item['risk_rank']}</td>
                            <td class="first-td">{$item['risk_proposal']}</td>
                            <td class="first-td">{$item['take_steps']}</td>
                            <td class="first-td">{$item['ct']}</td>
                        </tr>
EOD;
                if(!empty($item['site_img'])){
                    $RiskHtml .= <<<EOD
                        <tr>
                            <td class="first-th" >风险图片</td>
                            <td class="first-th" colspan="6">
EOD;
                    foreach ($item['site_img'] as $img){
                        $url = 'https://lbsxcx.com'.$img;
                        $RiskHtml .= <<<EOD
                           <img class="logo" src="{$url}" alt="">
EOD;
                    }
                    $RiskHtml .= <<<EOD
                            </td>
                        </tr>
EOD;
                }
            }
            $RiskHtml .= <<<EOD
                    </table>
EOD;
        }
        return $RiskHtml;
    }

    /**
     * 设备巡查
     * @param $param
     * @return string
     */
    public static function CreateDeviceInspectionHtml($param)
    {
        $DeviceInspectionData = ReportData::getDeviceInspectionInfo($param);
//        print_r($DeviceInspectionData);exit;
        $DeviceInspectionHtml = '';
        if(!empty($DeviceInspectionData)){
            $DeviceInspectionHtml .= <<<EOD
                <table class="style-table">
                <thead>
                    <tr>
                        <th class="first-th mian-title" colspan="13">设备巡查</th>
                    </tr>
                </thead>
EOD;
            foreach ($DeviceInspectionData as $key=>$item){
                $DeviceInspectionHtml .= <<<EOD
                    <tr>
                        <th colspan="13" align="left">{$item['device_info']['name']}({$item['tigger_count']}/{$item['equipment_total_count']})</th>
                    </tr>
EOD;
                $DeviceInspectionHtml .= <<<EOD
                    <tr>
                        <th class="first-th">序号</th>
                        <th class="first-th">区域</th>
EOD;
                foreach ($item['device_info']['check_targt'] as $v){
                    $DeviceInspectionHtml .= <<<EOD
                        <th class="first-th">{$v}</th>
EOD;
                }
                $DeviceInspectionHtml .= <<<EOD
                        
                        <th class="first-th">检查与处理</th>
                        <th class="first-th">补充说明</th>
                    </tr>
EOD;
                foreach ($item['equipment_list'] as $eq){
                    $DeviceInspectionHtml .= <<<EOD
                    <tr>
                        <td class="first-th">{$eq['number']}</td>
                        <td class="first-th">{$eq['equipment_area']}</td>
EOD;
                    if(!empty($eq['check_datas'])){
                        $sbArr = json_decode($eq['check_datas'],true);
                        foreach ($sbArr as $targt){
                            $DeviceInspectionHtml .= <<<EOD
                            <td class="first-th">{$targt['value']}</td>
EOD;
                        }
                    }
                    $DeviceInspectionHtml .= <<<EOD
                        <td class="first-th">{$eq['check_handle']}</td>
                        <td class="first-th">{$eq['more_info']}</td>
                    </tr>
EOD;
                }
            }
            $DeviceInspectionHtml .= <<<EOD
                </table>
EOD;
        }
        return $DeviceInspectionHtml;
    }

    /**
     * 智能设备
     * @param $param
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function createSmarttechHtml($param)
    {
        $SmarttechData = ReportData::getSmarttechInfo($param);
        $SmarttechHtml = '';
        if(!empty($SmarttechData)){
            $SmarttechHtml = <<<EOF
                <table class="style-table">
                        <thead>
                            <tr>
                                <th class="first-th mian-title" colspan="13">智能设备</th>
                            </tr>
                        </thead>
EOF;
            foreach ($SmarttechData  as $item){
                $SmarttechHtml .= <<<EOF
                    <tr>
                        <th colspan="13" align="left">{$item['device_cn_name']} ({$item['all_trigger_count']}/{$item['device_count']})</th>
                    </tr>
					<tr>
                        <th class="first-th">装置名称</th>
                        <th class="first-th">区域</th>
                        <th class="first-th">08：00-00:00 触发次数</th>
                        <th class="first-th">00：00-08:00 触发次数</th>
                    </tr>
EOF;
                foreach ($item['list']  as $k=>$v) {
                    $SmarttechHtml .= <<<EOF
					<tr>
                        <td class="first-td">{$v['Device_Name']}</td>
                        <td class="first-td">{$v['floor']} {$v['layer']} {$v['others']}</td>
                        <td class="first-td">{$v['day_trigger_count']}</td>
                        <td class="first-td">{$v['night_trigger_count']}</td>
                    </tr>
EOF;
                }
            }
        }
        return $SmarttechHtml;
    }

    /**
     * 智能设备饼状图
     * @param $param
     * @return string
     */
    public static function createSmarttechCakeHtml($param)
    {
        $SmarttechCakeData = ReportData::getSmarttechCakeInfo($param);
        $SmarttechCakeHtml = '';
        if(!empty($SmarttechCakeData)){
            $SmarttechCakeHtml = <<<EOF
                <table class="style-table">
                    <thead>
                        <tr>
                            <th class="first-th mian-title" colspan="13">智能设备饼状图（最常侦测区域）</th>
                        </tr>
                    </thead>
                    <tr>       
                        <td width="100%">
                            {$SmarttechCakeData}
                        </td>      
                    </tr>
                </table>
EOF;
        }
        return $SmarttechCakeHtml;
    }

    /**
     * 智能设备折线图（侦测趋势 (按时间)）
     * @param $param
     * @return string
     */
    public static function createSmarttechLineTimeHtml($param)
    {
        $SmarttechLineTimeData = ReportData::getSmarttechLineTimeInfo($param);
        $SmarttechLineTimeHtml = '';
        if(!empty($SmarttechLineTimeData)){
            $SmarttechLineTimeHtml = <<<EOF
                <table class="style-table">
                    <thead>
                        <tr>
                            <th class="first-th mian-title" colspan="13">智能设备折线图（侦测趋势 (按时间)）</th>
                        </tr>
                    </thead>
                    <tr>       
                        <td width="100%">
                            {$SmarttechLineTimeData}
                        </td>      
                    </tr>
                </table>
EOF;
        }
        return $SmarttechLineTimeHtml;
    }

    /**
     * 智能设备折线图（侦测趋势 (按日期)）
     * @param $param
     * @return string
     */
    public static function createSmarttechLineDateHtml($param)
    {
        $SmarttechLineDateData = ReportData::getSmarttechLineDateInfo($param);
        $SmarttechLineDateHtml = '';
        if(!empty($SmarttechLineDateData)){
            $SmarttechLineDateHtml = <<<EOF
                <table class="style-table">
                    <thead>
                        <tr>
                            <th class="first-th mian-title" colspan="13">能设备折线图（侦测趋势 (按日期)）</th>
                        </tr>
                    </thead>
                    <tr>       
                        <td width="100%">
                            {$SmarttechLineDateData}
                        </td>      
                    </tr>
                </table>
EOF;
        }
        return $SmarttechLineDateHtml;
    }

    /**
     * 客户点评
     * @param $param
     * @return string
     */
    public static function createCustomerCommentsHtml($param)
    {
        $CustomerCommentsData = ReportData::getCustomerCommentsInfo($param);
        $CustomerCommentsHtml = '';
        if(!empty($CustomerCommentsData)){
            $CustomerCommentsHtml .= <<<EOF
                <table class="style-table">
                    <thead>
                        <tr>
                            <th class="first-th mian-title" colspan="13">客户点评</th>
                        </tr>
                    </thead>
                    <tr>
                        <th colspan="13" align="left">{$CustomerCommentsData}星(1~3)</th>
                    </tr>
                </table>
EOF;
        }
        return $CustomerCommentsHtml;
    }

    /**
     * 报告签名
     * @param $param
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function createReportSignatureHtml($param)
    {
        $ReportSignatureData = ReportData::getReportSignatureInfo($param);
        $ReportSignatureHtml = '';
        if(!empty($ReportSignatureData)){
            $ReportSignatureHtml .= <<<EOF
                <table class="style-table">
                    <thead>
                        <tr>
                            <th class="first-th mian-title" colspan="13">报告签名</th>
                        </tr>
                    </thead>
                    <tr>
                        <th class="first-th">服务人员签字</th>
                        <th class="first-th">客户签字</th>
                    </tr>
                    <tr>
                        <td class="first-td">
EOF;
            if(!empty($ReportSignatureData['staff'])){
                foreach ($ReportSignatureData['staff'] as $item){
                    if(!empty($item)){
                        $ReportSignatureHtml .= <<<EOF
                            <img class="logo" src="{$item}" alt="">
EOF;
                    }
                }
            }
            $ReportSignatureHtml .= <<<EOF
                        </td>
                        <td class="first-td">
EOF;
            $ReportSignatureHtml .= <<<EOF
                            <img class="logo" src="{$ReportSignatureData['customer_signature_url']}" alt="">
EOF;
            $ReportSignatureHtml .= <<<EOF
                        </td>
                    </tr>
                </table>
EOF;
        }
        return $ReportSignatureHtml;
    }
}