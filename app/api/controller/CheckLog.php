<?php

namespace app\api\controller;

use app\BaseController;
use app\technician\model\AutographV2;
use app\technician\model\JobOrder;
use app\technician\model\ServiceEquipments;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\App;
use think\facade\Request;

class CheckLog extends BaseController
{

    /**
     * 工作表worksheet对应的标题名称
     * */
    private $workList = [
        ['鼠饵站', '鼠类防制检查记录表'],
        ['粘鼠板OR捕鼠夹', '鼠类防制检查记录表'],
        ['灭蝇灯', '飞虫防制检查记录表(灭蝇灯)'],
        ['蟑螂屋', '蟑螂防制检查记录表'],
    ];
    /**
     * 每个工作表worksheet key对应的显示内容
     * */
    private $type = [
        '鼠饵站' => ['盗食50%以下', '盗食50%以上', '无盗食'],
        '粘鼠板OR捕鼠夹' => ['发现老鼠  Rat caught', '没发现老鼠  Rat activity unidentified'],
        '灭蝇灯' => ['苍蝇', '蚊子', '绿化飞虫', '仓储害虫', '卫生性飞虫'],
        '蟑螂屋' => ['捕获蟑螂数量 Number of captured'],
    ];

    private $equList = [];
    private $job_id = '';
    private $jobData = [];

    private $custSign = [];

    protected $rat = [
        '鼠饵站' => ['SE'],
        '灭蝇灯' => ['MY'],
        '蟑螂屋' => ['ZJ'],
        '粘鼠板OR捕鼠夹' => ['SL', 'SJ', 'SC', 'SB'],
    ];


    public function __construct(App $app)
    {
        $this->serviceEquipmentsModel = new ServiceEquipments;
        $this->jobOrderModel = new JobOrder;
        $this->autographV2Model = new AutographV2;
        parent::__construct($app);
    }

    public function getEquData($job_id, $op)
    {
        return $this->serviceEquipmentsModel->whereIn('equipment_number', $op)->where('job_id', '=', $job_id)->order('equipment_number','DESC')->select()->toArray();
    }

    /**
     * 查询订单详情
     * */
    public function getOrderInfo($job_id)
    {
        return $this->jobOrderModel->alias('j')->field('j.JobDate,JobID,CustomerName,u.StaffName as staff1,uo.StaffName as staff2,ut.StaffName as staff3')->join('staff u', 'j.Staff01=u.StaffID')->join('staff uo', 'j.Staff02=uo.StaffID', 'left')->join('staff ut', 'j.Staff03=ut.StaffID', 'left')->where('JobID', $job_id)->findOrEmpty()->toArray();
    }

    /**
     * 查询getCustSignInfo详情
     * */
    public function getCustSignInfo($job_id)
    {
        $where = [
            'job_id' => $job_id
        ];
        return AutographV2::where($where)->findOrEmpty()->toArray();
    }

    private function createCustSign($worksheet, $data = [], $col = '')
    {
//        dd(app()->getRootPath().'public'.$data['customer_signature_url']);
        $logo = new Drawing();
        $logo->setName('cust_sign');
        $logo->setDescription('cust_sign');
        $logo->setPath(app()->getRootPath() . 'public' . $data['customer_signature_url']);
        $logo->setWidth(25);
        $logo->setHeight(25);
        $logo->setCoordinates($col);
//        $logo->setOffsetY(28);
//         $logo->setOffsetX(300); // Uncomment this line if needed
        $logo->setWorksheet($worksheet);
    }


