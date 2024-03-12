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

use app\common\model\EnumsModel;
use app\common\model\OfficeCityModel;
use think\App;
use think\facade\Request;

use PhpOffice\PhpSpreadsheet\IOFactory;

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

    public function getJobids(string $customer_id, array $daterange): array{
        return $this->jobOrderModel->field('GROUP_CONCAT(JobID) as job_ids')->whereTime('JobDate', 'between', [$daterange[0], $daterange[1]])->whereIn('CustomerID', $customer_id)->where('Status',3)->findOrEmpty()->toArray();
    }


    /**
     * 查询getCustSignInfo详情
     * */
    public function getRiskInfoByIds($job_ids){
        return $this->serviceRisksModel::alias('s')->field('j.CustomerID,j.CustomerName,s.*')->join('joborder j','j.JobID = s.job_id')->whereIn('job_id', $job_ids['job_ids'])->select()->toArray();
    }

    private function createCustSign($worksheet, $data = [], $col = ''){
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


    public function index(Request $request){
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

    public function writeDataToExcel($data, $orginFile){
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


    public function getTitleStyle(){
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
    public function list(){

        $search = Request::param('q', ''); // 获取搜索关键字
        // echo $search;exit;
        $where = array();
        if ($search != '') {
            $where[] = ['c.NameZH', 'like', '%' . $search . '%']; // 添加搜索关键字查询条件
        }

        $list = $this->serviceRisksModel->alias('m')
            ->leftJoin('joborder j','m.job_id=j.JobID')
            ->leftJoin('customercompany c','c.CustomerID=j.CustomerID')
            ->leftJoin('enums e','e.EnumID=j.City')
            ->where('c.CustomerType','=',248)
            ->where($where)
            ->field('m.id,m.job_id,m.job_type,m.risk_data,c.NameZH,c.CustomerID,j.JobDate,e.Text')
            //->paginate(); // 查询客户信息列表
            ->paginate()
            ->each(function($item, $key){
                if($item['risk_data']){
                    $item['risk_data']= json_decode($item['risk_data'],true);
                }

                return $item;
            });

        $sql = $this->serviceRisksModel->getLastSql();
        return success(0, 'success', $list,$sql); // 返回操作结果和数据

    }
    public function demo(){
        echo "demo";
    }
    // 导出
    public function export(){
        header('Content-Type: text/html;charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin');


        $name = Request::param('name', '');
        $date = Request::param('date', '');


        if(!$name){
            $data['code'] = 400;
            $data['msg'] = '请输入地区';
            echo json_encode($data,true);
            exit;
        }
        if(count($date)==0){
            $data['code'] = 400;
            $data['msg'] = '请选择日期';
            echo json_encode($data,true);
            exit;
        }
        if($name){
            // 获取办公室代号
            $EnumID = (new EnumsModel())->where('EnumType','=',8)->where('Text','=',$name)->value('EnumID');

            // 获取代号下面的城市
            $city_arr = (new OfficeCityModel)->where('Office','=',$EnumID)->field('City')->select()->toArray();
            // echo  (new OfficeCityModel)->getLastSql();exit;
            $ids = array_column($city_arr, 'City');
            $where[] = ['j.City','in',$ids];
        }

        if($date){

            $start_date = $date[0];
            $end_date = $date[1];

            $where[] = ['j.JobDate','between',[$start_date,$end_date]];
        }

        // risk_types 风险类别| risk_description 风险描述 | risk_proposal 整改建议 | take_steps 采取措施 undefined if(sex=1,"男","女")
        // $list = $this->serviceRisksModel->alias('m')
        //     ->leftJoin('joborder j','m.job_id=j.JobID')
        //     ->leftJoin('customercompany c','c.CustomerID=j.CustomerID')
        //     ->leftJoin('enums e','e.EnumID=j.City')
        //     ->where('c.CustomerType','=',248)
        //     ->where('j.ServiceType','=',2)
        //     ->where('j.FinishTime','>','00:00:00')
        //     ->where($where)
        //     ->field('m.id,m.job_id,m.job_type,m.risk_data,m.risk_types,m.risk_description,m.risk_proposal,m.take_steps,c.NameZH,c.CustomerID,j.JobDate,j.StartTime,j.FinishTime,e.Text')
        //     ->select()->toArray();

        // $list = $this->serviceRisksModel->alias('m')
        //     ->leftJoin('joborder j','m.job_id=j.JobID')
        //     ->leftJoin('customercompany c','c.CustomerID=j.CustomerID')
        //     ->leftJoin('enums e','e.EnumID=j.City')
        //     ->where('c.CustomerType','in','248,139')
        //     ->where('j.ServiceType','=',2)
        //     ->where('j.FinishTime','>','00:00:00')
        //     ->where($where)
        //     ->field('m.id,m.job_id,m.job_type,m.risk_data,m.risk_types,m.risk_description,m.risk_proposal,m.take_steps,c.NameZH,c.CustomerID,j.JobDate,j.StartTime,j.FinishTime,e.Text')
        //     ->select()->toArray();

        // $sql = $this->serviceRisksModel->getLastSql();


        $list = $this->jobOrderModel->alias('j')
            ->leftJoin('enums e','e.EnumID=j.City')
            ->leftJoin('customercompany c','c.CustomerID=j.CustomerID')
            ->leftJoin('lbs_service_risks m','m.job_id = j.JobID')
            ->where('c.CustomerType','in','248,249,139')
            ->where('j.ServiceType','=',2)
            ->where('j.FinishTime','>','00:00:00')
            ->where($where)
            ->field('j.JobID,j.JobDate,j.StartTime,j.FinishTime,e.Text,c.NameZH,c.CustomerID,m.id,m.job_id,m.job_type,m.risk_data,m.risk_types,m.risk_description,m.risk_proposal,m.take_steps')
            ->select()->toArray();
        $sql = $this->jobOrderModel->getLastSQL();


        if(!$list){
            $data['code'] = 400;
            $data['msg'] = '没有数据';
            echo json_encode($data,true);
            exit;
        }

        foreach ($list as $key=>$val){
            $check_data = json_decode($val['risk_data'],true);

            $list[$key]['s_1'] = isset($check_data) ? $check_data[0]['value'] : '0';
            $list[$key]['s_2'] = isset($check_data) ? $check_data[1]['value'] : '无';
            $list[$key]['z_1'] = isset($check_data) ? $check_data[2]['value'] : '0';
            $list[$key]['z_2'] = isset($check_data) ? $check_data[3]['value'] : '无';
            $list[$key]['f_1'] = isset($check_data) ? $check_data[4]['value'] : '0';
            $list[$key]['f_2'] = isset($check_data) ? $check_data[5]['value'] : '';

        }

        list($file, $file_url) = $this->dataToExcel_ProductReport($list);

        if (!file_exists($file)){
            exception('导出失败', -1);
        }

        //返回文件地址
        $domain = config('app.domain_url');

        $data['file_url'] = $domain.$file_url;
        $data['sql'] = $sql;
        return success(200, 'success', $data);
    }
    public function dataToExcel_ProductReport($list){

        $path = config('filesystem.disks')['export_RiskReport']['root'];

        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        $sheet->setCellValue('A1', '地区');
        $sheet->setCellValue('B1', '客户名称');
        $sheet->setCellValue('C1', '客户编号');
        $sheet->setCellValue('D1', '服务日期');

        $sheet->setCellValue('E1', '服务开始时间');
        $sheet->setCellValue('F1', '服务结束时间');

        $sheet->setCellValue('G1', '鼠类发现数量');
        $sheet->setCellValue('H1', '鼠迹');
        $sheet->setCellValue('I1', '蟑螂活体数量');
        $sheet->setCellValue('J1', '蟑螂痕迹');
        $sheet->setCellValue('K1', '飞虫数量');
        $sheet->setCellValue('L1', '飞虫类目');

        $sheet->setCellValue('M1', '风险类别');
        $sheet->setCellValue('N1', '风险描述');
        $sheet->setCellValue('O1', '整改建议');
        $sheet->setCellValue('P1', '采取措施');


        foreach ($list as $k=>$r){
            if(isset($r['s_2']) && $r['s_2']=='1'){
                $list[$k]['s_2'] = '有';
            }
            if(isset($r['s_2']) && $r['s_2']=='0'){
                $list[$k]['s_2'] = '无';
            }

            if(isset($r['z_2']) && $r['z_2']=='1'){
                $list[$k]['z_2'] = '有';
            }
            if(isset($r['z_2']) && $r['z_2']=='0'){
                $list[$k]['z_2'] = '有';
            }

            if($r['risk_description']=='undefined'){
                $list[$k]['risk_description'] = '';
            }
            if($r['risk_proposal']=='undefined'){
                $list[$k]['risk_proposal'] = '';
            }
            if($r['take_steps']=='undefined'){
                $list[$k]['take_steps'] = '';
            }
        }
        foreach ($list as $k=>$r){
            $sheet->setCellValue('A' . ($k+2), $r['Text']);
            $sheet->setCellValue('B' . ($k+2), $r['NameZH']);
            $sheet->setCellValue('C' . ($k+2), $r['CustomerID']);
            $sheet->setCellValue('D' . ($k+2), $r['JobDate']);

            $sheet->setCellValue('E' . ($k+2), $r['StartTime']);
            $sheet->setCellValue('F' . ($k+2), $r['FinishTime']);

            $sheet->setCellValue('G' . ($k+2), $r['s_1']);
            $sheet->setCellValue('H' . ($k+2), $r['s_2']);
            $sheet->setCellValue('I' . ($k+2), $r['z_1']);
            $sheet->setCellValue('J' . ($k+2), $r['z_2']);
            $sheet->setCellValue('K' . ($k+2), $r['f_1']);
            $sheet->setCellValue('L' . ($k+2), $r['f_2']);

            $sheet->setCellValue('M' . ($k+2), $r['risk_types']);
            $sheet->setCellValue('N' . ($k+2), $r['risk_description']);
            $sheet->setCellValue('O' . ($k+2), $r['risk_proposal']);
            $sheet->setCellValue('P' . ($k+2), $r['take_steps']);
        }



        $fileName = '';
        if (count($list) > 0) {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            /* 删除当天之前的文件 */
            $files = glob($path . 'Material_*.*');
            $todayDate = date("Y-m-d");
            foreach ($files as $file) {
                $fileDate = date("Y-m-d", filemtime($file));
                if ($fileDate < $todayDate) {
                    unlink($file);
                }
            }

            $fileName = 'Risk_' . $todayDate . '.xlsx';
            if (file_exists($path . $fileName)) {
                unlink($path . $fileName);
            }

            $objWriter = IOFactory::createWriter($objPHPExcel, 'Xls');
            $objWriter->save($path .$fileName);
        }

        return [$fileName ?$path.$fileName:'', config('filesystem.disks')['export_RiskReport']['url'].'/'.$fileName];

    }
}
