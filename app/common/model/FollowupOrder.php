<?php

namespace app\common\model;

use app\technician\model\Evaluates;
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


    /**
     * 获取分数(来自点评)
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getCustomerGradeAttr($value,$data)
    {
        return (new Evaluates())->where(['order_id'=>$data['FollowUpID'],'order_type'=>2])->value('score',null);
    }
}