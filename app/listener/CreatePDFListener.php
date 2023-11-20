<?php
declare (strict_types = 1);

namespace app\listener;

use app\jobs\service\CreatePDF;
class CreatePDFListener
{
    /**
     * @param $param
     * @return void
     */
    public function handle($param)
    {
        $class = new CreatePDF();
        $class->htmlTopPDF($param);
    }
}