    public function index($job_id = '')
    {
        if (empty($job_id)) {
            return error(-1, '参数错误,请检查！');
        }
        $this->job_id = $job_id;
        $this->jobData = $this->getOrderInfo($job_id);
        if (empty($this->jobData)) {
            return error(-1, '未找到相关设备记录表请稍后再试！');
        }
        $this->custSign = $this->getCustSignInfo($job_id);

        $spreadsheet = new Spreadsheet();
        foreach ($this->workList as $index => $workName) {
//            $worksheet = $workName[0] ?? '';
            $workTitle = $workName[0] ?? '';
            // 创建工作表
            $worksheet = $spreadsheet->createSheet($index);
            $worksheet->setTitle($workTitle);
            // 清除未使用的样式，并设置为空白
//            $this->clearUnusedStyles($worksheet);
            // 添加数据和设置样式
            $this->setWorksheetData($worksheet, $workName[1]);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = $this->jobData['JobID'] . '.xlsx';

        $date = $this->jobData['JobDate'] . '/';
        $filePath = "excel/" . $date;
        $directory = app()->getRootPath() . '/public/' . $filePath;
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $orginFile = $directory . $fileName;
        // dd($orginFile);
        $writer->save($orginFile);
        if (file_exists($orginFile)) {
            $domain = Request::domain();
            return success(0, 'ok', $domain . '/' . $filePath.$fileName);
        } else {
            return error(-1, '生成错误，请稍后再试！');
        }

    }

    private function setWorksheetData($worksheet, $workName)
    {
        // 插入 Logo
        $this->insertLogo($worksheet);
        // 创建标题
        $this->createTitle($worksheet, $workName);
        // 设置表头
        $this->setTableHeader($worksheet);
        // 动态的设置数据
        $this->setTableData($worksheet);// 设置列宽
        $this->setColumnWidth($worksheet);
    }

// 清除未使用的样式，并设置为空白
    private function clearUnusedStyles($worksheet)
    {
        /** @var TYPE_NAME $highestColumn */
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
        $logo->setWidth(35);
        $logo->setHeight(35);
        $logo->setCoordinates('A1');
        $logo->setOffsetY(40);
//        $logo->setOffsetX(50);
        $logo->setWorksheet($worksheet);
    }

    // 创建标题
    private function createTitle($worksheet, $workName)
    {
        $title = $worksheet->getTitle();
        switch ($title) {
            case '鼠饵站':
                $worksheet->mergeCells('A2:G2');
                break;
            case '粘鼠板OR捕鼠夹':
                $worksheet->mergeCells('A2:F2');
                break;
            case '灭蝇灯':
                $worksheet->mergeCells('A2:I2');
                break;
            case '蟑螂屋':
                $worksheet->mergeCells('A2:E2');
                break;
            default:
                break;
        }
        $worksheet->setCellValue('A2', $this->jobData['CustomerName'] . $workName);
        $worksheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 20,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function colAutoWarp($worksheet, $id)
    {
        // 获取单元格样式对象并设置自动换行
        $style = $worksheet->getStyle($id);
        $alignment = $style->getAlignment();
        $alignment->setWrapText(true);
    }

    private function setTableHeader($worksheet)
    {
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'ffffff'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '305496'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];
        // 获取当前工作表的标题
        $title = $worksheet->getTitle();
        // 检查当前工作表是否为鼠饵站
        $worksheet->setCellValue('A4', '区域 Area');
        $worksheet->setCellValue('B4', '监测站编号 Station No.');
        switch ($title) {
            case '鼠饵站':
                $worksheet->mergeCells('C4:E4');
                $worksheet->mergeCells('B4:B5');

                $worksheet->mergeCells('A4:A5');
                $worksheet->mergeCells('B4:B5');

                $worksheet->setCellValue('C4', '检查结果 Findings');
                $worksheet->setCellValue('F4', '处理情况 Action Taken');
                $this->colAutoWarp($worksheet, 'C4');
                $this->colAutoWarp($worksheet, 'F4');

                $worksheet->setCellValue('G4', '备注 Remarks');
                $worksheet->mergeCells('F4:F5');
                $worksheet->mergeCells('G4:G5');
                $this->setColValue($worksheet, $title);
                $worksheet->getStyle('A4:G5')->applyFromArray($headerStyle);
                break;
            case '粘鼠板OR捕鼠夹':
                $worksheet->mergeCells('A4:A5');
                $worksheet->mergeCells('B4:B5');
                $worksheet->mergeCells('C4:D4');

                $worksheet->mergeCells('E4:E5');
                $worksheet->mergeCells('F4:F5');
                $worksheet->setCellValue('C4', '检查结果 Findings');
                $this->colAutoWarp($worksheet, 'C4');

                $worksheet->setCellValue('E4', '处理情况 Action Taken');
                $worksheet->setCellValue('F4', '备注 Remarks');
                $this->colAutoWarp($worksheet, 'F4');

                $this->setColValue($worksheet, $title);
                $worksheet->getStyle('A4:F5')->applyFromArray($headerStyle);
                break;
            case '灭蝇灯':
                $worksheet->mergeCells('A4:A5');
                $worksheet->mergeCells('B4:B5');
                $worksheet->mergeCells('C4:G4');
                $worksheet->mergeCells('H4:H5');
                $worksheet->mergeCells('I4:I5');
                $worksheet->setCellValue('C4', '检查结果 Findings');
                // 获取单元格样式对象并设置自动换行
                $this->colAutoWarp($worksheet, 'C4');
                $worksheet->setCellValue('H4', '备注 Remarks');
                $worksheet->setCellValue('I4', '处理情况 Action Taken');
                // 获取单元格样式对象并设置自动换行
                $this->colAutoWarp($worksheet, 'I4');

                $this->setColValue($worksheet, $title);
                $worksheet->getStyle('A4:I5')->applyFromArray($headerStyle);
                break;
            case '蟑螂屋':
                $worksheet->mergeCells('A4:A5');
                $worksheet->mergeCells('B4:B5');
                $worksheet->mergeCells('D4:D5');
                $worksheet->mergeCells('E4:E5');
                $worksheet->setCellValue('A4', '区域 Area');
                $worksheet->setCellValue('B4', '监测站编号 Station No.');
                $this->colAutoWarp($worksheet, 'B4');

                $worksheet->mergeCells('C4:C4');
                $worksheet->setCellValue('C4', '检查结果 Findings');
                $this->colAutoWarp($worksheet, 'C4');

                $worksheet->setCellValue('D4', '备注 Remarks');
                $worksheet->setCellValue('E4', '处理情况 Action Taken');
                $this->colAutoWarp($worksheet, 'E4');

                $this->setColValue($worksheet, $title);
                $worksheet->getStyle('A4:E5')->applyFromArray($headerStyle);
                break;
            default:
        }

        // 将单元格样式设置为自动换行
        $style = $worksheet->getStyle('B4');
        $style->getAlignment()->setWrapText(true);
        // 设置行高以适应内容
        $worksheet->getRowDimension(4)->setRowHeight(-1);
        $worksheet->getRowDimension(5)->setRowHeight(-1);
    }

    private function setColValue($worksheet, $title)
    {
        if (isset($this->type[$title])) {
            // 获取当前标题对应的值数组
            $values = $this->type[$title];
            // 设置检查结果单元格的值
            foreach ($values as $index => $value) {
                $worksheet->setCellValueByColumnAndRow(3 + $index, 5, $value);
            }
        }
    }

    private function setTableData($worksheet)
    {
        $title = $worksheet->getTitle();
        $row = 6; // 从第6行开始
        switch ($title) {
            case '鼠饵站':
                $worksheet->getColumnDimension('C')->setWidth(30);
                $worksheet->getColumnDimension('D')->setWidth(30);
                $worksheet->getColumnDimension('F')->setWidth(30);
                $worksheet->getColumnDimension('G')->setWidth(20);
                // 处理鼠饵站的逻辑
                $data = $this->getEquData($this->job_id, $this->rat[$title]);

                foreach ($data as $item) {
                    $worksheet->setCellValue("A{$row}", $item['equipment_area']);
                    $worksheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $worksheet->setCellValue("B{$row}", $item['equipment_number'] . '-' . $item['number']);
                    $worksheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $array = json_decode($item['check_datas'], true);
                    $columnIndex = 3; // 从 C 开始的
                    if(!empty($array)) {
                        foreach ($array as $v) {
                            if ($v['value'] == $this->type["鼠饵站"][0]) {
                                $worksheet->setCellValueByColumnAndRow($columnIndex, $row, "✔");
                                $worksheet->getStyleByColumnAndRow($columnIndex, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            }else if ($v['value'] == $this->type["鼠饵站"][1]) {
                                $worksheet->setCellValueByColumnAndRow($columnIndex + 1, $row, "✔");
                                $worksheet->getStyleByColumnAndRow($columnIndex + 1, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            }else if ($v['value'] == $this->type["鼠饵站"][2]) {
                                $worksheet->setCellValueByColumnAndRow($columnIndex + 2, $row, "✔");
                                $worksheet->getStyleByColumnAndRow($columnIndex + 2, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            }else{
                                $worksheet->setCellValueByColumnAndRow($columnIndex + 2, $row, "✔");
                                $worksheet->getStyleByColumnAndRow($columnIndex + 2, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            }
                            $columnIndex++;
                        }
                    }else{
                        $worksheet->setCellValueByColumnAndRow($columnIndex + 2, $row, "✔");
                        $worksheet->getStyleByColumnAndRow($columnIndex + 2, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }

                    $param_col = "F{$row}";
                    $moreInfo = $item['more_info'] === 'null' ? '' : $item['more_info'];
                    $worksheet->setCellValue($param_col, $moreInfo);
                    $worksheet->getStyle($param_col)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle($param_col)->getAlignment()->setWrapText(true);


                    $worksheet->setCellValue("G{$row}", $item['check_handle'] === 'null' ? '' : $item['check_handle']);
                    $worksheet->getStyle("G{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle("G{$row}")->getAlignment()->setWrapText(true);
                    $row++;
                }
                break;
            case '粘鼠板OR捕鼠夹':
                $worksheet->getColumnDimension('C')->setWidth(30);
                $worksheet->getColumnDimension('D')->setWidth(50);
                $worksheet->getColumnDimension('F')->setWidth(30);
                // 处理粘鼠板OR捕鼠夹的逻辑
                $data = $this->getEquData($this->job_id, $this->rat[$title]);

                foreach ($data as $item) {
                    $worksheet->setCellValue("A{$row}", $item['equipment_area']);
                    $worksheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $worksheet->setCellValue("B{$row}", $item['equipment_number'] . '-' . $item['number']);
                    $worksheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $array = json_decode($item['check_datas'], true);
                    $columnIndex = 3; // 从 D 开始的
                    if(!empty($array)) {
                        foreach ($array as $v) {
                            $worksheet->setCellValueByColumnAndRow($columnIndex, $row, $v['value']);
                            $worksheet->getStyleByColumnAndRow($columnIndex, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $columnIndex++;
                            if ($v['value'] == 0) {
                                $worksheet->setCellValueByColumnAndRow($columnIndex, $row, "✔");
                                $worksheet->getStyleByColumnAndRow($columnIndex, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                                $columnIndex++;
                            }
                        }
                    }else{
                        $worksheet->setCellValueByColumnAndRow($columnIndex+1, $row, "✔");
                        $worksheet->getStyleByColumnAndRow($columnIndex+1, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }
                    $worksheet->setCellValue("E{$row}", $item['more_info'] === 'null' ? '' : $item['more_info']);
                    $worksheet->getStyle("E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle("E{$row}")->getAlignment()->setWrapText(true);
                    $worksheet->setCellValue("F{$row}", $item['check_handle'] === 'null' ? '' : $item['check_handle']);
                    $worksheet->getStyle("F{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle("F{$row}")->getAlignment()->setWrapText(true);
                    $row++;
                }
                break;
            case '灭蝇灯':
                $worksheet->getColumnDimension('C')->setWidth(30);
                $worksheet->getColumnDimension('D')->setWidth(30);
                $worksheet->getColumnDimension('F')->setWidth(30);
                $worksheet->getColumnDimension('G')->setWidth(20);
                $worksheet->getColumnDimension('H')->setWidth(20);
                $worksheet->getColumnDimension('I')->setWidth(20);
                $data = $this->getEquData($this->job_id, $this->rat[$title]);
                foreach ($data as $item) {
                    $worksheet->setCellValue("A{$row}", $item['equipment_area']);
                    $worksheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $worksheet->setCellValue("B{$row}", $item['equipment_number'] . '-' . $item['number']);
                    $worksheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $array = json_decode($item['check_datas'], true);

                    $columnIndex = 3; // 从 C 开始的
                    if(!empty($array)) {
                        foreach ($array as $v) {
                            if (empty($v['value'])) {
                                // 处理值为空的情况
                                $worksheet->setCellValueByColumnAndRow($columnIndex, $row, '0');
                            } else {
                                // 处理值不为空的情况
                                $worksheet->setCellValueByColumnAndRow($columnIndex, $row, $v['value']);
                            }
                            $worksheet->getStyleByColumnAndRow($columnIndex, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $columnIndex++;
                        }
                    }else{
                        for ($i = 0;$i <= 4;$i++){
                            $worksheet->setCellValueByColumnAndRow($columnIndex+$i, $row, '0');
                            $worksheet->getStyleByColumnAndRow($columnIndex+$i, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        }

                    }

                    $worksheet->setCellValue("H{$row}", $item['more_info'] === 'null' ? '' : $item['more_info']);
                    $worksheet->getStyle("H{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle("H{$row}")->getAlignment()->setWrapText(true);

                    $worksheet->setCellValue("I{$row}", $item['check_handle'] === 'null' ? '' : $item['check_handle']);
                    $worksheet->getStyle("I{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle("I{$row}")->getAlignment()->setWrapText(true);

                    $row++;
                }
                break;
            case '蟑螂屋':
                $worksheet->getColumnDimension('C')->setWidth(40);
                $worksheet->getColumnDimension('D')->setWidth(30);
                // 处理蟑螂屋的逻辑
                $data = $this->getEquData($this->job_id, $this->rat[$title]);
                foreach ($data as $item) {
                    $worksheet->setCellValue("A{$row}", $item['equipment_area']);
                    $worksheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $worksheet->setCellValue("B{$row}", $item['equipment_number'] . '-' . $item['number']);
                    $worksheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $array = json_decode($item['check_datas'], true);

                    $columnIndex = 3; // 从 D 开始的
                    if(!empty($array)) {
                        foreach ($array as $v) {
                            if (empty($v['value'])) {
                                // 处理值为空的情况
                                $worksheet->setCellValueByColumnAndRow($columnIndex, $row, '0');
                            } else {
                                // 处理值不为空的情况
                                $worksheet->setCellValueByColumnAndRow($columnIndex, $row, $v['value']);
                            }
                            $worksheet->getStyleByColumnAndRow($columnIndex, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                            $columnIndex++;
                        }
                    }else{
                        $worksheet->setCellValueByColumnAndRow($columnIndex, $row, '0');
                        $worksheet->getStyleByColumnAndRow($columnIndex, $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }

                    $worksheet->setCellValue("D{$row}", $item['more_info'] === 'null' ? '' : $item['more_info']);
                    $worksheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);

                    $worksheet->setCellValue("E{$row}", $item['check_handle'] === 'null' ? '' : $item['check_handle']);
                    $worksheet->getStyle("E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle("E{$row}")->getAlignment()->setWrapText(true);

                    $row++;
                }
                break;
            default:
                break;
        }
        if ($row < 25) {
            $row = 25;
//            $this->setCockroachQuantity($worksheet);
        }
        $this->setActionTakenHeader($worksheet, $row);
        $this->setReviewerSignature($worksheet, $row);
        $this->setCellBorders($worksheet, $row);
    }

    private function setBorderThin($worksheet, $num, $col)
    {
        $worksheet->setCellValue('A' . $num, '处理情况Action taken：')
            ->getStyle('A' . $num . (':' . $col . ($num + 2)))->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        $cell = 'A' . $num.":". $col . ($num);
        $worksheet->mergeCells($cell);
    }

    // 设置处理情况表头
    private function setActionTakenHeader($worksheet, $num = 25)
    {
        $title = $worksheet->getTitle();
        switch ($title) {
            case '鼠饵站':
                $this->setBorderThin($worksheet, $num, 'G');
                break;
            case '粘鼠板OR捕鼠夹':
                $this->setBorderThin($worksheet, $num, 'F');
                break;
            case '灭蝇灯':
                $this->setBorderThin($worksheet, $num, 'I');
                break;
            case '蟑螂屋':
                $this->setBorderThin($worksheet, $num, 'E');
                break;
            default:
                break;
        }
        $worksheet->setCellValue('A' . ($num + 1), '1-设备更换');
        $worksheet->setCellValue('B' . ($num + 1), '2-设备异常');
        $worksheet->setCellValue('C' . ($num + 1), '3-更换诱饵');
        $worksheet->setCellValue('D' . ($num + 1), '4-清洁设备');
        $worksheet->setCellValue('E' . ($num + 1), '5-更新标签');
        $worksheet->setCellValue('A' . ($num + 2), '1-replace equipment ');
        $worksheet->setCellValue('B' . ($num + 2), '2- Device abnormality ');
        $worksheet->setCellValue('C' . ($num + 2), '3-replace by new rodenticide');
        $worksheet->setCellValue('D' . ($num + 2), '4-Cleaning equipment');
        $worksheet->setCellValue('E' . ($num + 2), '5-Update labels');

        $title = $worksheet->getTitle();


        //隐藏边框
        $style_col_start = 'A' . ($num + 1) . (':H' . ($num + 3));
        $style_col2_end = 'A' . ($num + 4) . (':H' . ($num + 6));
        if ($title == '灭蝇灯') {
            $style_col_start = 'A' . ($num + 1) . (':I' . ($num + 3));
            $style_col2_end = 'A' . ($num + 4) . (':I' . ($num + 6));
        }

        $worksheet->getStyle($style_col_start)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                    'color' => [
                        'rgb' => 'FFFFFF' // 设置边框颜色为白色
                    ]
                ],
            ],
        ]);

        $worksheet->getStyle($style_col2_end)->applyFromArray([
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

    /**
     * 动态设置复核人签名样式
     */
    private function autoSetCellValue($worksheet, $num, $rtnText, $col, $rtnFlag = true)
    {
        $rtnNum = $rtnFlag ? $num + 4 : $num + 6;
        $worksheet->setCellValue('A' . $rtnNum, $rtnText)
            ->getStyle('A' . $rtnNum . ':' . $col . $rtnNum)->applyFromArray([
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


    /**
     * 根据worksheet的名称设置复核人签名
     * */
    private function setReviewerSignature($worksheet, $num = 25)
    {
        // 合并单元格
        $worksheet->mergeCells('A' . ($num + 4) . (':H' . ($num + 4)));

        $title = $worksheet->getTitle();
        if ($title == '灭蝇灯') {
            $worksheet->mergeCells('A' . ($num + 4) . (':I' . ($num + 4)));
        }

        // 创建富文本对象
        $rtnText = new RichText();

        $jobData = $this->jobData;

        $staff1 = $jobData['staff1'] ?? '';
        $staff2 = $jobData['staff2'] ?? '';
        $staff3 = $jobData['staff3'] ?? '';

        $staffs = implode('、', array_filter([$staff1, $staff2, $staff3]));

        // 创建普通文本运行对象，并设置普通文本
        $textRun = $rtnText->createTextRun('技术员姓名technician：');
        $textRun->getFont()->setUnderline(false);

        // 创建签名运行对象，并设置文本和下划线
        $signatureRun = $rtnText->createTextRun("             " . $staffs . "             ");
        $signatureRun->getFont()->setUnderline(true);

        // 创建普通文本运行对象，并设置普通文本
        $textRun = $rtnText->createTextRun('                                ');
        $textRun->getFont()->setUnderline(false);

        // 创建普通文本运行对象，并设置普通文本
        $textRun = $rtnText->createTextRun('日期date：');
        $textRun->getFont()->setUnderline(false);

        // 创建签名运行对象，并设置文本和下划线
        $signatureRun = $rtnText->createTextRun("" . $jobData['JobDate'] ?? "");
        $signatureRun->getFont()->setUnderline(true);

        // 创建复核人签名的富文本对象
        $rtnTextCheck = new RichText();
        // 创建普通文本运行对象，并设置普通文本
        $textRunCheck = $rtnTextCheck->createTextRun('复核人签名checked by：');
        $textRunCheck->getFont()->setUnderline(false);

        // 创建签名运行对象，并设置文本和下划线
        try {
            $this->createCustSign($worksheet, $this->custSign, 'C' . ($num + 6));
        } catch (\Exception $e) {
        }

        $title = $worksheet->getTitle();

        // 根据标题设置单元格样式和值
        switch ($title) {
            case '鼠饵站':
                $this->autoSetCellValue($worksheet,$num,$rtnText,'G',true);
                $this->autoSetCellValue($worksheet,$num,$rtnTextCheck,'G',false);
                break;
            case '粘鼠板OR捕鼠夹':
                $this->autoSetCellValue($worksheet,$num,$rtnText,'F',true);
                $this->autoSetCellValue($worksheet,$num,$rtnTextCheck,'F',false);
                break;
            case '灭蝇灯':
                $this->autoSetCellValue($worksheet,$num,$rtnText,'I',true);
                $this->autoSetCellValue($worksheet,$num,$rtnTextCheck,'I',false);
                break;
            case '蟑螂屋':
                $this->autoSetCellValue($worksheet,$num,$rtnText,'E',true);
                $this->autoSetCellValue($worksheet,$num,$rtnTextCheck,'E',false);
                break;
        }
    }


    // 设置列宽
    private function setColumnWidth($worksheet)
    {
        $worksheet->getColumnDimension('A')->setWidth(15);
        $worksheet->getColumnDimension('B')->setWidth(30);
//        $worksheet->getColumnDimension('C')->setWidth(30);//->setAutoSize(20);
//        $worksheet->getColumnDimension('D')->setWidth(30);
        $worksheet->getColumnDimension('E')->setWidth(30);
        $worksheet->getDefaultRowDimension()->setRowHeight(22);
    }

    private function setCellBorders($worksheet, $num = 25)
    {
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        // 设置边框颜色为白色
        $noneBorderStyle = [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
            'color' => [
                'rgb' => 'FFFFFF'
            ]
        ];

        $ranges = [
            '灭蝇灯' => 'A4:I',
            '鼠饵站' => 'A4:G',
            '粘鼠板OR捕鼠夹' => 'A4:F',
            '蟑螂屋' => 'A4:E'
        ];

        $title = $worksheet->getTitle();
        if (isset($ranges[$title])) {
            $range = $ranges[$title] . ($num + 6);
            $worksheet->getStyle($range)->applyFromArray($styleArray);
        }

        // 隐藏边框
        $worksheet->getStyle('A1:I3')->applyFromArray($noneBorderStyle);

        // 设置活动单元格为A4
        $worksheet->getParent()->setActiveSheetIndex($worksheet->getParent()->getIndex($worksheet));
        $worksheet->setSelectedCell('A4');
    }

}
