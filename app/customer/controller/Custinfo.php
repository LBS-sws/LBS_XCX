<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\common\model\ImCustomer;
use app\common\model\ImRecords;
use think\facade\Db;
use think\facade\Request;
use think\Validate;


class Custinfo
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入客户ID';
        $result['data'] = null;
        $token = request()->header('token');
        if(empty($_POST['staffid']) || empty($token) || empty($_POST['customerid'])){
            return json($result);
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $customerid = $_POST['customerid'];
        //获取用户登录信息
        $user_token = Db::name('cuztoken')->where('StaffID',$staffid)->find();
        $login_time = strtotime($user_token['stamp']);
        $now_time = strtotime('now');
        $c_time = ($now_time - $login_time)/60/60;
        //验证登录状态
        if ($token==$user_token['token'] &&  ($c_time <= 24)) {
            $customer = Db::name('customercompany')->where('CustomerID',$customerid)->find();
            if($customer['isHQ'] == 1 && !empty($customer['GroupID'])){
                //查询集团下的所有店
                $customer_group = Db::name('customercompany')->where('GroupID', $customer['GroupID'])->field('CustomerID as value,NameZH as label,City as city')->select()->toArray();
                if (!empty($customer_group)) {
                    $all_stores = implode(',', array_column($customer_group, 'value'));
                    $customer_group[] = ['value' => $all_stores, 'label' => '全部门店', 'city' => ''];
                    return success(1, 'ok', $customer_group);
                }
            }else{
                $data['value'] = $customer['CustomerID'];
                $data['label'] = $customer['NameZH'];
                return success(1,'ok',[$data]);
            }
        }else{
            $result['code'] = 0;
            $result['msg'] = '登录失效，请重新登陆';
            $result['data'] = null;
        }
        return json($result);
    }

    public function getList(){
        $data = [
            'customer_id' => Request::get('customer_id', ''),
            'city' => Request::get('city', ''),
            'page' => Request::get('page', 1),
            'list_rows' => Request::get('list_rows', 15),
            'query' => Request::get('query', ''),
        ];

        $validate = new Validate();
        $validate->rule([
            'city' => 'require',
        ]);

        if (!$validate->check($data)) {
            return error(1, $validate->getError());
        }


        $where = [];

        if ($data['customer_id'] != '') {
            $where[] = ['c.customer_id', '=', $data['customer_id']];
        }

        if ($data['city'] != '') {
            $where[] = ['c.city_id', '=', "{$data['city']}"];
        }
        if ($data['query'] != '') {
            $where[] = ['c.customer_id', 'like', "%{$data['query']}%"];
        }

        if ($data['city'] == 'CN') {
            $where = [];
        }

        // 在这里查询访客发送的未读消息的条数
        $cust = ImCustomer::alias('c')
            ->leftJoin('im_records r', 'c.customer_id = r.customer_id and r.is_staff = 0')
            ->where($where)
            ->field('c.*, IFNULL((SELECT COUNT(id) FROM im_records WHERE customer_id = c.customer_id AND is_read = 0), 0) AS unread_count')
            ->group('c.customer_id')
            ->paginate([
                'list_rows' => $data['list_rows'],
                'page' => $data['page']
            ]);
        return success(0, 'success', $cust);
    }

    public function changeStatus(){

        $data = [
            'customer_id' => Request::get('customer_id', ''),
            'staff_id' => Request::get('staff_id', 'admin'),
        ];

        $validate = new Validate();
        $validate->rule([
            'customer_id' => 'require',
        ]);

        if (!$validate->check($data)) {
            return error(1, $validate->getError());
        }
        $where = [];

        if ($data['customer_id'] != '') {
            $where[] = ['customer_id', '=', $data['customer_id']];
            $where[] = ['is_read', '=', 0];
        }

        // 在这里查询访客发送的未读消息的条数
        $data = [
            'is_read' => 1,
            'staff_id' => $data['staff_id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $cust = ImRecords::where($where)->save($data);
        if ($cust) {
            return success(0, 'success', $cust);
        }
    }
}
