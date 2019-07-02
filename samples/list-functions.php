<?php

require_once "vendor/autoload.php";

use TcbManager\TcbManager;

// 1. 初始化 TcbManager
$tcbManager = TcbManager::init([
    "secretId" => "",
    "secretKey" => "",
    "envId" => ""
]);

// 2. 获得云函数管理示例
$funcManager = $tcbManager->getFunctionManager();

// 3. 调用 getFunction 获取云函数详情
$result = $funcManager->listFunctions();

// 4. 打印结果
print_r($result);

// 数据库示例
$db = $tcbManager->getDatabaseManager()->db();

$tcbManager->getDatabaseManager()->deleteCollection("users");
$db->createCollection("users");

$collection = $db->collection("users");
$countResult = $collection->count();
$collection->add(['name' => 'ben']);
$queryResult = $collection->where([
    'name'=> "ben"
])->get();

print_r($queryResult);
