<?php
declare (strict_types = 1);

namespace app\customer\controller;

use app\BaseController;
use think\facade\Db;
use think\Request;

class GetRiskTotal
{
    public function index(Request $request)
    {
        $result = [
            'code' => 0,
            'msg' => '请输入客户ID',
            'data' => null,
        ];

        $staffId = $request->post('staffid');
        $token = $request->header('token');
        $customerId = $request->post('customerid');

        if (!$staffId || !$token || !$customerId) {
            return json($result);
        }

        try {
            $userToken = Db::name('cuztoken')->where('StaffID', $staffId)->find();
            $loginTime = strtotime($userToken['stamp']);
            $nowTime = strtotime('now');
            $cTime = ($nowTime - $loginTime) / 60 / 60;

            if ($token !== $userToken['token'] || $cTime > 24) {
                $result['msg'] = '登录失效，请重新登陆';
                return json($result);
            }

            $lastRiskDatas = [
                'y' => [],
                'n' => [],
                'f' => [],
                'total_y' => 0,
                'total_n' => 0,
                'total_f' => 0,
            ];

            $lastW = [
                'Status' => 3,
                'CustomerID' => $customerId,
            ];

            $lastE = [
                'job_type' => 1,
            ];

            $lastJob = Db::table('joborder')
                ->where($lastW)
                ->order('JobDate', 'desc')
                ->field('GROUP_CONCAT(JobID ORDER BY JobID DESC ) as id')
                ->find();

            if ($lastJob && $lastJob['id']) {
                $y = Db::table('lbs_service_risks')
                    ->where($lastE)
                    ->where('status', 1)
                    ->where('follow_id', 0)
                    ->whereIn('job_id', $lastJob['id'])
                    ->order('id', 'asc')
                    ->select();

                $n = Db::table('lbs_service_risks')
                    ->where($lastE)
                    ->where('status', 0)
                    ->where('follow_id', 0)
                    ->whereIn('job_id', $lastJob['id'])
                    ->order('id', 'asc')
                    ->select();

                $f = Db::table('lbs_service_risks')
                    ->where($lastE)
                    ->where('status', 2)
                    ->whereIn('job_id', $lastJob['id'])
                    ->where('follow_id', '>', 0)
                    ->order('id', 'asc')
                    ->select();

                $lastRiskDatas['y'] = $y ?? [];
                $lastRiskDatas['n'] = $n ?? [];
                $lastRiskDatas['f'] = $f ?? [];
                $lastRiskDatas['total_y'] = count($y);
                $lastRiskDatas['total_n'] = count($n);
                $lastRiskDatas['total_f'] = count($f);
            }

            $result['code'] = 1;
            $result['msg'] = '成功';
            $result['data'] = $lastRiskDatas;

        } catch (\Exception $e) {
            $result['msg'] = '服务器错误';
        }
        return json($result);
    }
}
