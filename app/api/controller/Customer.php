<?php
declare (strict_types = 1);
namespace app\api\controller;
use app\BaseController;
use app\technician\model\AnalyseReport;
use app\technician\model\CustomerCompany;
use think\App;
use think\facade\Db;


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

    public function index()
    {
        $search = $_GET['q'] ?? '';
        $city = $_GET['city'] ?? '';
//        $city_en = Db::query("select GROUP_CONCAT(City) as citys from enums as e left join officecity as o on o.Office=e.EnumID where e.Text= ? and e.EnumType = 8
//",[$city]);
        $where = [];
        if(isset($city) && $city!= ''){
            $where[]=['city','=',$city];
        }
        if(isset($search) && $search!= ''){
            $where[]=[['customer_id','like','%'.$search.'%']];
        }
        if(isset($city) && $city == 'CN' ){
            $where=[];
        }
        $cust = $this->analyseReport->field("customer_id,customer_name,date,city")->where($where)->paginate();

        return success(0,'success',$cust);
    }

    public function search()
    {
        $search = $_GET['q'] ?? '';
        $city = $_GET['city'] ?? '';
        $where = [];
        if(isset($city) && $city!= ''){
            $where[]=['city','=',$city];
        }
        if(isset($city) && $city == 'CN' ){
            $where=[];
        }
        if(isset($search) && $search!= ''){
            $where[]=[['customer_name','like','%'.$search.'%']];
        }
        $cust = $this->analyseReport->field("customer_id as label,customer_name as value,date,city")->where($where)->limit(10)->select()->toArray();
        return success(0,'success',$cust);
    }

    public function getPdf($month = '2023-04',$cust = '',$custzh = '',$city = '',$city_id = '',$is_cust = 0,$is_force = 0){
        if($month == '' || $cust == '' ){
            return error(-1,'输入参数有误',[]);
        }
        if(!empty($city_id)){
            $city_ret = Db::query("select e.Text from enums as e left join officecity as o on o.Office=e.EnumID where o.City= ? and e.EnumType=8
;",[$city_id]);
            $city = $city_ret[0]['Text'];
        }
        $where = [
            'customer_id' =>$cust,
            'date' =>$month,
            'city' =>$city,
        ];
        $cust_info = $this->analyseReport->field("customer_id,customer_name,date,city,url_id")->where($where)->findOrEmpty()->toArray();
        $file_path = 'analyse/'.$month.'/'.$cust_info['url_id'].'.pdf';
        if (is_file($file_path)) {
            $domain = $this->request->domain().'/';
            $url = $domain.$file_path;
            //有报告就返回，没返回就
            return success(0,'success',$url);
        } else {
            $res =  new Analyse();
            $report = $res->index($month,$cust,$city,$cust_info['url_id']);
            if($report){
                $domain = $this->request->domain().'/';
                $url = $domain.$file_path;
                //有报告就返回，没返回就
                return success(0,'success',$url);
            }

        }
    }

}