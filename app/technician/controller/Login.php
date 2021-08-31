<?php
declare (strict_types = 1);

namespace app\technician\controller;
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
        if(!isset($_POST['staffid']) || !isset($_POST['password'])){
            return json($result); 
        }
        if(empty($_POST['staffid']) || empty($_POST['password'])){
            return json($result); 
        }
        //获取信息
        $staffid = $_POST['staffid'];
        $password = $_POST['password'];
        //验证登录
        $user = Db::name('staff')->where('StaffID', $staffid)->where('Password', $password)->find();
        if ($user) {
            if ($user['Status']==1 || $user['Status']==2) {
                //获取城市
                $office = Db::name('enums')->alias('e')->join('officecity o ','o.Office=e.EnumID ')->where('o.City', $user['City'])->where('e.EnumType', 8)->find();
                //验证成功，生成token
                $token = $this->create_guid();
                //增加token表数据
                $data_token = ['StaffID' => $staffid, 'token' => $token,'stamp'=>date('Y-m-d H:i:s')];
                $token_save = Db::name('token')->save($data_token);
                //返回状态
                $result['code'] = 1;
                $result['msg'] = '登录成功';
                $result['data']['staffname'] = $user['StaffName'];
                $result['data']['city'] = $office['Text'];
                $result['data']['token'] = $token;
                
            }else{
                $result['code'] = 0;
                $result['msg'] = '账号未启用，请联系管理员';
                $result['token'] = null;
            }
            
        }else{
            $result['code'] = 0;
            $result['msg'] = '登录失败，用户名或密码错误';
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
