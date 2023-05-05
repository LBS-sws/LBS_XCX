<?php
declare (strict_types = 1);

namespace app\customer\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Request;
class Login
{
    public function index()
    {
        $result['code'] = 0;
        $result['msg'] = '请输入用户名和密码';
        $result['data'] = null;
        if(!isset($_POST['mobile']) || !isset($_POST['password']) || !isset($_POST['type'])){
            return json($result); 
        }
        if(empty($_POST['mobile']) || empty($_POST['password']) || empty($_POST['type'])){
            return json($result); 
        }
        //获取信息
        $mobile = $_POST['mobile'];
        $password = $_POST['password'];
        $type = $_POST['type'];
        
        //验证登录
        if ($type==1) {
            $where_l['Mobile'] = $mobile;
        }elseif($type==2){
            $where_l['ContactID'] = $mobile;
        }
        $where_l['Status'] = 1;
        $user = Db::name('customercontact')->where($where_l)->find();
        if ($user) {
            if (password_verify($password,$user['Password'])) {
                if ($user['Status']==1) {
                    //验证成功，生成token
                    $token = $this->create_guid();
                    //增加token表数据
                    $get_token = Db::name('cuztoken')->where('StaffID', $user['ContactID'])->find();
                    $data_token = ['StaffID' => $user['ContactID'],'CustomerID' => $user['CustomerID'], 'token' => $token,'stamp'=>date('Y-m-d H:i:s')];
                    if ($get_token) {
                        $token_save = Db::name('cuztoken')->update($data_token);
                    }else{
                        $token_save = Db::name('cuztoken')->insert($data_token);
                    }


                    //办公室电话
                    $office = Db::name('customercompany')->alias('cc')->join('officecity oc ','cc.City=oc.City')->join('officesettings os ','oc.Office=os.Office')->where('cc.CustomerID', $user['CustomerID'])->field('os.Tel,cc.isHQ,cc.NameZH,cc.City')->find();
                    //查询(1)总店还是(0)分店
                    // $main_store = 0;
                    //返回状态
                    $result['code'] = 1;
                    $result['msg'] = '登录成功';
                    $result['data']['contactid'] = $user['ContactID'];
                    $result['data']['mobile'] = $user['Mobile'];
                    $result['data']['contactname'] = $user['ContactName'];
                    $result['data']['customerid'] = $user['CustomerID'];
                    $result['data']['NameZH'] = $office['NameZH'];
                    $result['data']['City'] = $office['City'];
                    $result['data']['officetel'] = $office['Tel'];
                    $result['data']['mainstore'] = $office['isHQ'];
                    $result['data']['token'] = $token;
                    
                }else{
                    $result['code'] = 0;
                    $result['msg'] = '账号未启用，请联系管理员';
                    $result['token'] = null;
                }
                
            }else{
                $result['code'] = 0;
                $result['msg'] = '登录失败，密码错误';
                $result['token'] = null;
            }
        }else{
            $result['code'] = 0;
            $result['msg'] = '账号错误';
            $result['token'] = null;
        }

        return json($result);
    }

    
    //token生成
    public function create_guid($namespace = '') {  
      static $guid = '';
      $uid = uniqid("", true);
      $data = $namespace;
      $data .= $_SERVER['REQUEST_TIME'];
      $data .= $_SERVER['HTTP_USER_AGENT'];
      $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
      $guid =  
          substr($hash, 0, 8) .
          '' .
          substr($hash, 8, 4) .
          '' .
          substr($hash, 12, 4) .
          '' .
          substr($hash, 16, 4) .
          '' .
          substr($hash, 20, 12);
      return strtolower($guid);
     }
     
}
