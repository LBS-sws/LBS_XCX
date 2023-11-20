<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use app\common\model\AutographV2;
use app\technician\model\CustomerCompany;
use think\facade\Db;
use think\facade\Request;
use TCPDF;


class Generatepdf
{
    public $custType = 250;

    public function index(){
        event('CreatePDF',$_POST);
    }




    function pic_rotating($degrees,$url){
        $srcImg = imagecreatefrompng($url);     //获取图片资源
        $rotate = imagerotate($srcImg, $degrees, 0);        //原图旋转

        //获取旋转后的宽高
        $srcWidth = imagesx($rotate);
        $srcHeight = imagesy($rotate);

        //创建新图
        $newImg = imagecreatetruecolor($srcWidth, $srcHeight);

        //分配颜色 + alpha，将颜色填充到新图上
        $alpha = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
        imagefill($newImg, 0, 0, $alpha);

        //将源图拷贝到新图上，并设置在保存 PNG 图像时保存完整的 alpha 通道信息
        imagecopyresampled($newImg, $rotate, 0, 0, 0, 0, $srcWidth, $srcHeight, $srcWidth, $srcHeight);
        imagesavealpha($newImg, true);

        //生成新图
        imagepng($newImg, $url);
    }
}
