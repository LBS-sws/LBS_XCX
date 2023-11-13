<?php
declare (strict_types = 1);

namespace app\command;

use app\jobs\service\DeviceService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Device extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Device')
            ->setDescription('the Device command');
    }

    protected function execute(Input $input, Output $output)
    {
        $typeArrr = ['sigfox','nbiot'];
        foreach ($typeArrr as $item){
            DeviceService::getDeviceList($item);
        }
    }
}
