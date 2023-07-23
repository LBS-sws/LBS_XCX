<?php

namespace app\common\model;

use think\model;

class ImRecords extends Model
{
    protected $name = 'im_records';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;

}