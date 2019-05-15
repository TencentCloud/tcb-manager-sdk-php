<?php

require_once "../autoload.php";

use TcbManager\TcbManager;

// 1. 初始化 TcbManager
$tcbManager = TcbManager::init([
    "secretId" => "secretId",
    "secretKey" => "secretKey",
    "envId" => "demo-619e0a"
]);

// 2. 获得云函数管理示例
$funcManager = $tcbManager->getFunctionManager();

// 3. 调用 getFunction 获取云函数详情
$result = $funcManager->listFunctions();

// 4. 打印结果
print_r($result);
