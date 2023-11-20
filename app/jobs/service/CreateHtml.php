<?php
namespace app\jobs\service;


use app\technician\model\JobOrder;

class CreateHtml
{
    protected static $startBody = '<!DOCTYPE html>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <title>史伟莎服务现场管理报告</title>
    <style>
        body {
            max-width: 800px;
            margin: 0 auto;
        }
        .style-table {
            border-collapse:collapse;
            width: 800px;
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
        }
		
        .first-th {
            font-size: 20px;
            border: 1px solid #cad9ea;
            color: #0c0c0c;
            height: 30px;
            width: 130px;
			text-align:center;
        }
        .first-td {
            font-size: 18px;
            border: 1px solid #dddddd;
            color: #0c0c0c;
            height: 30px;
        }
		.mian-title{
			text-align:left;
			padding-left: 8px;
		}
        .style-table-content {
            margin: 50px auto;
            width: 800px;
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
        .center{
			text-align: center;
		}
		.echart-border-none{
			border-collapse: unset;
		}
		 .head-title {
            background-color: rgb(220, 230, 242);
            color: #ffffff;
            text-align: left;
        }
        .report-img{
			width:20%
		}
		.img {
			max-width: 100%;
			transform: scale(0.5);
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
                <img src="https://xcx.lbsapps.cn/pdf/company/ZY.jpg" style="width: 100%" alt="">
            </td>
        </tr>
    </table></div></body></html>';

    protected static $imgLink = 'https://operation.lbsapps.cn/';

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
        //灭虫单且不是首单才会使用智能设备
        if($BaseData['ServiceType'] == JobOrder::KILL_INSECT_SERVICE && !$BaseData['FirstJob']){
            //智能设备
            $SmarttechHtml = self::createSmarttechHtml($param);
            //智能设备饼状图（最常侦测区域）
            $SmarttechCakeHtml = self::createSmarttechCakeHtml($param);
            //智能设备折线图（侦测趋势 (按时间)）
            $SmarttechLineTimeHtml = self::createSmarttechLineTimeHtml($param);
            //智能设备折线图（侦测趋势 (按日期)）
            $SmarttechLineDateHtml = self::createSmarttechLineDateHtml($param);
        }else{
            $SmarttechHtml = $SmarttechCakeHtml = $SmarttechLineTimeHtml = $SmarttechLineDateHtml = '';
        }
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
                <tr class='head-title'>
                    <th class="first-th mian-title" colspan="13">基础信息</th>
                </tr>
                <tr>
                    <td class="first-th">客户名称</td>
                    <td class="first-td " colspan="10">{$baseInfoData['CustomerName']}</td>
                    <td class="first-th" colspan="1">服务日期</td>
                    <td class="first-td" colspan="6">{$baseInfoData['JobDate']}</td>
                </tr>
                <tr>
                    <td class="first-th">客户地址</td>
                    <td class="first-td" colspan="12">{$baseInfoData['Addr']}</td>
                </tr>
                <tr>
                    <td class="first-th" colspan="1">服务类型</td>
                    <td class="first-td" colspan="6">{$baseInfoData['ServiceName']['ServiceName']}</td>
                    <td class="first-th" colspan="1">服务项目</td>
                    <td class="first-td" colspan="6">{$baseInfoData['service_projects']}</td>
                </tr>
                <tr>
                    <td class="first-th">服务人员</td>
                    <td class="first-td" colspan="6">{$baseInfoData['ContactName']}</td>
                    <td class="first-th">联系电话</td>
                    <td class="first-td" colspan="7">{$baseInfoData['Mobile']}</td>
                </tr>
                <tr>
                    <td class="first-th">任务类型</td>
                    <td class="first-td" colspan="6">{$baseInfoData['task_type']}</td>
                    <td class="first-th">服务人员</td>
                    <td class="first-td" colspan="7">{$baseInfoData['staff']}</td>
                </tr>
                <tr>
                    <td class="first-th">监测设备</td>
                    <td class="first-td" colspan="12">{$baseInfoData['device']}</td>
                </tr>
            </table>
EOD;
            return ['baseInfoHtml'=>$baseInfoHtml,'CustomerName'=>$baseInfoData['CustomerName'],'ServiceType'=>$baseInfoData['ServiceType'],'FirstJob'=>$baseInfoData['FirstJob']];
        }
        return ['baseInfoHtml'=>$baseInfoHtml,'CustomerName'=>'','ServiceType'=>'','FirstJob'=>0];
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
                    <tr class='head-title'>
                        <th class="first-th mian-title" colspan="13">服务简报</th>
                    </tr>
                    <tr>
                        <td class="first-th">服务内容</td>
                        <td class="first-td" colspan="12">{$ServiceBriefingData['content']}</td>
                    </tr>
                    <tr>
                        <td class="first-th">跟进与建议</td>
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
                        <tr class='head-title'>
                            <th class="first-th mian-title" colspan="13">现场工作照</th>
                        </tr>
EOD;
            foreach ($WorkPhotosData as $item){
                $WorkPhotosHtml .= <<<EOD
                    <tr>
                        <td class="first-th">{$item['remarks']}</td>
                        <td class="first-td" colspan="12">
EOD;
                foreach ($item['site_photos'] as $v) {
                    if(!empty($v)){
                        $img = base64EncodeImage(self::$imgLink.$v);
                        $WorkPhotosHtml .= <<<EOD
                    {$img}
EOD;
                    }
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
                        <tr class='head-title'>
                            <th class="first-th mian-title" colspan="13">物料使用</th>
                        </tr>
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
                            <td class="first-th">{$item['material_name']}</td>
                            <td class="first-th">{$item['processing_space']}</td>
                            <td class="first-th">{$item['material_ratio']}</td>
                            <td class="first-th">{$item['dosage']} {$item['unit']}</td>
                            <td class="first-th">{$item['use_mode']}</td>
                            <td class="first-th">{$item['targets']}</td>
                            <td class="first-th">{$item['use_area']}</td>
                            <td class="first-th">{$item['matters_needing_attention']}</td>
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
                        <tr class='head-title'>
                            <th class="first-th mian-title" colspan="13">现场风险评估与建议</th>
                        </tr>
                    <tr>
                        <td class="first-th">风险类别</td>
                        <td class="first-th">风险描述</td>
                        <td class="first-th">靶标</td>
                        <td class="first-th">级别</td>
                        <td class="first-th">整改建议</td>
                        <td class="first-th">采取措施</td>
                        <td class="first-th">跟进日期</td>
                    </tr>
EOD;
            foreach ($RiskData as $item){
                $RiskHtml .= <<<EOD
                        <tr>
                            <td class="first-th">{$item['risk_types']}</td>
                            <td class="first-th">{$item['risk_description']}</td>
                            <td class="first-th">{$item['risk_targets']}</td>
                            <td class="first-th">{$item['risk_rank']}</td>
                            <td class="first-th">{$item['risk_proposal']}</td>
                            <td class="first-th">{$item['take_steps']}</td>
                            <td class="first-th">{$item['ct']}</td>
                        </tr>
EOD;
                if(!empty($item['site_img'])){
                    $RiskHtml .= <<<EOD
                        <tr>
                            <td class="first-th" >风险图片</td>
                            <td class="first-td" colspan="6">
EOD;
                    foreach ($item['site_img'] as $img){
                        $img = base64EncodeImage(self::$imgLink.$img);
                        $RiskHtml .= <<<EOD
                           {$img}
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
        $data = ReportData::getDeviceInspectionInfo($param);
        $DeviceInspectionHtml = '';
        if(!empty($data)){
            $DeviceInspectionData = $data['data'];
            $max_count = $data['max_count'];
            $DeviceInspectionHtml .= <<<EOD
                <table class="style-table">
                    <tr class='head-title'>
                        <th class="first-th mian-title" colspan="13">设备巡查</th>
                    </tr>
EOD;
            foreach ($DeviceInspectionData as $key=>$item){
                $DeviceInspectionHtml .= <<<EOD
                    <tr>
                        <td colspan="13" align="left">{$item['device_info']['name']}({$item['tigger_count']}/{$item['equipment_total_count']})</td>
                    </tr>
EOD;
                $DeviceInspectionHtml .= <<<EOD
                    <tr>
                        <td class="first-th">序号</td>
                        <td class="first-th">区域</td>
EOD;
                foreach ($item['device_info']['check_targt'] as $k=>$v){
                    $count = count($item['device_info']['check_targt']);
                    if($count < $max_count){
                        $Cells = ($max_count - $count) +1 ;
                        if($k == $count-1){
                            $DeviceInspectionHtml .= <<<EOD
                        <td class="first-th" colspan="{$Cells}">{$v}</td>
EOD;
                        }else{
                            $DeviceInspectionHtml .= <<<EOD
                        <td class="first-th" >{$v}</td>
EOD;
                        }
                    }else{
                        $DeviceInspectionHtml .= <<<EOD
                        <td class="first-th">{$v}</td>
EOD;
                    }
                }
                $DeviceInspectionHtml .= <<<EOD
                        <td class="first-th" style="width:25%;">检查与处理</td>
                        <td class="first-th" style="width:25%;">补充说明</td>
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
                        $v_count = count($sbArr);
                        foreach ($sbArr as $v_k =>$targt){
                            if($v_count < $max_count){
                                $Cells = ($max_count - $v_count) +1 ;
                                if($v_k == $v_count-1){
                                    $DeviceInspectionHtml .= <<<EOD
                            <td class="first-th" colspan="{$Cells}">{$targt['value']}</td>
EOD;
                                }else{
                                    $DeviceInspectionHtml .= <<<EOD
                            <td class="first-th">{$targt['value']}</td>
EOD;
                                }
                            }else{
                                $DeviceInspectionHtml .= <<<EOD
                            <td class="first-th">{$targt['value']}</td>
EOD;
                            }
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
                            <tr class='head-title'>
                                <th class="first-th mian-title" colspan="13">智能鼠密度监测</th>
                            </tr>
EOF;
            $SmarttechHtml .= <<<EOF
                    <tr>
                        <td colspan="13" align="left">{$SmarttechData['device_cn_name']} ({$SmarttechData['tigger_device_count']}/{$SmarttechData['device_count']})</td>
                    </tr>
					<tr>
                        <td class="first-th">装置名称</td>
                        <td class="first-th">区域</td>
                        <td class="first-th">{$SmarttechData['work_time']} 触发次数</td>
                        <td class="first-th">{$SmarttechData['no_work_time']} 触发次数</td>
                    </tr>
EOF;
            foreach ($SmarttechData['list']  as $item) {
                $SmarttechHtml .= <<<EOF
					<tr class='center'>
                        <td class="first-td">{$item['Device_Name']}</td>
                        <td class="first-td">{$item['area']}</td>
                        <td class="first-td">{$item['work_count']}</td>
                        <td class="first-td">{$item['no_work_count']}</td>
                    </tr>
EOF;
            }
        }
        return $SmarttechHtml;
    }

    /**
     * 智能设备饼状图
     * @param $param
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function createSmarttechCakeHtml($param)
    {
        $SmarttechCakeData = ReportData::getSmarttechCakeInfo($param);
        $SmarttechCakeHtml = '';
        if(!empty($SmarttechCakeData)){
            $SmarttechCakeHtml = <<<EOF
                <table class="style-table echart-border-none">
                   <tr>
                         <td colspan="13" align="left" >最常侦测区域</td>
                   </tr>   
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function createSmarttechLineTimeHtml($param)
    {
        $SmarttechLineTimeData = ReportData::getSmarttechLineTimeInfo($param);
        $SmarttechLineTimeHtml = '';
        if(!empty($SmarttechLineTimeData)){
            $SmarttechLineTimeHtml = <<<EOF
                <table class="style-table echart-border-none">
                    <tr>
                         <td colspan="13" align="left" >侦测趋势 (按时间)</td>
                    </tr>    
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function createSmarttechLineDateHtml($param)
    {
        $SmarttechLineDateData = ReportData::getSmarttechLineDateInfo($param);
        $SmarttechLineDateHtml = '';
        if(!empty($SmarttechLineDateData)){
            $SmarttechLineDateHtml = <<<EOF
                <table class="style-table echart-border-none">
                    <tr>
                        <td colspan="13" align="left" >侦测趋势 (按日期)</td>
                    </tr>
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
                        <tr class='head-title'>
                            <th class="first-th mian-title" colspan="13">客户点评</th>
                        </tr>
                    <tr>
                        <th colspan="13" align="left" style="padding: 10px;">{$CustomerCommentsData}星(1~3)</th>
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
                        <tr class='head-title'>
                            <th class="first-th mian-title" colspan="13">报告签名</th>
                        </tr>
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
                        $img = base64EncodeImage(self::$imgLink.$item);
                        $ReportSignatureHtml .= <<<EOF
                            {$img}
EOF;
                    }
                }
            }
            $ReportSignatureHtml .= <<<EOF
                        </td>
                        <td class="first-td">
EOF;
            if(!empty($ReportSignatureData['customer_signature'])){
                foreach ($ReportSignatureData['customer_signature'] as $item){
                    if(!empty($item)){
                        $img = base64EncodeImage(self::$imgLink.$item);
                        $ReportSignatureHtml .= <<<EOF
                            {$img}
EOF;
                    }
                }
            }
            $ReportSignatureHtml .= <<<EOF
                        </td>
                    </tr>
                </table>
EOF;
        }
        return $ReportSignatureHtml;
    }
}