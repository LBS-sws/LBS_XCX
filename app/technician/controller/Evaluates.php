<?php

namespace app\technician\controller;

use app\Request;
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
        $data = [];
        foreach ($questions as $key=>$val){
            if($val['type'] == 'radio'){//是、否单选项
                $data[$key] = [
                    'question' => $val['question'],
                    //后期如果有多种题型再扩展
//                    'type' => $val['type'],
//                    'answer' => [['o'=>'是','v'=>1], ['o'=>'否','v'=>0]]
                    'answer' => ''
                ];
            }
        }

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
        $staffId =  $request->post('staffid','admin');//职员id
        $jobId =  $request->post('job_id',0);//订单id
        $jobType =  $request->post('job_type',0);//订单id

        if(empty($jobId) || empty($jobType)){
            return error(0,'缺少参数');
        }

        //是否已评价过
        $evaluatesModel = new \app\technician\model\Evaluates();
        $evaluates = $evaluatesModel->where(['staff_id'=>$staffId,'order_id'=>$jobId,'order_type'=>$jobType])->find();
        if(!empty($evaluates)){
            return error(0,'请不要重复评价');
        }

        //查询订单
        $customer_id = '';
        switch ($jobType){
            case 1: //jobOrder
                $Order =  JobOrder::field('JobID,CustomerID')->Where('JobID',$jobId)->find()->toArray();
                if(empty($Order)){
                    return error(0,'找不到工作单');
                }
                $customer_id = $Order['CustomerID'];
                break;
            case 2: //followUpOrder
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
        $evaluatesModel->save([
           'question' => $questions_str,
           'score' => $score,
           'total_score' => $total_score,
           'customer_id' => $customer_id,
           'staff_id' => $staffId,
           'order_id' => $jobId,
           'order_type' => $jobType
        ]);
        return success(1, '点评成功');
    }



}