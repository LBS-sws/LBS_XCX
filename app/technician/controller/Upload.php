<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;
use think\facade\Log;

use think\exception\ValidateException;
use image\CompressImg;
use image\imageTool;
// use image\Water;


class Upload extends BaseController
{
    /**
     * 微信小程序上传图片
     * */
    public function imgswx(){
        //查看上传的版本
        $version = request()->param('version',0);
        if($version > 1){
            try {
                $file = request()->file();
                dd($file);
                if (null === $file) {
                    return error(-1,'请选择图片',[]);
                }
                $files = request()->file('file');
                validate(['file' => 'fileExt:jpg,png,gif,jpeg'])->check(['file' => $file]);
                // \validate(['image'=>'fileExt:jpg,png,gif,pdf'])->check($file);
                $savename = \think\facade\Filesystem::disk('public')->putFile( 'img', $files);
                $savename = "/storage/".$savename;
                $savename = str_replace("\\",'/',$savename);
                $source = $_SERVER['DOCUMENT_ROOT'] . $savename; // 上传后的路径
                $percent = 0.70;  #缩放比例
                (new CompressImg($source, $percent))->compressImg($source);  //压缩

                // 加水印 暂不使用
                /*if($is_mark){
                    $newImg = imageTool::getInstance();
                    $config = array(
                        # 设置绘制类型'img'图片水印，'txt'文字水印
                        'draw_type' => 'txt',
                        # 背景图片，支持jpeg,png
                        'draw_bg' => $source,
                        # 水印透明度 0-127
                        'opacity' => 60,
                        # 水印是否随机位置
                        'random_location' => false,
                        # logo水印
                        'logo_img' => './resources/ohcodes_logo.png',
                        # 字体文件
                        'font_file' => './myfont.TTF',
                        # 倾斜度，仅文字水印生效
                        'rotate_angle' => 45,
                        # 水印文字
                        'watermark_text'=> date('y/m/d H:i').'@'.$customer,
                        # 水印文字颜色13同等于RGB 13,13,13
                        'text_rgb' => 0,
                        # 文字水印是否开启阴影
                        'shadow' => true,
                        # 文字水印阴影颜色
                        'shadow_rgb' => '255,255,255',
                        # 阴影偏移量，允许负值如-3
                        'shadow_offset' => 3
                    );
                    $newImg->okIsRun($config);
                }*/
                // 先压缩再加水印就会GG[现在不会了，因为图片画布问题，裂开]
                return success(0,'ok',['file_name'=>$savename]);
            } catch (\think\exception\ValidateException $e) {
                return error(-1,$e->getMessage(),[]);
            }
        }else{
            try {
                $file = request()->file('file');
                // var_dump($file);exit;
                $savename_original = \think\facade\Filesystem::disk('public')->putFile( 'img', $file);
                $savename_new = "/storage/".$savename_original;
                $savename = str_replace("\\",'/',$savename_new);
                $source = $_SERVER['DOCUMENT_ROOT'] . $savename; // 上传后的路径
                // $water = new Water();
                $percent = 0.75;  #缩放比例
                (new CompressImg($source, $percent))->compressImg($source);  //压缩
                return json($savename);
            }catch (\Exception $exception){
                $orgin_path = '/storage/upload_exception/err_pic.png';
                $source = $_SERVER['DOCUMENT_ROOT'].$orgin_path;
                $end_path =$_SERVER['DOCUMENT_ROOT'].'/storage/upload_exception/'.date('Y-m-d').'/';
                $fileName = $this->fileCopy($source,$end_path);
                // 上传错误 使用错误图片标识
                $data = "/storage/upload_exception/".date('Y-m-d')."/".$fileName;
                return json($exception->getMessage());
            }
        }


    }


    /**
     * @description: 文件复制
     * @param  string $file 文件
     * @param  string $path 文件路径
     * @return:
     */
    protected function fileCopy(string $file, string $path){
        $dir=dirname($file);
        $fileName= str_replace( $dir. '/','', $file);  //获取文件名
        if(!is_dir($path)){   //判断目录是否存在
            //不存在则创建
            //   mkdir($pathcurr,0777))
            mkdir(iconv("UTF-8", "GBK",$path),0777,true); //iconv方法是为了防止中文乱码，保证可以创建识别中文目录，不用iconv方法格式的话，将无法创建中文目录,第三参数的开启递归模式，默认是关闭的
        }
        $fileNameCopy = uniqid().'_'.$fileName;
        copy($file,$path.$fileNameCopy);   //public_path()是laravel的自带方法生成public目录的绝对路径
        return $fileNameCopy;
    }


}
