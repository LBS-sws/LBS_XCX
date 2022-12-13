<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class Upload
{
   /**
     * 微信小程序上传图片
     * */
    public function imgswx(){
        $file = request()->file('file');
        $savename = \think\facade\Filesystem::disk('public')->putFile( 'img', $file);
        $savename = "/storage/".$savename;
        $savename = str_replace("\\",'/',$savename);
        return json($savename);
    }

}
