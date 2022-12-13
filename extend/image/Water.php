<?php

namespace image;

//$dst:大图路径
//$src:水印图片路径
//$pos:水印的位置 0,1,2,3,4,5,6,7,8
//$tm:水印的透明度
class Water
{
    function addWaterImg($dst, $src, $pos = 8, $tm = 90)
    {
        //1.创建画布
        $dst_arr = $this->getinfo($dst); //调用下面的getinfo函数,获取本图片的资源和大小组成的数组
        $dst_res = $dst_arr['res']; //获取该图片的资源
        $src_arr = $this->getinfo($src);
        $src_res = $src_arr['res'];

        //2.合并图片
        switch ($pos) { //判断水印存在的位置
            case 0: //0位置
                $x = 0; //左上角的x为0
                $y = 0; //左上角的y为0
                break;
            case 1: //上部的中间
                $x = $dst_arr['width'] / 2 - $src_arr['width'] / 2; //大图宽度的一半减去小图宽度的一半
                $y = 0;
                break;
            case 2: //右上角
                $x = $dst_arr['width'] - $src_arr['width']; //大图宽度减去小图宽度就是小图要放的坐标x的位置
                $y = 0;
                break;
            case 3: //中间左侧
                $x = 0;
                $y = $dst_arr['height'] / 2 - $src_arr['height'] / 2; //大图高度的一半减去小图高度的一半
                break;
            case 4:
                $x = $dst_arr['width'] / 2 - $src_arr['width'] / 2;
                $y = $dst_arr['height'] / 2 - $src_arr['height'] / 2;
                break;
            case 5:
                $x = $dst_arr['width'] - $src_arr['width'];
                $y = $dst_arr['height'] / 2 - $src_arr['height'] / 2;
                break;
            case 6:
                $x = 0;
                $y = $dst_arr['height'] - $src_arr['height'];
                break;
            case 7:
                $x = $dst_arr['width'] / 2 - $src_arr['width'] / 2;
                $y = $dst_arr['height'] - $src_arr['height'];
                break;
            case 8:
            default:
                $x = $dst_arr['width'] - $src_arr['width'];
                $y = $dst_arr['height'] - $src_arr['height'];
                echo $x;
                echo $y;
                break;
        }

        imagecopymerge($dst_res, $src_res, $x, $y, 0, 0, $src_arr['width'], $src_arr['height'], $tm); //合并两个图片
        //4.输出图像
        imagepng($dst_res, $dst);
        //5.销毁资源
        imagedestroy($dst_res);
        imagedestroy($src_res);
    }


