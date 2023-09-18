<?php
// 这是系统自动生成的公共文件
//function_exists('success')判断是否有这个文件
//成功时调用
if (!function_exists('success')) {
    function success($code = 0, $msg = 'success', $data = [])
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
    function error($code = -1, $msg = 'error', $data = [])
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
//  TODO 还是file_put_contents好用！！
if (!function_exists('conversionToImg')) {
    function conversionToImg($base64_image_content, $path, $file_name = "")
    {
        // 匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            if ($file_name == "") {
                $file_name = create_trade_no();
            }
            // 生成唯一的id
            $new_file = $path . $file_name . ".{$type}";

            // 将base64内容写入文件
            $image_data = base64_decode(str_replace($result[1], '', $base64_image_content));
            file_put_contents($new_file, $image_data);

            return '/' . $new_file;
        } else {
            return '';
        }
    }
}

/**
 * 生成唯一的交易号
 * @param string $prefix 前缀
 * @return string 生成的交易号
 */
function create_trade_no($prefix = 'cs'): string
{
    return $prefix . substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
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

if (!function_exists('curl_post')) {
    function curl_post($url, $data = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        if ($output === false) {
            $output = curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }
}

function encrypt($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

// 解密数据
function decrypt($data, $key) {
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}
