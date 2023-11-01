<?php
namespace app\jobs\service;

class CreatePDF
{

    /**
     * @return void
     */
    public function htmlTopPDF($param)
    {
        $html = CreateHtml::CreateHtml($param);
        print_r($html);
        exit;
//        $name = 'aaaaa';
//        $path = public_path();
//        exec("wkhtmltopdf  ".$path."/".$name.".html ".$path."/".$name.".pdf 2>&1",$out, $return_val);
//        if ($return_val === 0) {
//            print_r(mb_convert_encoding($out, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5'));
//        }
    }

}