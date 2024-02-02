<?php
namespace app\jobs\service;

class CreatePDF
{

    /**
     * @param $param
     * @return void
     */
    public function htmlTopPDF($param)
    {
        $data = CreateHtml::CreateHtml($param);
        $month = date('Y-m',time());
        $file_path = $this->outputHtml($month, $data['html'],  $data['CustomerName'].'-服务现场管理报告');
        if($file_path){
            $file = fopen($file_path, 'rb');
            $file_stats = fstat($file);
            $content_length = $file_stats['size'];
            header('Content-Type: application/pdf');
            header('Content-Length: ' . $content_length);
            fpassthru($file);
            fclose($file);
        }
    }

    /**
     * @param $month
     * @param $ctx
     * @param $cust
     * @return string
     */
    public function outputHtml($month, $ctx, $cust)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/report/' . $month . '/';
        if (!is_dir($dir)) {
            //iconv方法是为了防止中文乱码，保证可以创建识别中文目录，不用iconv方法格式的话，将无法创建中文目录,第三参数的开启递归模式，默认是关闭的
            mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
        }
        $fp = fopen($dir . $cust . '.html', "w");
        fwrite($fp, $ctx);
        fclose($fp);
        $rs = $this->exec($dir, $cust, $cust, $month);
        return $rs ? $dir . $cust . '.pdf' : false;
    }

    /**
     * @param $path
     * @param $filename
     * @param $name
     * @param $month
     * @return bool
     */
    public function exec($path, $filename, $name, $month)
    {
        $ext_pdf = '.pdf';
        $ext_html = '.html';
        $html_name = $path . $filename . $ext_html;
        $pdf_name = $path . $filename . $ext_pdf;
        $set_charset = 'export LANG=en_US.UTF-8;';
        $cmd = "wkhtmltopdf --print-media-type --page-size A4 --margin-left 0 --margin-right 0 --enable-local-file-access --footer-center [page]/[topage]  $html_name $pdf_name 2>&1"; //--print-media-type --page-size A4 --margin-left 0 --margin-right 0 --enable-local-file-access
        @exec($cmd, $output, $return_val);
        if($return_val === 0){
            return 1;
        }
    }

}