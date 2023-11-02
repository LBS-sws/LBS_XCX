<?php

namespace app\technician\controller;

use app\Request;
use app\common\model\AutographV2;
use app\common\model\FollowupOrder;
use app\common\model\JobOrder;
use Exception;
use think\facade\Db;
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
        $questions_str = $request->post('questions', ''); //问题
        $questionType = $request->post('question_type', 'questions'); //问题类型
        $staffId = $request->post('staffid', ''); //职员id
        $jobId = $request->post('job_id', 0); //订单id
        $jobType = $request->post('job_type', 0); //订单id

        if (empty($staffId) || empty($jobId) || empty($jobType)) {
            return error(0, '缺少参数');
        }

        //是否已评价过
        $evaluates = \app\technician\model\Evaluates::where(['order_id' => $jobId, 'order_type' => $jobType])->findOrEmpty();

        //查询订单
//        $customer_id = '';
//        $job_date = '';
//        $staff_id = '';

        switch ($jobType) {
            case 1: //jobOrder
                $Order = JobOrder::field('JobID,CustomerID,JobDate,Staff01')->Where('JobID', $jobId)->findOrEmpty();
                break;
            case 2: //followUpOrder
                $Order = FollowupOrder::field('FollowUpID,CustomerID,JobDate,Staff01')->Where('FollowUpID', $jobId)->findOrEmpty();
                break;
            default:
                return error(0, '无效的订单类型');
        }

        if ($Order->isEmpty()) {
            return error(0, '找不到订单');
        }

        $customer_id = $Order['CustomerID'];
        $job_date = $Order['JobDate'];
        $staff_id = $Order['Staff01'];

        //整理得分
        $questions = config('evaluates.' . $questionType); //获取原题
        $user_answer = json_decode($questions_str, true);
        $total_score = count($questions);
        $score = 0;

        foreach ($questions as $key => $val) {
            if ($val['type'] == 'radio' && $user_answer[$key]['answer'] == 1) { //单选项 且
                $score++;
            }
        }

        $condition = [
            'job_date' => $job_date,
            'staff_id' => $staff_id,
            'customer_id' => $customer_id,
            'order_id' => $jobId,
        ];

        // 在这里执行检查有哪些订单符合条件

        // 原始准备保存的数据
        $originalOrder = [
            'question' => $questions_str,
            'score' => $score,
            'total_score' => $total_score,
            'customer_id' => $customer_id,
            'staff_id' => $staffId,
            'order_id' => $jobId,
            'order_type' => $jobType,
        ];

        // 在这里执行检查有哪些订单符合条件
        $moreOrder = $this->checkOrders($condition);
        if (count($moreOrder) > 0 && $jobType == 1) {
            $newOrder = [];
            foreach ($moreOrder as $k => $v) {
                $newOrder[$k] = [
                    'order_id' => $v['JobID'],
                    'question' => $questions_str,
                    'score' => $score,
                    'total_score' => $total_score,
                    'customer_id' => $customer_id,
                    'staff_id' => $staffId,
                    'order_type' => $jobType,
                ];
            }
            // 把本身的追加进去
            $newOrder[] = $originalOrder;
            // 批量更新或新增
            foreach ($newOrder as $order) {
                $evaluates->where(['order_id' => $order['order_id']])->findOrEmpty()->save($order);
            }
            $jobId = array_column($newOrder, 'order_id');

        } else {
            // 单个保存
            $evaluates->save($originalOrder);
        }
        //更新 lbs_report_autograph_v2 的评分
        $res = AutographV2::whereIn('job_id',$jobId)->where(['job_type' => $jobType])->update(['customer_grade' => $score]);
        if($res) return success(1, '点评成功');
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
