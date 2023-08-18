<?php
declare (strict_types = 1);
namespace app\customer\controller;
use app\BaseController;
use app\common\model\ImRecords;
use app\common\model\ImCustomer;
use think\App;
use think\facade\Db;
use think\facade\Request;
use think\Validate;

class Records extends BaseController
{
    protected $imRecordModel = null;
    protected $imCustomerModel = null;

    public function __construct(App $app)
    {
        $this->imRecordModel = new ImRecords;
        $this->imCustomerModel = new ImCustomer();
        parent::__construct($app);
    }

    /**
     * 获取客户信息列表
     */
    public function index()
    {
        $data = [
            'customer_id' => Request::param('customer_id', ''),
            'city' => Request::param('city', ''),
            'date' => Request::param('date', date('Y-m-d')),
            'page' => Request::get('page', 1),
            'list_rows' => Request::get('list_rows', 15),
        ];

        $validate = new Validate();
        $validate->rule([
            'customer_id' => 'require',
            'city' => 'require',
            'date' => 'require|dateFormat:Y-m-d',
        ]);

        if (!$validate->check($data)) {
            return error(1, $validate->getError());
        }


        $where = [];

        if ($data['customer_id'] != '') {
            $where[] = ['customer_id', '=', $data['customer_id']];
        }

        if ($data['city'] != '') {
            $where[] = ['city_id', '=', "{$data['city']}"];
        }

        // Query records with is_read = 0
        $unreadRecords = ImRecords::where($where)
            ->where('is_read', '=', 0)
            ->field("*, created_at as timestamp")
            ->order('id DESC')
            ->count();
        if ($data['date'] != '' && $unreadRecords > 0) {
            $where[] = ['date', '=', $data['date']];
        }
        // if ($data['city'] == 'CN') {
        //     $where = [];
        // }
        // 设置分页大小为10，显示第3页的数据
        $result = ImRecords::where($where)->field("*, created_at as timestamp")->order('id DESC')
            ->paginate([
                'list_rows'=>$data['list_rows'],  // 分页大小为10
                'page'=>$data['page']  // 显示第3页的数据
            ]);

        return success(0, 'success', $result);
    }

    public function timer()
    {
        $data = [
            'customer_id' => Request::param('customer_id', ''),
            'city' => Request::param('city', ''),
            'date' => Request::param('date', date('Y-m-d')),
            'is_staff' => Request::param('is_staff', 0),
        ];

        $validate = new Validate();
        $validate->rule([
            'customer_id' => 'require',
            'city' => 'require',
            'date' => 'require|dateFormat:Y-m-d',
        ]);

        if (!$validate->check($data)) {
            return error(1, $validate->getError());
        }


        $where = [];

        if ($data['customer_id'] != '') {
            $where[] = ['customer_id', '=', $data['customer_id']];
        }

        if ($data['city'] != '') {
            $where[] = ['city_id', '=', "{$data['city']}"];
        }

        if ($data['date'] != '') {
            $where[] = ['date', '=', $data['date']];
        }
        if($data['is_staff'] == 0){
            $where[] = ['is_staff', '=', 1];
        }else{
            $where[] = ['is_staff', '=', 0];
        }

        $threeSecondsAgo = date('Y-m-d H:i:s', strtotime('-7600 seconds'));
        $query = $this->imRecordModel->where($where)->where('created_at', '>', $threeSecondsAgo)->select();
        return success(0, 'success', $query);
    }
}
