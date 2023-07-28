<?php

namespace app\api\controller;

use app\BaseController;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class Test1 extends BaseController
{
    public $workList = [
        ['鼠饵站','鼠类防制检查记录表'],
        ['粘鼠板OR捕鼠夹','鼠类防制检查记录表'],
        ['灭蝇灯','飞虫防制检查记录表(灭蝇灯)'],
        ['蟑螂屋','蟑螂防制检查记录表'],
    ];
    public $type = [
        ['盗食50%以下','盗食50%以上','无盗食'],
        ['发现老鼠 \n Rat caught','没发现老鼠 \n Rat activity unidentified'],
        ['苍蝇','蚊子','卫生性飞虫','绿化飞虫','仓储害虫'],
        ['"捕获蟑螂数量 \n Number of captured'],
    ];
    public function index(){
        $res =  curl_post("https://xcx.lbsapps.cn/api/CheckLog?job_id=25173033");
        $res_de = json_decode($res, true);
        if (isset($res_de) && $res_de['code'] == 0) {
            echo '<script>window.open("' . $res_de['data'] . '", "_blank");</script>';
        }else{
            echo $res_de['msg'];
        }
    }

    public function index1()
    {
        // 创建一个新的 Spreadsheet 对象
        $spreadsheet = new Spreadsheet();
        foreach ($this->workList as $index => $workName) {
//            $worksheet = $workName[0] ?? '';
            $workTitle = $workName[0] ?? '';
            // 创建工作表
            $worksheet = $spreadsheet->createSheet($index);
            $worksheet->setTitle($workTitle);
            // 清除未使用的样式，并设置为空白
            $this->clearUnusedStyles($worksheet);
            // 添加数据和设置样式
            $this->setWorksheetData($worksheet, $workName);
        }

        // 删除第一个空白的worksheet
//        $firstWorksheet = $spreadsheet->getSheet(0);
//        $spreadsheet->removeSheetByIndex($firstWorksheet->getIndex());

        // 保存 Excel 文件
        $writer = new Xlsx($spreadsheet);
        $writer->save('蟑螂防制检查记录表3.xlsx');
    }


    private function setWorksheetData($worksheet, $workName)
    {
        // 插入 Logo
        $this->insertLogo($worksheet);
//        dd($workName);
        // 创建标题
        $this->createTitle($worksheet, $workName[1]);

        // 设置设备数量和盗食数量
        $this->setDeviceAndTheftQuantity($worksheet);

        // 设置表头
        $this->setTableHeader($worksheet);

        // 设置捕获蟑螂数量
        $this->setCockroachQuantity($worksheet);

        // 设置处理情况表头
        $this->setActionTakenHeader($worksheet);

        // 设置复核人签名
        $this->setReviewerSignature($worksheet);

        // 设置列宽
        $this->setColumnWidth($worksheet);

        // 设置单元格边框
        $this->setCellBorders($worksheet);
    }
// 清除未使用的样式，并设置为空白
    private function clearUnusedStyles($worksheet)
    {
        $highestColumn = $worksheet->getHighestColumn();
        $highestRow = $worksheet->getHighestRow();

        // 清除未使用的列和行的格式
        for ($column = 'I'; $column <= $highestColumn; $column++) {
            $worksheet->getColumnDimension($column)->setVisible(false);
        }

        // 清除未使用的行的格式
        for ($row = 33; $row <= $highestRow; $row++) {
            $worksheet->getRowDimension($row)->setVisible(false);
        }

        // 设置未使用的列和行为空白
        $range = 'I1:' . $highestColumn . '32';
        $worksheet->getStyle($range)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'], // 设置填充颜色为白色
            ],
        ]);

        // 设置未使用的行为空白
        $range = 'A33:' . $highestColumn . $highestRow;
        $worksheet->getStyle($range)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'], // 设置填充颜色为白色
            ],
        ]);
    }



    // 插入 Logo
    private function insertLogo($worksheet)
    {
        $logo = new Drawing();
        $logo->setName('Logo');
        $logo->setDescription('Logo');
        $logo->setPath('logo.png');
        $logo->setWidth(45);
        $logo->setHeight(45);
        $logo->setCoordinates('A1');
        $logo->setOffsetY(18);
        $logo->setOffsetX(50);
        $logo->setWorksheet($worksheet);
    }

    // 创建标题
    private function createTitle($worksheet, $workName)
    {
        $worksheet->mergeCells('A2:F2');
        $worksheet->setCellValue('A2','XX工厂'.$workName);
        $worksheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 21,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    // 设置设备数量和盗食数量
    private function setDeviceAndTheftQuantity($worksheet)
    {
        $worksheet->setCellValue('G2', '设备数量:');
        $worksheet->setCellValue('H2', '20')->getStyle('H2:H2')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);
        $worksheet->setCellValue('G3', '盗食数量:');
        $worksheet->setCellValue('H3', '4')->getStyle('H3:H3')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);
    }

    // 设置表头
    private function setTableHeader($worksheet)
    {
        $worksheet->mergeCells('A4:A5');
        $worksheet->mergeCells('B4:B5');
        $worksheet->mergeCells('C4:C5');
        $worksheet->mergeCells('G4:G5');
        $worksheet->mergeCells('H4:H5');
        $worksheet->setCellValue('A4', '区域 Area')
            ->setCellValue('B4', "监测站编号\nStation No.")
            ->setCellValue('C4', '检查位置 Location')
            ->setCellValue('D4', '检查结果 Findings')
            ->setCellValue('D5', '盗食50%以下')
            ->setCellValue('E5', '盗食50%以上')
            ->setCellValue('F5', '无盗食')
            ->setCellValue('G4', '备注 Remarks')
            ->setCellValue('H4', '处理情况 Action Taken');
        // 将单元格样式设置为自动换行
        $style = $worksheet->getStyle('B4');
        $style->getAlignment()->setWrapText(true);
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => array('rgb' => 'ffffff'),
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '305496',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $worksheet->getStyle('A4:H5')->applyFromArray($headerStyle);
        $range = 'D4:F4';
        $worksheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->mergeCells($range);
    }


    // 设置捕获蟑螂数量
    private function setCockroachQuantity($worksheet)
    {
        for ($i = 6; $i <= 25; $i++) {
            $worksheet->setCellValue("F{$i}", '');
            $worksheet->getStyle("B{$i}:F{$i}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        }
    }

    // 设置处理情况表头
    private function setActionTakenHeader($worksheet)
    {
        $worksheet->setCellValue('A25', '处理情况Action taken：')
            ->getStyle('A25:H27')->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);

        $worksheet->setCellValue('A26', '1-设备更换');
        $worksheet->setCellValue('B26', '2-设备异常');
        $worksheet->setCellValue('C26', '3-更换诱饵');
        $worksheet->setCellValue('D26', '4-清洁设备');
        $worksheet->setCellValue('E26', '5-更新标签');
        $worksheet->setCellValue('A27', '1-replace equipment ');
        $worksheet->setCellValue('B27', '2- Device abnormality ');
        $worksheet->setCellValue('C27', '3-replace by new rodenticide');
        $worksheet->setCellValue('D27', '4-Cleaning equipment');
        $worksheet->setCellValue('E27', '5-Update labels');




        //隐藏边框
        $worksheet->getStyle('A26:H28')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    'color' => [
                        'rgb' => 'FFFFFF' // 设置边框颜色为白色
                    ]
                ],
            ],
        ]);

        $worksheet->getStyle('A29:H32')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    'color' => [
                        'rgb' => 'FFFFFF' // 设置边框颜色为白色
                    ]
                ],
            ],
        ]);
    }

    // 设置复核人签名
    private function setReviewerSignature($worksheet)
    {

        $worksheet->mergeCells('A29:H29');
        $rtnText = new RichText();

        // 创建普通文本运行对象，并设置普通文本
        $textRun = $rtnText->createTextRun('技术员姓名technician：');
        $textRun->getFont()->setUnderline(false);

        // 创建签名运行对象，并设置文本和下划线
        $signatureRun = $rtnText->createTextRun('John Doe');
        $signatureRun->getFont()->setUnderline(true);

        // 创建普通文本运行对象，并设置普通文本
        $textRun = $rtnText->createTextRun('                                ');
        $textRun->getFont()->setUnderline(false);

        // 创建普通文本运行对象，并设置普通文本
        $textRun = $rtnText->createTextRun('日期date：');
        $textRun->getFont()->setUnderline(false);

        // 创建签名运行对象，并设置文本和下划线
        $signatureRun = $rtnText->createTextRun('2023.4.14');
        $signatureRun->getFont()->setUnderline(true);

        $worksheet->setCellValue('A29', $rtnText)
            ->getStyle('A29:H29')->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);

        $rtnTextCheck = new RichText();
        // 创建普通文本运行对象，并设置普通文本
        $textRunCheck = $rtnTextCheck->createTextRun('复核人签名checked by：');
        $textRunCheck->getFont()->setUnderline(false);

        // 创建签名运行对象，并设置文本和下划线
        $signatureRun = $rtnTextCheck->createTextRun('John Doe');
        $signatureRun->getFont()->setUnderline(true);


        $worksheet->mergeCells('A31:H31');
        $worksheet->setCellValue('A31', $rtnTextCheck)
            ->getStyle('A31:H31')->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
    }

    // 设置列宽
    private function setColumnWidth($worksheet)
    {
        $worksheet->getColumnDimension('A')->setWidth(15);
        $worksheet->getColumnDimension('B')->setWidth(20);
        $worksheet->getColumnDimension('C')->setWidth(20);//->setAutoSize(20);
        $worksheet->getColumnDimension('D')->setWidth(20);
        $worksheet->getColumnDimension('E')->setWidth(20);
        $worksheet->getColumnDimension('F')->setWidth(20);
        $worksheet->getColumnDimension('G')->setWidth(20);
        $worksheet->getColumnDimension('H')->setWidth(20);
        $worksheet->getDefaultRowDimension()->setRowHeight(20);
    }

    // 设置单元格边框
    private function setCellBorders($worksheet)
    {
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $worksheet->getStyle('A4:H31')->applyFromArray($styleArray);

        //隐藏边框
        $worksheet->getStyle('A1:H3')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    'color' => [
                        'rgb' => 'FFFFFF' // 设置边框颜色为白色
                    ]
                ],
            ],
        ]);
    }
}
