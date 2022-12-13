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
            if ($user['Status']==1 || $user['Status']==2 || $user['Status']==5) {
                //获取城市
                $office = Db::name('enums')->alias('e')->join('officecity o ','o.Office=e.EnumID ')->join('officesettings os ','o.Office=os.Office')->where('o.City', $user['City'])->where('e.EnumType', 8)->field('e.Text,os.Tel')->find();
                //验证成功，生成token
                $token = $this->create_guid();
                //增加token表数据
                $get_token = Db::name('token')->where('StaffID', $user['StaffID'])->find();
                $data_token = ['StaffID' => $staffid, 'token' => $token,'stamp'=>date('Y-m-d H:i:s')];
                if ($get_token) {
                    $token_save = Db::name('token')->update($data_token);
                }else{
                    $token_save = Db::name('token')->insert($data_token);
                }
                //回传新U登录状态
                $arr = array('staffid'=>$staffid,'password'=>$password,'token'=>$token);
                $xinu_data = $this->curl_post('https://app.lbsapps.cn/web/ajax/editJobToken.php',$arr);
                $xinu = json_decode($xinu_data,true);
                 if($xinu['code']==1){
                    //返回状态
                    $result['code'] = 1;
                    $result['msg'] = '登录成功';
                    $result['data']['staffname'] = $user['StaffName'];
                    $result['data']['city'] = $office['Text'];
                    $result['data']['token'] = $token;
                    $result['data']['officetel'] = $office['Tel'];
                    $result['xinu'] = $xinu_data;
                }else{
                   //返回数据
                    $result['code'] = 0;
                    $result['msg'] = '新U回传失败';
                    $result['xinu'] = $xinu_data; 
                }
                
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
    public function curl_post($url , $data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        if($output === false)
        {
            $output = curl_error($ch);
        }
        curl_close($ch);
        return $output;
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
