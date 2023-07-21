<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$server = new \Swoole\WebSocket\Server("0.0.0.0", 9501);
$clients = []; // 初始化客户端数组

// 设置心跳检测间隔和超时（以秒为单位）
$server->set([
    'heartbeat_check_interval' => 60, // 每隔60秒检查一次
    'heartbeat_idle_time' => 600,     // 连接空闲时间600秒（10分钟）
]);

$server->on('open', function (\Swoole\WebSocket\Server $server, $request) use (&$clients) {
    echo "服务器: 握手成功，fd{$request->fd}\n";

    $cityId = $request->get['cityId'] ?? null;
    $customerId = $request->get['customerId'] ?? null;
    $isStaff = $request->get['isStaff'] ?? 0;
    $customerName = $request->get['customerName'] ?? 0;

    echo "isStaff:" . $isStaff;

    $clients[$request->fd] = [
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

        // Forward the message to staff with the same city ID
        foreach ($clients as $fd => $client) {
            if ($clients[$fd]['isStaff'] == 1 && $clients[$fd]['cityId'] === $cityId) {
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
        if (!isset($clients[$request->fd]['sentWelcome'])) {
            $arr = [
                "botContent" => "{$customerId}您好，欢迎使用史伟莎售后客服！，你已成功连接。城市 ID：{$cityId}",
                "recordId" => 0,
                "titleId" => 0,
                "userContent" => "",
                "userId" => 0
            ];
            $server->push($request->fd, json_encode($arr, 256));
            $clients[$request->fd]['sentWelcome'] = true;
        }
    }
});


$server->on('message', function (\Swoole\WebSocket\Server $server, $frame) use (&$clients) {
    $data = json_decode($frame->data, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        // 无效的 JSON 数据，根据需求处理
        echo "JSON 解码错误：" . json_last_error_msg();
        return;
    }

    // 处理心跳消息
    if (isset($data['type']) && $data['type'] == 'heartbeat') {
        $cityId = $clients[$frame->fd]['cityId'] ?? null;
        $customerId = $clients[$frame->fd]['customerId'] ?? null;

        if ($cityId !== null && $customerId !== null) {
            foreach ($clients as $fd => $client) {
                if ($fd === $frame->fd) {
                    $clients[$fd]['lastHeartbeat'] = time();
                }
            }
        }
        return;
    }

    // 检查客户端数组是否为空或未正确初始化
    if (!is_array($clients) || empty($clients)) {
        // 记录日志或根据需求处理客户端数组为空的情况
        return;
    }
    // 其余的消息处理逻辑在这里...

    // 获取发送消息的客户端信息
    $senderCityId = $clients[$frame->fd]['cityId'];
    $customerId = $clients[$frame->fd]['customerId'];
    $isStaff = $clients[$frame->fd]['isStaff'];
    $time = date('Y-m-d H:i:s');
    // 广播消息给所有连接的客户端
    foreach ($server->connections as $fd) {
        // 访客发送消息给客服
        if ($isStaff == 0 && $data['cityId'] == $senderCityId && $data['customerId'] == $customerId) {
            $arr = [
                "botContent" => "",
                "recordId" => 0,
                "titleId" => 0,
                "userContent" => "[{$time}]"."\n"."{$data['userContent']}",
                "userId" => 0,
                "fromCustomer" => 1
            ];
            $server->push($fd, json_encode($arr, 256));
        }
        // 客服发送消息给访客
        if ($isStaff == 1 && $clients[$fd]['isStaff'] == 0 && $data['cityId'] == $senderCityId) {
            $arr = [
                "botContent" => "[{$time}]"."\n"."{$data['botContent']}",
                "recordId" => 0,
                "titleId" => 0,
                "userContent" => "",
                "userId" => 0
            ];
            $server->push($fd, json_encode($arr, 256));
        }
    }
});

$server->on('close', function ($server, $fd) use (&$clients) {
    echo "客户端 {$fd} 已关闭\n";
    unset($clients[$fd]);
});

$server->start();
