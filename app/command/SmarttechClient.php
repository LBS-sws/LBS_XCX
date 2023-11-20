<?php
declare (strict_types = 1);

namespace app\command;

use app\jobs\service\SmarttechClientService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SmarttechClient extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('SmarttechClient')
            ->setDescription('the SmarttechClient command');
    }

    protected function execute(Input $input, Output $output)
    {
        SmarttechClientService::getClientList();
    }
}
