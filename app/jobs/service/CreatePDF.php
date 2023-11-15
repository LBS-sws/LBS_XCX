<?php
namespace app\jobs\service;

class CreatePDF
{

    /**
     * @param $param
     * @return mixed|string
     */
    public function htmlTopPDF($param)
    {
        $data = CreateHtml::CreateHtml($param);
        $month = date('Y-m',time());
        $res = $this->outputHtml($month, $data['html'],  $data['CustomerName'].'-服务现场管理报告');
        return $res ? $data['html'] : '';
    }

    public function outputHtml($month, $ctx, $cust)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/report/' . $month . '/';
        if (!is_dir($dir)) {
            //iconv方法是为了防止中文乱码，保证可以创建识别中文目录，不用iconv方法格式的话，将无法创建中文目录,第三参数的开启递归模式，默认是关闭的
            mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
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
        $cmd = "wkhtmltopdf --print-media-type --page-size A4 --margin-left 0 --margin-right 0 --enable-local-file-access $html_name $pdf_name 2>&1";
        @exec($cmd, $output, $return_val);
        if ($return_val === 0) {
            return 1;
        }
    }

}