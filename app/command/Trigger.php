<?php
declare (strict_types = 1);

namespace app\command;

use app\jobs\service\TriggerDeviceService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Trigger extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Trigger')
            ->setDescription('the Trigger command');
    }

    protected function execute(Input $input, Output $output)
    {
        $typeArrr = ['sigfox'];
        foreach ($typeArrr as $item){
            TriggerDeviceService::getDeviceList($item);
        }
    }
}
