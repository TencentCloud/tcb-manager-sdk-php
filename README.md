# tcb-manager-php

<div id="badges">

[![Build Status](https://travis-ci.org/TencentCloudBase/tcb-manager-php.svg?branch=master)](https://travis-ci.org/tencentcloudbase/tcb-manager-php)
[![Latest Stable Version](https://poser.pugx.org/tencentcloudbase/tcb-manager-php/version)](https://packagist.org/packages/tencentcloudbase/tcb-manager-php)
[![Latest Unstable Version](https://poser.pugx.org/tencentcloudbase/tcb-manager-php/v/unstable)](//packagist.org/packages/tencentcloudbase/tcb-manager-php)
[![Total Downloads](https://poser.pugx.org/tencentcloudbase/tcb-manager-php/downloads)](https://packagist.org/packages/tencentcloudbase/tcb-manager-php)

</div>

## 使用步骤

### 安装SDK

1. 【推荐】通过 `composer` 安装：

    安装 `composer`，见：https://getcomposer.org/doc/00-intro.md

    ```bash
    composer require tencentcloudbase/tcb-manager-php:
    ```

2. 手动安装源码包：

    1. 前往源码仓库下载源码包，仓库地址：https://github.com/TencentCloudBase/tcb-manager-php；
    2. 将源码包放到项目合适位置；

### 引入SDK

如果项目使用 `composer` 管理依赖，则会自动引入，可跳过此步骤

```php
require_once "/path/to/tcb-manager-php/autoload.php"
```

### 使用SDK

引用 SDK 后，便可以使用了，SDK 命名空间：`TcbManager`。

### 初始化SDK

通过腾讯云API密钥初始化：

```php
$tcbManager = TcbManager::init([
    "secretId" => "Your SecretId",
    "secretKey" => "Your SecretKey",
    "secretToken" => "Your SecretToken", // 使用临时凭证需要此字段
    "envId" => "Your envId"  // TCB环境ID，可在腾讯云TCB控制台获取
]);
```

> 注意：需要提前开通TCB服务并创建环境，否则SDK无法使用

腾讯云TCB控制台地址：https://console.cloud.tencent.com/tcb

在云函数环境下，支持免密钥初始化：

```php
$tcbManager = TcbManager::init([
    "envId" => "Your envId"
]);
```

初始化后得到一个 `TcbManager` 实例，注意，该实例是单例的，多次调用 `TcbManager::init` 只会初始化一次。

你也可以通过 `new TcbManager` 创建实例：

```php
$tcbManager = new TcbManager([
    "secretId" => "Your SecretId",
    "secretKey" => "Your SecretKey",
    "secretToken" => "Your SecretToken", // 使用临时凭证需要此字段
    "envId" => "Your envId"  // TCB环境ID，可在腾讯云TCB控制台获取
])
```

每次初始化都会得到一个全新的 `TcbManager` 实例，如果需要管理多个腾讯云账号下的 `TCB` 服务，可通过此种方式创建多个 `TcbManager` 实例。

初始化完成之后，便可以使用相关功能了。

### 完整示例

list-functions（[源码](samples/list-functions.php)）：

```php
<?php

// 使用 composer 时不需要 ../autoload.php -> tcb-manager-php/autoload.php
require_once "../autoload.php";

use TcbManager\TcbManager;

// 1. 初始化 TcbManager
$tcbManager = TcbManager::init([
    "secretId" => "Your SecretId",
    "secretToken" => "Your SecretToken", // 使用临时凭证需要此字段
    "envId" => "Your envId"  // TCB环境ID，可在腾讯云TCB控制台获取
]);

// 2. 获得云函数管理示例
$funcManager = $tcbManager->getFunctionManager();

// 3. 调用 getFunction 获取云函数详情
$result = $funcManager->getFunction("hellotcb");

// 4. 打印结果
print_r($result);
```

输出示例：

```txt
stdClass Object
(
    [TotalCount] => 14
    [Functions] => Array
        (
            [0] => stdClass Object
                (
                    [ModTime] => 2019-05-20 11:40:55
                    [Status] => Active
                    [StatusDesc] => 
                    [FunctionName] => unit_test_3q4zyU
                    [Tags] => Array
                        (
                        )

                    [AddTime] => 2019-05-20 11:40:50
                    [Runtime] => Nodejs8.9
                    [Namespace] => demo-619e0a
                    [FunctionId] => lam-0mykhmki
                    [Description] => this is new description.
                )

            ...
        )

    [RequestId] => 5caec8d9-88c1-4c29-b776-67e4a6a2823e
)
```

## Docs

* [Overview](docs/overview.md)
* [Initialization](docs/initialization.md)
* [Cloud Function](docs/cloudfunction.md)
* [Cloud Database](docs/clouddatabase.md)
* [Cloud Storage](docs/cloudstorage.md)
