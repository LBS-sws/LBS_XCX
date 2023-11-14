<?php

namespace app\common\model;

use app\common\model\setting\ServiceModel;
use app\technician\model\Evaluates;
use think\model;

class AutographV2 extends Model
{
    protected $table = 'lbs_report_autograph_v2';

    const jobType_jobOrder = 1;//服务单
    const jobType_followOrder = 2;//跟进单


    /**
     * 获取分数(来自点评)
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getScoreAttr($value,&$data)
    {
        $score = (new Evaluates())->where(['order_id'=>$data['job_id'],'order_type'=>$data['job_type']])->value('score',null);
        if($score){
            $data['customer_grade'] = $score;
            return $score;
        }else{
            return $data['customer_grade'];
        }
    }
}