    /**
     * 给图片添加文字水印 可控制位置，旋转，多行文字    **有效字体未验证**
     * @param string $imgurl  图片地址
     * @param array $text   水印文字（多行以'|'分割）
     * @param int $fontSize 字体大小
     * @param type $color 字体颜色  如： 255,255,255
     * @param int $point 水印位置
     * @param type $font 字体
     * @param int $angle 旋转角度  允许值：  0-90   270-360 不含
     * @param string $newimgurl  新图片地址 默认使用后缀命名图片
     * @return boolean
     */
    function addWaterText($imgurl, $text, $fontSize = '50', $color = '255,255,255', $point = '7', $font = '/myfont.TTF', $angle = 0, $newimgurl)
    {
        if(strlen($text)>=60){
            $fontSize = 30;
        }

        $imageCreateFunArr = array('image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/gif' => 'imagecreatefromgif');
        $imageOutputFunArr = array('image/jpeg' => 'imagejpeg', 'image/png' => 'imagepng', 'image/gif' => 'imagegif');

        //获取图片的mime类型
        $imgsize = getimagesize($imgurl);

        if (empty($imgsize)) {
            return false; //not image
        }

        $imgWidth = $imgsize[0];
        $imgHeight = $imgsize[1];
        $imgMime = $imgsize['mime'];

        if (!isset($imageCreateFunArr[$imgMime])) {
            return false; //do not have create img function
        }
        if (!isset($imageOutputFunArr[$imgMime])) {
            return false; //do not have output img function
        }

        $imageCreateFun = $imageCreateFunArr[$imgMime];
        $imageOutputFun = $imageOutputFunArr[$imgMime];

        $im = $imageCreateFun($imgurl);

        /*
         * 参数判断
         */
        $color = explode(',', $color);
        $text_color = imagecolorallocate($im, intval($color[0]), intval($color[1]), intval($color[2])); //文字水印颜色
        $point = intval($point) > 0 && intval($point) < 10 ? intval($point) : 1; //文字水印所在的位置
        $fontSize = intval($fontSize) > 0 ? intval($fontSize) : 14;
        $angle = ($angle >= 0 && $angle < 90 || $angle > 270 && $angle < 360) ? $angle : 0; //判断输入的angle值有效性
        $fontUrl = $_SERVER['DOCUMENT_ROOT'] . ($font ? $font : '/myfont.TTF'); //有效字体未验证
        $text = explode('|', $text);
        $newimgurl = $newimgurl ? $newimgurl : $imgurl . '_WordsWatermark.jpg'; //新图片地址 统一图片后缀

        /**
         *  根据文字所在图片的位置方向，计算文字的坐标
         * 首先获取文字的宽，高， 写一行文字，超出图片后是不显示的
         */
        $textLength = count($text) - 1;
        $maxtext = 0;
        foreach ($text as $val) {
            $maxtext = strlen($val) > strlen($maxtext) ? $val : $maxtext;
        }
        $textSize = imagettfbbox($fontSize, 0, $fontUrl, $maxtext);
        $textWidth = $textSize[2] - $textSize[1]; //文字的最大宽度
        $textHeight = $textSize[1] - $textSize[7]; //文字的高度
        $lineHeight = $textHeight + 3; //文字的行高
        //是否可以添加文字水印 只有图片的可以容纳文字水印时才添加
        if ($textWidth + 40 > $imgWidth || $lineHeight * $textLength + 40 > $imgHeight) {
            return false; //图片太小了，无法添加文字水印
        }

        if ($point == 1) { //左上角
            $porintLeft = 20;
            $pointTop = 20;
        } elseif ($point == 2) { //上中部
            $porintLeft = floor(($imgWidth - $textWidth) / 2);
            $pointTop = 20;
        } elseif ($point == 3) { //右上部
            $porintLeft = $imgWidth - $textWidth - 20;
            $pointTop = 20;
        } elseif ($point == 4) { //左中部
            $porintLeft = 20;
            $pointTop = floor(($imgHeight - $textLength * $lineHeight) / 2);
        } elseif ($point == 5) { //正中部
            $porintLeft = floor(($imgWidth - $textWidth) / 2);
            $pointTop = floor(($imgHeight - $textLength * $lineHeight) / 2);
        } elseif ($point == 6) { //右中部
            $porintLeft = $imgWidth - $textWidth - 20;
            $pointTop = floor(($imgHeight - $textLength * $lineHeight) / 2);
        } elseif ($point == 7) { //左下部
            $porintLeft = 20;
            $pointTop = $imgHeight - $textLength * $lineHeight - 20;
        } elseif ($point == 8) { //中下部
            $porintLeft = floor(($imgWidth - $textWidth) / 2);
            $pointTop = $imgHeight - $textLength * $lineHeight - 20;
        } elseif ($point == 9) { //右下部
            $porintLeft = $imgWidth - $textWidth - 20;
            $pointTop = $imgHeight - $textLength * $lineHeight - 20;
        }

        //如果有angle旋转角度，则重新设置 top ,left 坐标值
        if ($angle != 0) {
            if ($angle < 90) {
                $diffTop = ceil(sin($angle * M_PI / 180) * $textWidth);

                if (in_array($point, array(1, 2, 3))) { // 上部 top 值增加
                    $pointTop += $diffTop;
                } elseif (in_array($point, array(4, 5, 6))) { // 中部 top 值根据图片总高判断
                    if ($textWidth > ceil($imgHeight / 2)) {
                        $pointTop += ceil(($textWidth - $imgHeight / 2) / 2);
                    }
                }
            } elseif ($angle > 270) {
                $diffTop = ceil(sin((360 - $angle) * M_PI / 180) * $textWidth);

                if (in_array($point, array(7, 8, 9))) { // 上部 top 值增加
                    $pointTop -= $diffTop;
                } elseif (in_array($point, array(4, 5, 6))) { // 中部 top 值根据图片总高判断
                    if ($textWidth > ceil($imgHeight / 2)) {
                        $pointTop = ceil(($imgHeight - $diffTop) / 2);
                    }
                }
            }
        }
        $white = imagecolorallocate($im, 222, 229, 207);
        $black = imagecolorallocate($im, 30, 135, 234);
        // $black1 = imagecolorallocate($im, 240, 10, 50);
        foreach ($text as $key => $val) {
            imagettftext($im, $fontSize, $angle, $porintLeft + 2, ($pointTop + $key * $lineHeight), $white, $fontUrl, $val);
            imagettftext($im, $fontSize, $angle, $porintLeft, ($pointTop + $key * $lineHeight), $black, $fontUrl, $val);
        }
        // 输出图像
        // JPEG图像生成的图像的质量的是一个范围从0（最低质量，最小的文件大小）到100（最高质量，最大文件大小）
        // PNG生成图像的质量范围从0到9的
        $imageOutputFun($im, $newimgurl, 80);

        // 释放内存
        imagedestroy($im);
        return $newimgurl;
    }
}
