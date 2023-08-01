<?php

namespace app\swoole\service;

use app\swoole\service\SwooleDb;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService
{
    protected $server; // WebSocket 服务器实例
    protected $clients = []; // 客户端数组
    protected $masterPidFile = '/data/web/lbs_xcx/runtime/swoole_master.pid'; // 主进程ID文件路径
    protected $redis; // Redis 实例
    protected $db; // SwooleDb 实例
    public function __construct()
    {

        // 加载配置文件
        $config = require '/data/web/lbs_xcx/config/swoole.php';

        $this->db = new SwooleDb(
            $config['db_host'],
            $config['db_name'],
            $config['db_username'],
            $config['db_password']
        );

        // 创建主进程
        $this->createMasterProcess();
        // 设置跨域请求头
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");

        // 创建WebSocket服务器实例
        $this->server = new Server("0.0.0.0", 9501);
        $ln = "\n".<<<EOF

	                   _ooOoo_
	                  o8888888o
	                  88" . "88
	                  (| -_- |)
	                  O\  =  /O
	               ____/`---'\____
	             .'  \\|     |//  `.
	            /  \\|||  :  |||//  \
	           /  _||||| -:- |||||-  \
	           |   | \\\  -  /// |   |
	           | \_|  ''\-/''  |   |
	           \  .-\__  `-`  ___/-. /
	         ___`. .'  /-.-\  `. . __
	      ."" '<  `.___\_<|>_/___.'  >'"".
	     | | :  `- \`.;`\ _ /`;.`/ - ` : | |
	     \  \ `-.   \_ __\ /__ _/   .-` /  /
	======`-.____`-.___\_____/___.-`____.-'======
	              
EOF;
        echo $ln."\n";
        $this->writeLog($ln);


        // 创建 Redis 实例
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);

        // 设置心跳检测间隔和超时（以秒为单位）
        $this->server->set([
            'heartbeat_check_interval' => 3, // 每隔60秒检查一次
            'heartbeat_idle_time' => 600, // 连接空闲时间600秒（10分钟）
        ]);

        // 注册WebSocket服务器事件回调
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);

        // 每隔一定时间（例如10秒），调用checkMasterAndChildProcess方法
        $this->server->tick(10000, function () {
            $this->checkMasterAndChildProcess();
        });

        // 在构造函数中创建定时器
        $this->server->tick(60000, function () {
            $this->checkAndCleanInactiveConnections($this->server);
        });

    }
    // 添加析构函数，在服务器结束时关闭Redis连接
    public function __destruct()
    {
        if ($this->redis) {
            $this->redis->close();
        }
    }

    protected function createMasterProcess()
    {
        $process = new \swoole_process(function () {
            // 主进程逻辑
            // 这里可以添加其他需要在主进程中执行的代码

            // 写入主进程ID到文件
            file_put_contents($this->masterPidFile, getmypid());

            // 保持主进程运行
            while (true) {
                \swoole_process::wait(); // 等待子进程退出，防止僵尸进程
                sleep(1); // 每隔1秒检查一次子进程状态
            }
        });

        // 启动主进程
        $process->start();
    }


    public function checkMasterAndChildProcess()
    {
        // 检查主进程是否存在
        $masterPid = $this->getMasterPid();
        if ($masterPid > 0 && !\swoole_process::kill($masterPid, 0)) {
            echo "主进程不存在\n";
            // 可以在这里进行相应处理，比如重启主进程等操作
        }

        // 检查子进程是否存在（假设子进程的PID保存在子进程ID文件中）
        $childPid = $this->getChildPid();
        if ($childPid > 0 && !\swoole_process::kill($childPid, 0)) {
            echo "子进程不存在\n";
            // 可以在这里进行相应处理，比如重启子进程等操作
        }
    }

    protected function getMasterPid()
    {
        if (file_exists($this->masterPidFile)) {
            return intval(file_get_contents($this->masterPidFile));
        }

        return 0;
    }

    protected function getChildPid()
    {
        // 假设子进程的PID保存在子进程ID文件中
        $childPidFile = '/data/web/lbs_xcx/runtime/swoole_child.pid';
        if (file_exists($childPidFile)) {
            return intval(file_get_contents($childPidFile));
        }

        return 0;
    }

    public function start()
    {
        // 启动WebSocket服务器
        $this->server->start();
    }

    public function onCustomerConnected($city_id, $customer_id, $customer_name)
    {
        try {
            $this->db->connect();
            $this->db->beginTransaction();

            $conditions = ['city_id' => $city_id, 'customer_id' => $customer_id];
            $result = $this->db->table('im_customers')->where($conditions)->get();
            if($result == NULL || $result == ''){
                $data = [
                    'city_id' => $city_id,
                    'customer_id' => $customer_id,
                    'customer_name' => $customer_name,
                    'online_at' => date('Y-m-d H:i:s'),
                    'online_flag' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $this->db->table('im_customers')->create($data);
            }else{
                $customer_id = $result['customer_id'];
                $data = [
                    'online_at' => date('Y-m-d H:i:s'),
                    'online_flag' => 1
                ];
                $this->db->table('im_customers')->where($conditions)->update($data);
            }

            $this->db->commit();
            $this->db->disconnect();
        } catch (\Exception $e) {
            echo "操作失败：" . $e->getMessage();
        }
    }





    public function onOpen(Server $server, Request $request)
    {
        $query = urldecode($request->server['query_string']);
        $params = json_decode($query, true);

        $city_id = $params['city_id'] ?? null;
        $customer_id = $params['customer_id'] ?? null;
        $isStaff = $params['is_staff'] ?? 0;
        $customer_name = $params['customer_name'] ?? '';
//
//        var_dump("customer_name:");
//        var_dump($customer_name);
//
//        echo  "当前城市：";
//        echo  $city_id;
//        // 调用onCustomerConnected处理新客户连接
//        var_dump($city_id);
//        var_dump($customer_id);
//        var_dump($isStaff);
//        var_dump($customer_name);
        if($isStaff == 0 && $customer_id != NULL){
            $this->onCustomerConnected($city_id, $customer_id, $customer_name);
        }

//        echo "isStaff:" . $isStaff;

        $this->clients[$request->fd] = [
            'fd' => $request->fd,
            'city_id' => $city_id,
            'customer_id' => $customer_id,
            'is_staff' => $isStaff,
            'customer_name' => $customer_name,
            'lastHeartbeat' => time(), // 初始化最后心跳时间戳
        ];


        // Store the client information in Redis
        if($isStaff == 0) {
            $this->redis->hSet($isStaff . ':' . $customer_id . ':' . $city_id, $isStaff . ':' . $customer_id . ':' . $city_id, json_encode([
                'fd' => $request->fd,
                'customer_id' => $customer_id,
                'city_id' => $city_id,
                'is_staff' => $isStaff,
            ]));
        }
        if($isStaff == 1){
            $this->redis->hSet($isStaff . ':'  . $city_id, $isStaff . ':' . $city_id, json_encode([
                'fd' => $request->fd,
//                'customer_id' => $customer_id,
                'city_id' => $city_id,
                'is_staff' => $isStaff,
            ]));
        }

        // 在连接成功时向客户端发送服务是否正常的状态信息
        $this->checkMasterAndChildProcess(); // 检查主进程和子进程是否存在

//        $status = [
//            'masterProcessExists' => $this->getMasterPid() > 0,
//            'childProcessExists' => $this->getChildPid() > 0,
//        ];
//        $server->push($request->fd, json_encode($status));
        $this->writeLog("连接信息：城市ID：{$city_id}，客户ID：{$customer_id}，是否客服：{$isStaff}");

        if ($isStaff) {
            echo "客服已连接（城市 ID: {$city_id}，客户 ID: {$customer_id}，fd: {$request->fd})\n";
        } else {
            echo "访客已连接（城市 ID: {$city_id}，客户 ID: {$customer_id}，fd: {$request->fd})\n";

            // 将消息转发给相同城市ID的客服
            foreach ($this->clients as $fd => $client) {
//                if ($this->clients[$fd]['is_staff'] == 1 && $this->clients[$fd]['city_id'] === $city_id) {
//                    $arr = [
//                        "recordId" => 0,
//                        "titleId" => 0,
//                        "content" => "访客已连接（城市 ID: {$city_id}，客户 ID: {$customer_id}）",
//                        "userId" => 0,
//                        "is_staff" => 1,
//                    ];
//                    $server->push($request->fd, json_encode($arr, 256));
//                }
            }

            // 只向新连接的访客客户端发送欢迎消息
            if (!isset($this->clients[$request->fd]['sentWelcome'])) {

//                $arr = [
//                    "content" => "{$customer_name}您好，欢迎使用史伟莎售后客服！，你已成功连接。城市 ID：{$city_id}",
//                    "recordId" => 0,
//                    "titleId" => 0,
//                    "is_staff" => 1,
//                    "userId" => 0
//                ];
//                $server->push($request->fd, json_encode($arr, 256));
                $this->clients[$request->fd]['sentWelcome'] = true;
            }
        }
    }

    protected function checkAndCleanInactiveConnections(Server $server)
    {
        $now = time();
        $inactiveTimeout = 600; // 600 seconds (10 minutes) of inactivity allowed

        foreach ($this->clients as $fd => $client) {
            $lastHeartbeatTime = $client['lastHeartbeat'];
            if ($now - $lastHeartbeatTime >= $inactiveTimeout) {
                // Connection is inactive for too long, close the connection
                $server->close($fd);
                unset($this->clients[$fd]);
                echo "关闭不活跃连接：fd{$fd}\n";
            }
        }
    }

    public function recordMessage( $content, $city_id, $customer_id, $is_staff, $customer_name, $staff_id)
    {
        try {

            $this->db->connect();
            $this->db->beginTransaction();

            $data = [
                'content' => $content,
                'city_id' => $city_id,
                'customer_id' => $customer_id,
                'staff_id' => $staff_id,
                'is_staff' => $is_staff,
                'customer_name' => $customer_name,
                'date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->table('im_records')->create($data);
            $this->db->commit();
            $this->db->disconnect();
        } catch (\Exception $e) {
            $this->db->rollback();
            echo "操作失败：" . $e->getMessage();
        }
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $data = json_decode($frame->data, true);
        // 日志记录：收到消息
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            // 无效的 JSON 数据，根据需求处理
            echo "JSON 解码错误：" . json_last_error_msg();
            return;
        }

        // 处理心跳消息
        if (isset($data['type']) && $data['type'] === 'heartbeat') {
            $city_id = $this->clients[$frame->fd]['city_id'] ?? null;
            $customer_id = $this->clients[$frame->fd]['customer_id'] ?? null;

            if ($city_id !== null && $customer_id !== null) {
                foreach ($this->clients as $fd => $client) {
                    if ($fd === $frame->fd) {
                        $this->clients[$fd]['lastHeartbeat'] = time();
                    }
                }
            }
            return;
        }
        $this->writeLog("收到消息：{$frame->data}");

        // 检查客户端数组是否为空或未正确初始化
        if (!is_array($this->clients) || empty($this->clients)) {
            // 记录日志或根据需求处理客户端数组为空的情况
            return;
        }

        // 获取发送消息的客户端信息
        $senderCityId = $data['city_id'];
        $senderCustomerId = $data['customer_id'];
        $senderIsStaff = $data['is_staff'];
        $time = date('Y-m-d H:i:s');

        // 这个是获取客服人员的信息
        $senderDataS = $this->redis->hGet('1:' . $senderCityId, '1:' . $senderCityId);
        // 这个是获取访客的信息
        $senderDataV = $this->redis->hGet('0:' . $senderCustomerId . ':' . $senderCityId, '0:' . $senderCustomerId . ':' . $senderCityId);
        $staff_id = $data['staff_id'] ?? '';
        $this->recordMessage($data['content'],$data['city_id'],$data['customer_id'],$data['is_staff'],$data['customer_name'],$staff_id);

        // 判断如果发送消息的是客服人员，则查询Redis中是否存在isStaff = 0的访客信息，并将消息转发给他们
        if ($senderIsStaff === 1 && $senderDataV !== false) {
            $senderInfo = json_decode($senderDataV, true);

            // Now you can access the sender's data
            $senderFd = $senderInfo['fd'];

            $arr = [
                "content" => $data['content'],
                "recordId" => 0,
                "titleId" => 0,
                "userId" => 0,
                "is_staff" => 1
            ];
            $this->writeLog("is_staff=1===》消息转发给：{$senderFd}");

            $server->push($senderFd, json_encode($arr, 256));
        }

        // 判断如果发送消息的是访客，则查询Redis中是否存在城市ID和访客ID匹配的客服信息，并将消息转发给他们
        if ($senderIsStaff === 0 && $senderDataS !== false) {
            $senderInfo = json_decode($senderDataS, true);
            $senderFd = $senderInfo['fd'];

            $arr_vis = [
                "recordId" => 0,
                "titleId" => 0,
                "cityId" => $data['city_id'],
                "customer_id" => $data['customer_id'],
                "content" => $data['content'],
                "userId" => 0,
                "is_staff" => 0,
                "fromCustomer" => 1
            ];
            $this->writeLog("is_staff=0===》消息转发给：{$senderFd}");

            $server->push($senderFd, json_encode($arr_vis, 256));
        }

    }



    public function onClose(Server $server, $fd)
    {
        echo "客户端 {$fd} 已关闭\n";
        foreach ($this->clients as $clientData) {
            if ($clientData['fd'] === $fd) {
                $this->redis->hDel($clientData['is_staff'] . ':' .$clientData['customer_id'] . ':' . $clientData['city_id'], $clientData['is_staff'] . ':' .$clientData['customer_id'] . ':' . $clientData['city_id']);
                break;
            }
        }
        $this->writeLog("客户端 {$fd} 已关闭");

        unset($this->clients[$fd]);
    }

    public function writeLog($message, $filename = 'swoole')
    {
        $logDir = '/data/web/lbs_xcx/runtime/swoole/';
        $logFile = $logDir . date('Y-m-d') . '_' . $filename . '.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    }

}
