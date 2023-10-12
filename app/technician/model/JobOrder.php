<?php

namespace app\technician\model;

use think\model;
class JobOrder extends Model
{
    protected $table = 'joborder';

    /**
     * 关联客户签名点评表，获取客户评分
     * @return model\relation\HasOne
     */
    public function ReportAutographV2(){
        return $this->hasOne(AutographV2::class,'job_id', 'JobID')->bind(['customer_grade']);
    }
}