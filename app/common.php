<?php
// 这是系统自动生成的公共文件
//function_exists('success')判断是否有这个文件
//成功时调用
if(!function_exists('success'))
{
    function success($code,$msg,$data)
    {
        return json([
            'code'=>$code,
            'msg'=>$msg,
            'data'=>$data,
        ]);
    }
}
//失败时调用
if(!function_exists('error'))
{
    function error($code,$msg,$data)
    {
        return json([
            'code'=>$code,
            'msg'=>$msg,
            'data'=>$data,
 
        ]);
 
    }
}