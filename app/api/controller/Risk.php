<?php

namespace app\api\controller;

use app\BaseController;
use app\technician\model\Risks;
use app\common\model\JobOrder;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\App;
use think\facade\Request;

class Risk extends BaseController
{


    private $serviceRisksModel = '';
    private $jobOrderModel = '';

    public function __construct(App $app)
    {
        $this->serviceRisksModel = new Risks;
        $this->jobOrderModel = new JobOrder;
        parent::__construct($app);
    }

    public function getJobids(string $customer_id, array $daterange): array
    {
        return $this->jobOrderModel->field('GROUP_CONCAT(JobID) as job_ids')->whereTime('JobDate', 'between', [$daterange[0], $daterange[1]])->whereIn('CustomerID', $customer_id)->where('Status',3)->findOrEmpty()->toArray();
    }


    /**
     * 查询getCustSignInfo详情
     * */
    public function getRiskInfoByIds($job_ids)
    {
        return $this->serviceRisksModel::alias('s')->field('j.CustomerID,j.CustomerName,s.*')->join('joborder j','j.JobID = s.job_id')->whereIn('job_id', $job_ids['job_ids'])->select()->toArray();
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


    public function index(Request $request)
    {
        try {
            $customer_id = $_GET['customerid']??'';
            $daterangeStr = $_GET['daterange']??[];
            if(empty($customer_id) || empty($daterangeStr)){
                return error(-1,'参数错误');
            }
            $daterange = json_decode($daterangeStr, true);
            $jobIds = [];
            $jobIds = $this->getJobids($customer_id,$daterange);
            $ret = $this->getRiskInfoByIds($jobIds);
//            dd($this->jobIds);
            if(empty($jobIds) || empty($ret)){
                return error(-1,'暂无风险记录');
            }
            $filename = $customer_id.'_'.date('Y-m').'.xlsx';
            $date = date('Y-m-d') . '/';
            $filePath = "risks/" . $date;
            $directory = app()->getRootPath() . '/public/' . $filePath;
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            $orginFile = $directory . $filename;
            $res = $this->writeDataToExcel($ret, $orginFile);
            if ($res){
                $domain = Request::domain();
                return success(0, 'ok', $domain . '/' . $filePath.$filename);
            }else{
                return error(-1,'error');
            }
        }catch (\Exception $e){
            return error(-1,$e->getMessage());
        }



    }

    public function writeDataToExcel($data, $orginFile)
    {
        // Create a new Excel spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title style
        $titleStyle = $this->getTitleStyle();

        // Set headers
        $sheet->setCellValue('A1', '编号');
        $sheet->setCellValue('B1', '门店');
        $sheet->setCellValue('C1', '创建时间');
        $sheet->setCellValue('D1', '跟进次数');
        $sheet->setCellValue('E1', '靶标');
        $sheet->setCellValue('F1', '风险类别');
        $sheet->setCellValue('G1', '风险等级');
        $sheet->setCellValue('H1', '风险标签');
        $sheet->setCellValue('I1', '风险描述');
        $sheet->setCellValue('J1', '整改建议');
        $sheet->setCellValue('K1', '采取措施');
        $sheet->setCellValue('L1', '跟进日期');
        $sheet->setCellValue('M1', '现场照片');
        $sheet->setCellValue('N1', '状态');

        // Set title style for headers
        $sheet->getStyle('A1:N1')->applyFromArray($titleStyle);

        // Write data to the spreadsheet
        $row = 2; // Start writing from the second row
        $previousCustomerID = ''; // Track CustomerID
        $previousTitleCount = 0; // Track previous title count
        foreach ($data as $item) {
            if ($item['CustomerID'] !== $previousCustomerID) {
                // Merge cells and display only one store name
                $mergeRange = 'B' . ($row + $previousTitleCount) . ':B' . ($row + $previousTitleCount + count($this->type) - 1);
                $sheet->mergeCells($mergeRange);
                $sheet->setCellValue('B' . ($row + $previousTitleCount), $item['CustomerName']);
                $sheet->getStyle($mergeRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . ($row + $previousTitleCount))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER); // Center align vertically
                $previousCustomerID = $item['CustomerID'];
                $previousTitleCount = count($this->type);
            } else {
                $previousTitleCount += count($this->type);
            }
            // Set other cell values
            $sheet->setCellValue('A' . $row, $item['id']);
            $sheet->setCellValue('C' . $row, $item['creat_time']);
            $sheet->setCellValue('D' . $row, $item['follow_times']);
            $sheet->setCellValue('E' . $row, $item['risk_targets']);
            $sheet->setCellValue('F' . $row, $item['risk_types']);
            $sheet->setCellValue('G' . $row, $item['risk_rank']);
            $sheet->setCellValue('H' . $row, $item['risk_label']);
            $sheet->setCellValue('I' . $row, $item['risk_description']);
            $sheet->setCellValue('J' . $row, $item['risk_proposal']);
            $sheet->setCellValue('K' . $row, $item['take_steps']);
            $sheet->setCellValue('L' . $row, $item['update_time']);
            $statusText = '';
            switch ($item['status']) {
                case 0:
                    $statusText = '未解决';
                    break;
                case 1:
                    $statusText = '已解决';
                    break;
                case 2:
                    $statusText = '跟进中';
                    break;
                default:
                    $statusText = '未知状态';
                    break;
            }
            // 将状态文本写入单元格
            $sheet->setCellValue('N' . $row, $statusText);

            // Set wrap text property
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('I' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('J' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('K' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('M' . $row)->getAlignment()->setWrapText(true);

            if (isset($item['site_photos']) && $item['site_photos'] != '') {
                $imgItems = explode(',', $item['site_photos']);
                $realPath = app()->getRootPath() . 'public' . $imgItems[0]; // Get the first image only
                if (file_exists($realPath)) {
                    // Create a new drawing object
                    $drawing = new Drawing();
                    $drawing->setName('Image');
                    $drawing->setDescription('Image');
                    $drawing->setPath($realPath);
                    $drawing->setCoordinates('M' . $row);
                    $drawing->setOffsetX(0);
                    $drawing->setOffsetY(50);
                    $maxWidth = $sheet->getColumnDimension('M')->getWidth() - 50; // Adjust the width as needed
                    $maxHeight = 60; // Set the maximum height in pixels
                    $drawing->setResizeProportional(true);
                    $drawing->setWidth($maxWidth);
                    $drawing->setHeight($maxHeight);
                    $drawing->setWorksheet($sheet);
                    $drawing->setOffsetY(($sheet->getRowDimension($row)->getRowIndex() - $drawing->getHeight()) / 2);
                }
            }

            $row++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(20);
        $sheet->getColumnDimension('L')->setWidth(20);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(20);

        // Save the Excel spreadsheet
        $writer = new Xlsx($spreadsheet);

        // dd($orginFile);
        $writer->save($orginFile);
        if (file_exists($orginFile)) {
            return  1;
        } else {
            return  0;
        }
    }


    public function getTitleStyle()
    {
        return [
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
    }

}
