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

    public function __construct()
    {
        // 设置跨域请求头
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");

        // 创建WebSocket服务器实例
        $this->server = new Server("0.0.0.0", 9501);

        // 设置心跳检测间隔和超时（以秒为单位）
        $this->server->set([
            'heartbeat_check_interval' => 60, // 每隔60秒检查一次
            'heartbeat_idle_time' => 600, // 连接空闲时间600秒（10分钟）
        ]);

        // 注册WebSocket服务器事件回调
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);
    }

    public function start()
    {
        // 启动WebSocket服务器
        $this->server->start();
    }

    public function onCustomerConnected($cityId, $customerId, $customerName)
    {
        try {
            $db = new SwooleDb('127.0.0.1', 'lbs_xcx', 'lbs_xcx', 'A3hWiMeDtEwBcFKY');
            $db->connect();
            $db->beginTransaction();

            $conditions = ['city_id' => $cityId, 'customer_id' => $customerId];
            $result = $db->table('im_customers')->where($conditions)->get();

            if ($result) {
                $customerId = $result['customer_id'];
                $data = [
                    'online_at' => date('Y-m-d H:i:s'),
                    'online_flag' => 1
                ];
                $db->table('im_customers')->where($conditions)->update($data);
            } else {
                $data = [
                    'city_id' => $cityId,
                    'customer_id' => $customerId,
                    'customer_name' => $customerName,
                    'online_at' => date('Y-m-d H:i:s'),
                    'online_flag' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $db->table('im_customers')->create($data);
            }

            $db->commit();
            $db->disconnect();
        } catch (\Exception $e) {
            echo "操作失败：" . $e->getMessage();
        }
    }





    public function onOpen(Server $server, Request $request)
    {
        echo "服务器: 握手成功，fd{$request->fd}\n";

        $cityId = $request->get['cityId'] ?? null;
        $customerId = $request->get['customerId'] ?? null;
        $isStaff = $request->get['isStaff'] ?? 0;
        $customerName = $request->get['customerName'] ?? 0;

        echo  "当前城市：";
        echo  $cityId;
        // 调用onCustomerConnected处理新客户连接
        $this->onCustomerConnected($cityId, $customerId, $customerName);

        echo "isStaff:" . $isStaff;

        $this->clients[$request->fd] = [
            'fd' => $request->fd,
            'cityId' => $cityId,
            'customerId' => $customerId,
            'isStaff' => $isStaff,
            'lastHeartbeat' => time(), // 初始化最后心跳时间戳
        ];

        if ($isStaff) {
            echo "客服已连接（城市 ID: {$cityId}，客户 ID: {$customerId}，fd: {$request->fd})\n";
        } else {
            echo "访客已连接（城市 ID: {$cityId}，客户 ID: {$customerId}，fd: {$request->fd})\n";

            // 将消息转发给相同城市ID的客服
            foreach ($this->clients as $fd => $client) {
                if ($this->clients[$fd]['isStaff'] == 1 && $this->clients[$fd]['cityId'] === $cityId) {
                    $arr = [
                        "botContent" => "",
                        "recordId" => 0,
                        "titleId" => 0,
                        "userContent" => "访客已连接（城市 ID: {$cityId}，客户 ID: {$customerId}）",
                        "userId" => 0
                    ];
                    $server->push($fd, json_encode($arr, 256));
                }
            }

            // 只向新连接的访客客户端发送欢迎消息
            if (!isset($this->clients[$request->fd]['sentWelcome'])) {
                $arr = [
                    "botContent" => "{$customerId}您好，欢迎使用史伟莎售后客服！，你已成功连接。城市 ID：{$cityId}",
                    "recordId" => 0,
                    "titleId" => 0,
                    "userContent" => "",
                    "userId" => 0
                ];
                $server->push($request->fd, json_encode($arr, 256));
                $this->clients[$request->fd]['sentWelcome'] = true;
            }
        }
    }

    public function recordMessage($botContent, $userContent, $cityId, $customerId, $isStaff, $customerName)
    {
        try {
            $db = new SwooleDb('127.0.0.1', 'lbs_xcx', 'lbs_xcx', 'A3hWiMeDtEwBcFKY');
            $db->connect();
            $db->beginTransaction();

            $data = [
                'bot_content' => $botContent,
                'user_content' => $userContent,
                'city_id' => $cityId,
                'customer_id' => $customerId,
                'isStaff' => $isStaff,
                'customer_name' => $customerName,
                'date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $db->table('im_records')->create($data);

            $db->commit();
            $db->disconnect();
        } catch (\Exception $e) {
            $db->rollback();
            echo "操作失败：" . $e->getMessage();
        }
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $data = json_decode($frame->data, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            // 无效的 JSON 数据，根据需求处理
            echo "JSON 解码错误：" . json_last_error_msg();
            return;
        }

        // 处理心跳消息
        if (isset($data['type']) && $data['type'] == 'heartbeat') {
            $cityId = $this->clients[$frame->fd]['cityId'] ?? null;
            $customerId = $this->clients[$frame->fd]['customerId'] ?? null;

            if ($cityId !== null && $customerId !== null) {
                foreach ($this->clients as $fd => $client) {
                    if ($fd === $frame->fd) {
                        $this->clients[$fd]['lastHeartbeat'] = time();
                    }
                }
            }
            return;
        }

        // 检查客户端数组是否为空或未正确初始化
        if (!is_array($this->clients) || empty($this->clients)) {
            // 记录日志或根据需求处理客户端数组为空的情况
            return;
        }

        // 其余的消息处理逻辑在这里...

        // 获取发送消息的客户端信息
        $senderCityId = $this->clients[$frame->fd]['cityId'];
        $customerId = $this->clients[$frame->fd]['customerId'];
        $isStaff = $this->clients[$frame->fd]['isStaff'];
        $customerName = $this->clients[$frame->fd]['customerName'];
        $time = date('Y-m-d H:i:s');

        // 广播消息给所有连接的客户端
        foreach ($server->connections as $fd) {
            // 访客发送消息给客服
            if ($isStaff == 0 && $data['cityId'] == $senderCityId && $data['customerId'] == $customerId) {
                $arr = [
                    "botContent" => "",
                    "recordId" => 0,
                    "titleId" => 0,
                    "userContent" => "[{$time}]\n{$data['userContent']}",
                    "userId" => 0,
                    "fromCustomer" => 1
                ];
                $server->push($fd, json_encode($arr, 256));
                $this->recordMessage($data['botContent'], $data['userContent'], $data['cityId'], $customerId, $isStaff, $customerName);

            }
            // 客服发送消息给访客
            var_dump($data);
            if ($isStaff == 1 && $this->clients[$fd]['isStaff'] == 0 && $data['cityId'] == $senderCityId) {
                $arr = [
                    "botContent" => "[{$time}]\n{$data['botContent']}",
                    "recordId" => 0,
                    "titleId" => 0,
                    "userContent" => "",
                    "userId" => 0
                ];
                $server->push($fd, json_encode($arr, 256));
            }
        }
    }

    public function onClose(Server $server, $fd)
    {
        echo "客户端 {$fd} 已关闭\n";
        unset($this->clients[$fd]);
    }
}
