<?php

namespace app\technician\model;

use think\Model;

/**
 * 评价表
 */
class Evaluates extends Model
{

    // 设置当前模型对应的完整数据表名称
    protected $table = 'lbs_evaluates';


    protected $schema = [
        'id'          => 'int',
        'question'    => 'string',
        'score'       => 'int',
        'total_score' => 'int',
        'staff_id'    => 'int',
        'order_id'    => 'string',
        'order_type'  => 'string',
        'customer_id' => 'string',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    //自动时间戳
    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}