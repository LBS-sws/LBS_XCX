<?php

namespace app\technician\controller;

use app\Request;
use app\technician\model\JobOrder;
use think\response\Json;

class Evaluates
{

    /**
     * 新增评价记录
     * @param Request $request
     * @return Json
     */
    public function add(Request $request): Json
    {
       $questions_one =  $request->post('q0');//问题1
       $questions_two =  $request->post('q0');//问题2
       $questions_three =  $request->post('q0');//问题3
       $staffId =  $request->post('staffid','admin');//职员id
       $jobId =  $request->post('job_id');//订单id


        $questions = [
          "史伟莎技术人员着装是否整齐?"=>  $questions_one,
          "服务前史伟莎技术人员是否主动了解门店情况?"=>  $questions_two,
          "服务完成后史伟莎技术人员是否汇报本次服务情况?"=>  $questions_three,
        ];

       $jobOrder =  JobOrder::Where('JobID',$jobId)->find();

       foreach ($questions as $question => $b){
           $evaluates = new \app\technician\model\Evaluates();
           $evaluates->save([
               "question" => $question,
               "score" => $b ?1:2,
               "customer_id" => $jobOrder['CustomerID'],
               "staff_id" => $staffId,
               "contract_id" => $jobId,
           ]);
       }
       return success();
    }



}