<?php
declare (strict_types = 1);

namespace app\technician\controller;
use app\BaseController;
use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class Upload
{
    /**
     * 上传图片
     */
     public function images(){

        $file = request() -> file('file');

        if ($file == null) {
            return $this -> show(
                config("status.failed"),
                config("message.failed"),
                '未上传图片'
            );
        }

        $temp = explode(".", $_FILES["file"]["name"]);
        $extension = end($temp);

        if(!in_array($extension, array("jpeg","jpg","png"))){
            return $this -> show(
                config("status.failed"),
                config("message.failed"),
                '上传图片不合法'
            );
        }
        // var_dump($file);die();
        $saveName = Filesystem::disk('photo')->putFile('photo', $file, 'md5');

        return (str_replace('\\', '', '/uploads/' . $saveName));
     }
}
