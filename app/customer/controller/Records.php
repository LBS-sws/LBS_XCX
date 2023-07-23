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

        if ($data['city'] == 'CN') {
            $where = [];
        }
        $cust = $this->imRecordModel->field("user_content as userContent,bot_content as botContent,customer_id as customerId,city_id as cityId,customer_name as customerName,date")
            ->where($where)
            ->paginate();

        return success(0, 'success', $cust);
    }
}