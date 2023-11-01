<?php
namespace app\jobs\service;


class CreateHtml
{
    protected static $startBody = '<!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="UTF-8">
              <meta http-equiv="X-UA-Compatible" content="IE=edge">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>LBS-SERVICE REPORT</title>
              <style>
            body{
                padding: 0;
                font-family: STFangsong;
            }
            .myTable {
                height: 300px;
                width: 100%;
                font-family:STFangsong;
            }
            .myTitle {
                background-color: #eeeeee;
                font-size: 17px;
                font-weight: bold;
            }
            tr:hover {
                background: #edffcf;
            }
            th {
                font-size: 17px;
                font-weight: bold;
            }
            td {
                font-size: 16px;
            }
            th,td {
                border: solid 1px #eeeeee;
                text-align: center;
            }
            p{
                font-size: 18px;
                line-height:10px;
            }
            </style>
            </head>
            <body><table class="myTable" cellpadding="5">';

    protected static $endBody = '</table></body></html>';

    public static function CreateHtml($param)
    {
        //基础信息
        $BaseHtml = self::CreateBaseHtml($param);
        //服务简报
        $ServiceBriefingHtml = self::CreateServiceBriefing($param);
        //现场工作照
        $WorkPhotosHtml = self::CreateWorkPhotosHtml($param);
        //物料使用
        $MaterialUsageHtml = self::CreateMaterialUsageHtml($param);
        //现场风险评估与建议
        $RiskHtml = self::CreateRiskHtml($param);
        return self::$startBody . $BaseHtml . $ServiceBriefingHtml . $WorkPhotosHtml . $MaterialUsageHtml . $RiskHtml .self::$endBody;
    }

    /**
     * 基础信息
     * @param $param
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function CreateBaseHtml($param)
    {
        $baseInfoData = ReportData::getBaseInfo($param);
        $baseInfoHtml = <<<EOD
                <tr class="myTitle">
                    <th  width="100%"  style="text-align:left" align="left">基础信息</th>
                </tr>
                <tr>
                    <td width="15%">客户名称</td>
                    <td width="35%" align="left">{$baseInfoData['CustomerName']}</td>
                    <td width="15%">服务日期</td>
                    <td width="35%" align="left">{$baseInfoData['JobDate']}</td>
                </tr>
                <tr>
                    <td width="15%">客户地址</td>
                    <td width="85%" align="left">{$baseInfoData['Addr']}</td>
                </tr>
                <tr>
                    <td width="15%">服务类型</td>
                    <td width="35%" align="left">{$baseInfoData['ServiceName']['ServiceName']}</td>
                    <td width="15%">服务项目</td>
                    <td width="35%" align="left">{$baseInfoData['service_projects']}</td>
                </tr>
                <tr>
                    <td width="15%">联系人员</td>
                    <td width="35%" align="left">{$baseInfoData['ContactName']}</td>
                    <td width="15%">联系电话</td>
                    <td width="35%" align="left">{$baseInfoData['Mobile']}</td>
                </tr>
                <tr>
                    <td width="15%">任务类型</td>
                    <td width="35%" align="left">{$baseInfoData['task_type']}</td>
                    <td width="15%">服务人员</td>
                    <td width="35%" align="left">{$baseInfoData['staff']}</td>
                </tr>
                <tr>
                    <td width="15%">监测设备</td>
                    <td width="85%" align="left">{$baseInfoData['device']}</td>
                </tr>
EOD;
        return $baseInfoHtml;
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
                <tr class="myTitle">
                        <th width="100%" align="left">服务简报</th>
                    </tr>
                    <tr>
                        <td width="15%">服务内容</td>
                        <td width="85%" align="left">{$ServiceBriefingData['content']}</td>
                    </tr>
                    <tr>
                        <td width="15%">跟进与建议</td>
                        <td width="85%" align="left">{$ServiceBriefingData['proposal']}</td>
                    </tr>
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
                    <tr class="myTitle">
                        <th width="100%" align="left">现场工作照</th>
                    </tr>
EOD;
            foreach ($WorkPhotosData as $item){
                $WorkPhotosHtml .= <<<EOD
                    <tr>
                        <td width="15%">{$item['remarks']}</td>
                        <td width="20%" align="center">
EOD;
                foreach ($item['site_photos'] as $v) {
                    $WorkPhotosHtml .= <<<EOD
                    <img src="{$v}" width="80" height="100" style="padding:20px 50px;">
EOD;
                }
                    $WorkPhotosHtml .= <<<EOD
                        </td>
                    </tr>
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
                    <tr class="myTitle">
                       <th width="100%" align="left">物料使用</th>
                    </tr>  
                    <tr>
                        <td width="15%">名称</td>
                        <td width="12%">处理面积</td>
                        <td width="7%">配比</td>
                        <td width="8%">用量</td>
                        <td width="12%">使用方式</td>
                        <td width="12%">靶标</td>
                        <td width="12%">使用区域</td>
                        <td width="22%">备注</td>
                    </tr>
EOD;
            foreach ($MaterialUsageData as $item){
                $MaterialUsageHtml .= <<<EOD
                        <tr>
                            <td width="15%">{$item['material_name']}</td>
                            <td width="12%">{$item['processing_space']}</td>
                            <td width="7%">{$item['material_ratio']}</td>
                            <td width="8%">{$item['dosage']} {$item['unit']}</td>
                            <td width="12%" align="left">{$item['use_mode']}</td>
                            <td width="12%" align="left">{$item['targets']}</td>
                            <td width="12%" align="left">{$item['use_area']}</td>
                            <td width="22%" align="left">{$item['matters_needing_attention']}</td>
                      </tr>  
EOD;
            }
        }
        return $MaterialUsageHtml;
    }

    public static function CreateRiskHtml($param)
    {
        $RiskData = ReportData::getRiskInfo($param);
        $RiskHtml = '';
        if(!empty($RiskData)){
            $RiskHtml .= <<<EOD
                     <tr class="myTitle">
                            <th width="100%"align="left">现场风险评估与建议</th>
                     </tr>  
                     <tr>
                            <td width="16%">风险类别</td>
                            <td width="19%">风险描述</td>
                            <td width="13%">靶标</td>
                            <td width="7%">级别</td>
                            <td width="15%">整改建议</td>
                            <td width="15%">采取措施</td>
                            <td width="15%">跟进日期</td>
                     </tr>
EOD;
            foreach ($RiskData as $item){
                $MaterialUsageHtml .= <<<EOD
                        <tr>
                            <td width="15%">{$item['material_name']}</td>
                            <td width="12%">{$item['processing_space']}</td>
                            <td width="7%">{$item['material_ratio']}</td>
                            <td width="8%">{$item['dosage']} {$item['unit']}</td>
                            <td width="12%" align="left">{$item['use_mode']}</td>
                            <td width="12%" align="left">{$item['targets']}</td>
                            <td width="12%" align="left">{$item['use_area']}</td>
                            <td width="22%" align="left">{$item['matters_needing_attention']}</td>
                        </tr>  
EOD;
            }
        }
        return $RiskHtml;

    }
}