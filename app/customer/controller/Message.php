<?php
declare (strict_types = 1);
namespace app\customer\controller;
use app\BaseController;
use app\common\model\ImCustomer;
use app\common\model\ImRecords;
use think\App;
use think\facade\Db;
use think\facade\Request;
use think\Validate;

class Message extends BaseController
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
    /**
     * 获取客户信息列表
     */
    public function index()
    {
        $data = [
            'customer_id' => Request::param('customer_id', ''),
            'city_id' => Request::param('city_id', ''),
            'date' => Request::param('date', date('Y-m-d')),
            'is_staff' => Request::param('is_staff', 0),
            'customer_name' => Request::param('customer_name', 0),
            'content' => Request::param('content', ''),
        ];

        $validate = new Validate();
        $validate->rule([
            'customer_id' => 'require',
            'city_id' => 'require',
            'date' => 'require|dateFormat:Y-m-d',
        ]);

        if (!$validate->check($data)) {
            return error(1, $validate->getError());
        }
        Db::startTrans();
        try {
            if($data['is_staff'] == 0) {
                $conditions = ['city_id' => $data['city_id'], 'customer_id' => $data['customer_id']];
                $hasCust = $this->imCustomerModel->where($conditions)->findOrEmpty();
                if (!$hasCust) {
                    $data = [
                        'city_id' => $data['city_id'],
                        'customer_id' => $data['customer_id'],
                        'customer_name' => $data['customer_name'],
                        'online_at' => date('Y-m-d H:i:s'),
                        'online_flag' => 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $retCust = $this->imCustomerModel->insert($data);
                }
            }
            $data = [
                'content' => $data['content'],
                'city_id' => $data['city_id'],
                'customer_id' => $data['customer_id'],
                'is_staff' => $data['is_staff'],
                'customer_name' => $data['customer_name'],
                'date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $this->imRecordModel->insert($data);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return error(-1, 'error',$e->getMessage());
        }
        return success(0, 'ok');
    }
}
