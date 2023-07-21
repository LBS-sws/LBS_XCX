<?php

namespace app\common\model;

use think\Model;

class ImCustomer extends Model
{
    protected $name = 'im_customers';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;

}
