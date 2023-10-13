<?php

namespace app\technician\controller;

use app\Request;
use app\technician\model\AutographV2;
use app\technician\model\FollowupOrder;
use app\technician\model\JobOrder;
use think\response\Json;

/**
 * 评估
 */
class Evaluates
{
    /**
     * 获取问题列表
     * @param Request $request
     * @return Json
     */
    public function getQuestions(Request $request): Json
    {
        $type =  $request->post('type','');
        if(empty($type)){
            error(0,'缺少参数');
        }

        $questions = config('evaluates.'.$type);
        if(empty($questions)){
            return success(1);
        }

        //构造数据
        $data = $this->processData($questions);

        return success(1,'成功',$data);
    }

    /**
     * 新增评价记录
     * @param Request $request
     * @return Json
     */
    public function add(Request $request): Json
    {
        $questions_str =  $request->post('questions','');//问题
        $questionType =  $request->post('question_type','questions');//问题类型
        $staffId =  $request->post('staffid','');//职员id
        $jobId =  $request->post('job_id',0);//订单id
        $jobType =  $request->post('job_type',0);//订单id

        if(empty($staffId) || empty($jobId) || empty($jobType)){
            return error(0,'缺少参数');
        }

        //是否已评价过
        $evaluates = (new \app\technician\model\Evaluates())->where(['order_id'=>$jobId,'order_type'=>$jobType])->find();
        if(empty($evaluates)){
            $evaluates = new \app\technician\model\Evaluates();
        }

        //查询订单
        $customer_id = '';
        switch ($jobType){
            case 1: //jobOrder
                $Order =  JobOrder::field('JobID,CustomerID')->Where('JobID',$jobId)->find();
                if(empty($Order)){
                    return error(0,'找不到工作单');
                }
                $customer_id = $Order['CustomerID'];
                break;
            case 2: //followUpOrder
                $Order =  FollowupOrder::field('FollowUpID,CustomerID')->Where('FollowUpID',$jobId)->find();
                if(empty($Order)){
                    return error(0,'找不到跟进单');
                }
                $customer_id = $Order['CustomerID'];
                break;
        }

        //整理得分
        $questions = config('evaluates.'.$questionType);//获取原题
        $user_answer = json_decode($questions_str,true);
        $total_score = count($questions);
        $score = 0;
        foreach ($questions as $key=>$val){
            if($val['type'] == 'radio' && $user_answer[$key]['answer']==1){//单选项 且
                $score++;
            }
        }

        //保存
        $evaluates->save([
           'question' => $questions_str,
           'score' => $score,
           'total_score' => $total_score,
           'customer_id' => $customer_id,
           'staff_id' => $staffId,
           'order_id' => $jobId,
           'order_type' => $jobType
        ]);

        //更新 lbs_report_autograph_v2 的评分
        (new AutographV2())->where(['job_id'=>$jobId,'job_type'=>$jobType])->save(['customer_grade'=>$score]);

        return success(1, '点评成功');
    }

    /**
     * 获取问卷与填写详情
     * @return void
     */
    public function getAnswer(): Json{
        $questionType =  request()->post('question_type','questions');//问题类型
        $staffId =  request()->post('staffid','');//职员id
        $jobId =  request()->post('job_id',0);//订单id
        $jobType =  request()->post('job_type',0);//订单id

        if(empty($staffId) || empty($jobId) || empty($jobType)){
            return error(0,'缺少参数');
        }

        //是否已评价过
        $evaluates = (new \app\technician\model\Evaluates())->field('id,question')->where(['order_id'=>$jobId,'order_type'=>$jobType])->find();
        if(empty($evaluates)){
            $questions = config('evaluates.'.$questionType);
        }else{
            $questions = json_decode($evaluates['question'],true);
        }

        //处理数据
        $data = $this->processData($questions);
        return success(1,'成功',$data);
    }

    /**
     * 处理问题数据
     * @param $questions
     * @return array
     */
    public function processData($questions){
        $data = [];
        foreach ($questions as $key=>$val){
            if($val['type'] == 'radio'){//是、否单选项
                $data[$key] = [
                    'question' => $val['question'],
                    'type' => $val['type']??'radio',
                    //后期如果有多种题型再扩展
//                    'answer' => [['o'=>'是','v'=>1], ['o'=>'否','v'=>0]]
                    'answer' => $val['answer']??''
                ];
            }
        }
        return $data;
    }
}