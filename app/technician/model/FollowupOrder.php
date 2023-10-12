<?php

namespace app\technician\model;

use think\model;
class FollowupOrder extends Model
{
    protected $table = 'followuporder';

    /**
     * 关联客户签名点评表，获取客户评分
     * @return model\relation\HasOne
     */
    public function ReportAutographV2(){
        return $this->hasOne(AutographV2::class,'job_id', 'FollowUpID')->bind(['customer_grade']);
    }
}