<?php
declare (strict_types = 1);
namespace app\api\controller;
use app\BaseController;
use app\technician\model\AnalyseReport;
use app\technician\model\CustomerCompany;
use think\App;
use think\facade\Db;
use think\facade\Request;

class Customer extends BaseController
{
    protected $customerCompanyModel = '';
    protected $analyseReport = null;

    public function __construct(App $app)
    {
        $this->customerCompanyModel = new CustomerCompany;
        $this->analyseReport = new AnalyseReport();

        parent::__construct($app);
    }

    /**
     * 获取客户信息列表
     */
    public function index()
    {
        $search = Request::param('q', ''); // 获取搜索关键字
        $customer = Request::param('cust', ''); // cust
        $city = Request::param('city', ''); // 获取城市
        $daterange = Request::param('daterange', ''); // 获取日期范围
        $where = [];

        if ($customer != '') {
            $where[] = ['customer_id', '=', $customer];
        }

        if ($city != '') {
            $where[] = ['city', '=', $city]; // 添加城市查询条件
        }

        if ($search != '') {
            $where[] = ['customer_name', 'like', '%' . $search . '%']; // 添加搜索关键字查询条件
        }

        if ($daterange != '') {
            $date_de = json_decode($daterange);
            $where[] = ['date', '>=', date('Y-m', strtotime($date_de[0]))]; // 添加日期范围查询条件
            $where[] = ['date', '<=', date('Y-m', strtotime($date_de[1]))];
        }

        if ($city == 'CN') {
            $where = []; // 如果城市为CN，则清空查询条件
        }

        $cust = $this->analyseReport->field("customer_id,customer_name,date,city,url_id,url")
            ->where($where)
            ->paginate(); // 查询客户信息列表

        return success(0, 'success', $cust); // 返回操作结果和数据
    }

    /**
     * 搜索客户信息
     */
    public function search()
    {
        $search = Request::param('q', ''); // 获取搜索关键字
        $city = Request::param('city', ''); // 获取城市
        $where = [];

        if ($city != '') {
            $where[] = ['city', '=', $city]; // 添加城市查询条件
        }

        if ($search != '') {
            $where[] = ['customer_name', 'like', '%' . $search . '%']; // 添加搜索关键字查询条件
        }

        $cust = $this->analyseReport->field("customer_id as label,customer_name as value,date,city,url_id,url")
            ->where($where)
            ->limit(10)
            ->select()
            ->toArray(); // 查询客户信息

        return success(0, 'success', $cust); // 返回操作结果和数据
    }

    /**
     * 获取客户报告的PDF文件
     */
    public function getPdf($month = '2023-04', $cust = '', $custzh = '', $city = '', $city_id = '', $is_cust = 0, $is_force = 0)
    {
        if ($month == '' || $cust == '') {
            return error(-1, '输入参数有误', []); // 如果输入参数有误，则返回错误信息
        }

        if (!empty($city_id)) {
            $city_ret = Db::query("select e.Text from enums as e left join officecity as o on o.Office=e.EnumID where o.City= ? and e.EnumType=8;", [$city_id]);
            $city = $city_ret[0]['Text']; // 如果城市ID不为空，则查询城市名称
        }

        $reportId = AnalyseReport::where('date',$month)->where('customer_id',$cust)->findOrEmpty();

        $file_path = 'analyse/' . $month . '/' . $reportId['url_id'] . '.pdf'; // 构建PDF文件路径
        $domain = Request::domain() . '/';

        if (is_file($file_path)) { // 如果PDF文件已存在，则返回文件URL
            $url = $domain . $file_path;
            return success(0, 'success', $url);
        } else {
            $res = new Analyse();
            $report = $res->index($month, $cust, $city); // 如果PDF文件不存在，则生成PDF文件

            if ($report) {
                $url = $domain . $file_path;
                return success(0, 'success', $url); // 返回PDF文件URL
            }
        }

        return error(-1, '获取PDF文件失败', []); // 如果获取PDF文件失败，则返回错误信息
    }
}
