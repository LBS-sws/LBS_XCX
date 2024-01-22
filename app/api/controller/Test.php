<?php
declare (strict_types = 1);
namespace app\api\controller;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

use app\common\model\ServicePhotoModel;
class Test
{
    /**
     * 处理现有文件 同步到七牛云
     * */
    public function index()
    {

        /**
         * AccessKey:l9vdqWdQbdh_vq8j1_RcMQDoB0fhrVoIFILoGMk-
         * SecretKey:z54S16sXKLQHbv6ZqYkyNmOZNIc6_CC-JWJKETF-
         */
        $accessKey = 'l9vdqWdQbdh_vq8j1_RcMQDoB0fhrVoIFILoGMk-';
        $secretKey = 'z54S16sXKLQHbv6ZqYkyNmOZNIc6_CC-JWJKETF-';
        $bucket = 'lbsgroup-kudo';

        $servicePhotoModel = new ServicePhotoModel();
        $photoObj = $servicePhotoModel->limit(5)->select()->toArray();
        var_dump($photoObj);

        $basePath = public_path() . '/storage'; // 获取public目录路径
        $auth = new Auth($accessKey, $secretKey);
        $uploadMgr = new UploadManager();

        $servicePhotoModel = new ServicePhotoModel();

        $failedFilePaths = [];
//        $this->uploadFiles($basePath, $auth, $uploadMgr, $bucket, $failedFilePaths);

        // 将上传失败的文件路径记录到日志文件中
        if (!empty($failedFilePaths)) {
            $logFilePath = '/file.log'; // 替换为实际的日志文件路径

            $logMessage = "以下文件上传失败：\n";
            foreach ($failedFilePaths as $failedFilePath) {
                $logMessage .= $failedFilePath . "\n";
            }

            file_put_contents($logFilePath, $logMessage, FILE_APPEND);
        }
    }

    public function uploadFiles($basePath, $auth, $uploadMgr, $bucket, &$failedFilePaths)
    {
        // 获取指定目录中的所有文件和子目录
        $items = scandir($basePath);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $path = $basePath . '/' . $item;
                if (is_dir($path)) {
                    // 如果是子目录，递归调用上传函数
                    $this->uploadFiles($path, $auth, $uploadMgr, $bucket, $failedFilePaths);
                } else {
                    // 如果是文件，执行上传操作
                    // 生成上传凭证
                    $token = $auth->uploadToken($bucket);

                    // 执行上传操作
                    list($ret, $err) = $uploadMgr->putFile($token, null, $path);
                    var_dump($ret);

                    if ($err !== null) {
                        // 上传失败，记录失败的文件路径
                        $failedFilePaths[] = $path;
                    } else {
                        // 上传成功
                        // 实时输出结果

                        echo "文件上传成功：" . $item . "\n";
                    }
                }
            }
        }
    }
}
