<?php
declare (strict_types = 1);

namespace app\listener;

use app\jobs\service\CreatePDF;
class CreatePDFListener
{
    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($param)
    {
        $class = new CreatePDF();
        $result = $class->htmlTopPDF($param);
    }
}
