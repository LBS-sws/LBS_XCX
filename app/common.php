<?php
// 这是系统自动生成的公共文件
//function_exists('success')判断是否有这个文件
//成功时调用
if (!function_exists('success')) {
    function success($code, $msg, $data)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }
}
//失败时调用
if (!function_exists('error')) {
    function error($code, $msg, $data)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,

        ]);

    }
}

/**
 * base64转为图片
 *
 * @Params base64_image_content base64的内容
 * @Params path 存放路径
 * @return String
 * */
if (!function_exists('conversionToImg')) {
    function conversionToImg($base64_image_content, $path,$file_name = "")
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            if (!file_exists($path)) {
                mkdir($path, 0777, true);//0777表示文件夹权限，windows默认已无效，但这里因为用到第三个参数，得填写；true/false表示是否可以递归创建文件夹
            }
            if($file_name == ""){
                $file_name = unique_str();
            }
            //害怕重复  生成唯一的id
            $new_file = $path . $file_name . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return '/' . $new_file;
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
}

/**
 * 生成唯一的图片编号
 * */
if (!function_exists('unique_str')) {
    function unique_str()
    {
        $charid = strtolower(md5(uniqid(mt_rand(), true)));
        return substr($charid, 0, 8) . substr($charid, 8, 4) . substr($charid, 12, 4) . substr($charid, 16, 4) . substr($charid, 20, 12);
    }
}