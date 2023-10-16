<?php

namespace app\common\model;

use think\model;

class AutographV2 extends Model
{
    protected $table = 'lbs_report_autograph_v2';

    const jobType_jobOrder = 1;//服务单
    const jobType_followOrder = 2;//跟进单
}