<?php

// 加载自动加载文件
require __DIR__ . '/../vendor/autoload.php';

use think\App;
use app\swoole\service\WebSocketService;
// Create an instance of the App
$app = new App();
// Bind the WebSocketService to the App container
$app->bind('WebSocketService', WebSocketService::class);

// Start the WebSocketService
$app->make('WebSocketService')->start();
