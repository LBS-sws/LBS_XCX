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
        $BaseHtml = self::CreateBaseHtml($param);
        return self::$startBody . $BaseHtml . self::$endBody;
    }

    /**
     * 创建基础信息
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
}