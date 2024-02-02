<?php

namespace app\common\model;

use think\model;

class CustomerDeviceModel extends Model
{
    const SIGFOX = '老鼠动能感应器';
    const NBIOT = '智能老鼠夹';
    protected $table = 'lbs_customer_device';
    protected $pk = 'id';

}
