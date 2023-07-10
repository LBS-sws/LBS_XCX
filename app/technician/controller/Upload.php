<?php

namespace app\technician\controller;

use app\BaseController;
use image\CompressImg;
use think\facade\Filesystem;
use think\facade\Request;
use think\facade\Validate;

class Upload extends BaseController
{
    /**
     * 微信小程序上传图片
     */
    public function imgswx()
    {
        // 查看上传的版本
        $version = request()->param('version', 0);

        if ($version > 1) {
            try {
                $file = Request::file();
                if (null === $file) {
                    return error(-1, '请选择图片', '请选择图片');
                }
                $files = Request::file('file');
                if (!Validate::fileSize($file, 1024 * 1024 * 5)) {
                    return error(-1, '图片过大', '图片过大');
                }
                if (!Validate::fileExt($file, 'jpeg,jpg,png,gif')) {
                    return error(-1, '图片格式错误', '图片格式错误');
                }
                $newFilename = $this->create_trade_no() . '.' . $files->getOriginalExtension();
                $savePath = 'img' . DIRECTORY_SEPARATOR . date('Ymd');
                $savenameOrigin = Filesystem::disk('public')->putFileAs($savePath, $files, $newFilename);
                $savenameTmp = DIRECTORY_SEPARATOR . "storage/". DIRECTORY_SEPARATOR . $savenameOrigin;
                $saveName = str_replace("\\", '/', $savenameTmp);
                $source = $_SERVER['DOCUMENT_ROOT'] . $saveName;
                if (filesize($source) === 0 || $files->getSize() <= 1024) {
                    return error(-1, '请重新上传。[size]:' . filesize($source), '请重新上传。[size]:' . filesize($source));
                }
                $percent = $this->calculateCompressionPercent($files->getSize());
                if ($percent < 1) {
                    if (file_exists($source)) {
                        (new CompressImg($source, $percent))->compressImg($source);
                    } else {
                        return error(-1, '无法打开或处理图片', '无法打开或处理图片');
                    }
                }
                return success(0, 'ok', ['file_name' => $saveName]);
            } catch (\Exception $e) {
                return error(-1, $e->getMessage(), $e->getMessage());
            }
        } else {
            try {
                $file = request()->file('file');
                $savename_original = \think\facade\Filesystem::disk('public')->putFile('img', $file);
                $savename_new = "/storage/" . $savename_original;
                $savename = str_replace("\\", '/', $savename_new);
                $source = $_SERVER['DOCUMENT_ROOT'] . $savename;
                $percent = 0.75;
                (new CompressImg($source, $percent))->compressImg($source);
                return json($savename);
            } catch (\Exception $exception) {
                $orgin_path = '/storage/upload_exception/err_pic.png';
                $source = $_SERVER['DOCUMENT_ROOT'] . $orgin_path;
                $end_path = $_SERVER['DOCUMENT_ROOT'] . '/storage/upload_exception/' . date('Y-m-d') . '/';
                $fileName = $this->fileCopy($source, $end_path);
                $data = "/storage/upload_exception/" . date('Y-m-d') . DIRECTORY_SEPARATOR . $fileName;
                return json($exception->getMessage());
            }
        }
    }


    /**
     * 计算压缩百分比
     * @param int $size 图片大小
     * @return float 压缩百分比
     */
    protected function calculateCompressionPercent($size): float
    {
        $thresholds = [
            100 * 1024 => 0.8,   // 100KB 80%
            500 * 1024 => 0.7,   // 500KB 70%
            1024 * 1024 => 0.5,  // 1MB 50%
            2 * 1024 * 1024 => 0.5,  // 2MB 60%
            3 * 1024 * 1024 => 0.4,  // 3MB 50%
        ];

        foreach ($thresholds as $threshold => $percent) {
            if ($size <= $threshold) {
                return $percent;
            }
        }

        return 1.0;
    }

    /**
     * 生成唯一的交易号
     * @param string $prefix 前缀
     * @return string 生成的交易号
     */
    protected function create_trade_no($prefix = ''): string
    {
        return $prefix . date('ymdHis', time()) . substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
    }

    /**
     * 文件复制
     * @param string $file 文件
     * @param string $path 文件路径
     * @return string 复制后的文件名
     */
    protected function fileCopy(string $file, string $path)
    {
        $dir = dirname($file);
        $fileName = str_replace($dir . '/', '', $file); // 获取文件名

        if (!is_dir($path)) {
            // 目录不存在则创建
            mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
        }

        $fileNameCopy = uniqid() . '_' . $fileName;
        copy($file, $path . $fileNameCopy);

        return $fileNameCopy;
    }

    /**
     * 字符串长度（考虑中文字符）
     * @param string $str 字符串
     * @return int 字符串长度
     */
    public function utf8_strlen($str)
    {
        $count = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            if ((ord($str[$i]) & 0xC0) != 0x80) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 截取字符串（考虑中文字符）
     * @param string $str 字符串
     * @param int $start 起始位置
     * @param int $length 截取长度
     * @return string 截取后的字符串
     */
    public function msubstr($str, $start, $length)
    {
        $len = 0;
        $tmp = '';

        for ($i = 0; $i < strlen($str); $i++) {
            if (ord($str[$i]) >= 128) {
                $tmp .= $str[$i] . $str[$i + 1] . $str[$i + 2];
                $i += 2;
                $len += 2;
            } else {
                $tmp .= $str[$i];
                $len++;
            }

            if ($len >= $length) {
                break;
            }
        }

        return $tmp;
    }
}
