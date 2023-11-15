<?php

namespace app\common\model;

use app\technician\model\Evaluates;
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

    public function checkOrders($condition): array
    {
        //根据工作id查询出客户编号是多少
        $where = [
            'JobDate' => $condition['job_date'],
            'CustomerID' => $condition['customer_id'],
            'Staff01' => $condition['staff_id'],
            ['JobID', '<>', $condition['order_id']],
            ['StartTime', '<>', '00:00:00']
        ];
        $orders = JobOrder::field('JobID')
            ->where($where)
            ->select();

        if ($orders->isEmpty()) {
            return [];
        }
        return $orders->toArray();
    }

    /**
     * 获取分数(来自点评)
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getCustomerGradeAttr($value,$data)
    {
        return (new Evaluates())->where(['order_id'=>$data['JobID'],'order_type'=>1])->value('score',null);
    }
}
