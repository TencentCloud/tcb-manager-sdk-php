# tcb-manager-php

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


## API Docs

约定：

1. 所有对应于云API的函数命名都和云API一致，但是采用小驼峰风格；
2. 必选参数都对应都出现在函数签名上，可选的透传参数与云API参数保持一致，且大小写一致，非小驼峰风格；
3. 所有 API 调用返回的 JSON 数据全部反序列化为 PHP 的 `stdClass` 对象，非数组对象。

### TcbManager - 入口类，同一腾讯云TCB账户对应一个类实例

构造方法：

* `new TcbManager(array $options)`
    
    * `$options: array` - 【可选】初始化参数，如果SDK运行在云函数中，可省略，显式传递的参数优先级更高
      * `$secretId: string` - 腾讯云凭证 SecretId，`$secretId` 与 `$secretKey` 必须同时传递
      * `$secretKey: string` - 腾讯云凭证 SecretKey，`$secretId` 与 `$secretKey` 必须同时传递
      * `$secretToken: string` - 【可选】腾讯云临时凭证 `token`，传递此字段时意味着使用的是临时凭证，如果显式传递临时凭证，则此参数必传
      * `$envId: string` - 【可选】环境Id，因为后续的很多接口依赖于环境，在未传递的情况下，需要通过 `addEnvironment()` 添加环境方可进行后续接口调用

静态方法：

* `static function init(array $options): TcbManager` - 初始化默认 `TcbManager` 对象实例，单例的。

    参数同构造方法参数相同

    示例：
    
    ```php
    $tcbManager = TcbManager::init([
        "secretId" => "Your SecretId",
        "secretKey" => "Your SecretKey",
        "secretToken" => "Your SecretToken",
        "envId" => "Your envId"
    ]);
    ```
    
    【推荐】使用默认实例并通过该方法进行初始化。

实例方法：

1. 环境相关：

`TcbManager` 通过 `EnvironmentManager` 可管理多个 `Environment` 实例，存在一个当前环境的 `Environment`。

* `getEnvironmentManager(): EnvironmentManager` 获取环境管理器实例，可对多个 `Environment` 进行管理，存在一个当前的 `Environment` 对应于当前环境
* `addEnvironment(string $envId): void` 增加环境的实例，如果不存在当前环境，新增加的环境实例自动成为当前环境。注意，该方法不会在腾讯云TCB服务中创建环境，所以 `$envId` 对应的环境需要预先存在
* `currentEnvironment(): Environment` 获取当前环境 `Environment` 的实例

1. 能力相关：

能力是与环境 `Environment` 相关联的，所以以下函数都是获取当前 `Environment` 环境下的资源管理对象。

在没有切换当前环境的情况下，对应于初始化 `TcbManger` 时的 `envId` 所对应的环境。

* `getFunctionManager(): FunctionManager` - 获取当前环境下的 [FunctionManager](#FunctionManager) 对象实例，通过该对象实例可以管理云函数

### FunctionManager

`FunctionManager` 实例可以对云函数进行管理，包括创建、删除、更新、调用等云函数管理功能。

获得当前环境下的 `FunctionManager` 实例：

```php
$funcManager = $tcbManager->getFunctionManager();
```

该部分接口与[无服务器云函数](https://cloud.tencent.com/document/api/583/17234) API 相对应，相关接口的详细介绍说明可以参考此文档，返回值的详细描述可在 API 文档中查阅。

* `listFunctions()` - 获取云函数列表

    返回字段及更多说明见 API 文档: https://cloud.tencent.com/document/api/583/18582
    
    调用示例：
    
    ```php
    $funcManager->listFunctions();
    ```
    
    返回示例：
    
    ```json
    {
        "Response": {
            "Functions": [
                {
                    "FunctionId": "lam-xxxxxxx",
                    "Namespace": "default",
                    "FunctionName": "test",
                    "ModTime": "2018-04-08 19:02:20",
                    "AddTime": "2018-04-08 15:18:49",
                    "Runtime": "Python2.7"            
                }
            ],
            "TotalCount": 1,
            "RequestID": "3c140219-cfe9-470e-b241-907877d6fb03"
        }
    }
    ```
    
* `createFunction(string $functionName, array $code, string $handler, string $runtime, array $options = [])` - 创建函数

    * `$functionName: string` - 函数名称
    * `$code: array` - 源码资源，压缩包限制 `20M`，以下参数必选一种方式上传源码文件
        * `$ZipFile: string` - 包含函数代码文件及其依赖项的 `zip` 格式文件 经过 `base64` 编码后的字符串
        * `$ZipFilePath: string` - 包含函数代码文件及其依赖项的 `zip` 格式文件路径
        * `$SourceFilePath: string` - 源码文件路径
    * `$handler: string` - 函数调用入口，指明调用云函数时需要从哪个文件中的哪个函数开始执行。
                   通常写为 `index.main_handler`，指向的是 `index.[ext]` 文件内的 `main_handler` 函数方法。
                   包含 `入口文件名` 和 `入口函数名`，格式：`入口文件名.入口函数名`，例如：`index.main_handler`，文件名后缀省略
    * `$runtime: string` - 函数运行时，`Php7`，请注意运行时与函数源文件对应，否则无法执行
    * `$options: array` - 可选参数
        * `Description: string` - 函数描述
        * `Timeout: number` - 函数超时时间
        * `MemorySize: number` - 函数运行时内存大小，默认为 128M，可选范围 128MB-1536MB，并且以 128MB 为阶梯
        * `Environment: array` - 函数运行环境，见调用示例
          * `Variables: array` - 环境变量
            * `Key: string` - 变量的名称
            * `Value: string` - 变量的值

    注意：请在测试时在 TCB 控制台确认函数创建并部署成功，有可能创建成功，`createFunction` 成功返回，但是部署失败，部署失败的原因通常为 `$handler` 参数与源码包不对应。

    返回字段及更多说明见 API 文档: https://cloud.tencent.com/document/api/583/18586
 
    调用示例：
    
    ```php
    $funcManager->createFunction(
        "functionName",
        [
           // 根据实际需要选择以下某种方式
           "ZipFile" => "base64 zip file content"
           // "ZipFilePath" => "path/to/zipFile"
           // "SourceFilePath" => "path/to/source-code"
        ],
        "index.main",
        "Php7",
        [
           "Description" => "this is function description",
           "Environment" => [
               "Variables" => [
                   ["Key" => "Key", "Value" => "Value"]
               ]
           ]
        ]
    );
    ```
    
    返回示例：
    
    ```json
    {
        "Response": {
            "RequestId": "eac6b301-a322-493a-8e36-83b295459397"
        }
    }
    ```
    
    以JSON对象描述，在PHP中为对应的数组结构，其他函数返回格式相同

* `updateFunctionCode(string $functionName, string $code, string $handler, array $options = [])` - 更新云函数代码
    
    * `$functionName: string` - 函数名称
    * `$code: array` - 源码资源，压缩包限制 `20M`，以下参数必选一种方式上传源码文件
        * `$ZipFile: string` - 包含函数代码文件及其依赖项的 `zip` 格式文件 经过 `base64` 编码后的字符串
        * `$ZipFilePath: string` - 包含函数代码文件及其依赖项的 `zip` 格式文件路径
        * `$SourceFilePath: string` - 源码文件路径
    * `$handler: string` - 函数调用入口，同创建函数说明

    返回字段及更多说明见 API 文档: https://cloud.tencent.com/document/api/583/18581
 
    调用示例：
    
    ```php
    $funcManager->updateFunctionCode(
        "functionName",
        [
           // 根据实际需要选择以下某种方式
           "ZipFile" => "base64 zip file content"
           // "ZipFilePath" => "path/to/zipFile"
           // "SourceFilePath" => "path/to/source-code"
        ],
        "index.main",
        "Nodejs8.9"
    );
    ```
    
    返回示例：
    
    ```json
    {
        "Response": {
            "RequestId": "eac6b301-a322-493a-8e36-83b295459397"
        }
    }
    ```
    
* `updateFunctionConfiguration(string $functionName, array $options = [])` - 更新云函数配置
    
    * `$functionName: string` - 函数名称
    * `$options: array` - 可选参数，同 `createFunction`

    返回字段及更多说明见 API 文档: https://cloud.tencent.com/document/api/583/18580

    调用示例：
    
    ```php
    $funcManager->updateFunctionConfiguration(
        "functionName",
        [
            "Description" => "this is new description.",
            "Timeout" => 10,
            "Environment" => [
                "Variables" => [
                    ["Key" => "Key", "Value" => "NewValue"]
                ]
            ]
        ]
    );
    ```
    
    返回示例：
    
    ```json
    {
        "Response": {
            "RequestId": "eac6b301-a322-493a-8e36-83b295459397"
        }
    }
    ```

* `deleteFunction(string $functionName)` - 删除云函数

    * `$functionName: string` - 函数名称

    返回字段及更多说明见 API 文档: https://cloud.tencent.com/document/api/583/18585

    调用示例：
    
    ```php
    $funcManager->deleteFunction("functionName");
    ```
    
    返回示例：
    
    ```json
    {
        "Response": {
            "RequestId": "eac6b301-a322-493a-8e36-83b295459397"
        }
    }
    ```

* `getFunction(string $functionName)` - 获取云函数详情
    
    * `$functionName: string` - 函数名称

    返回字段及更多说明见 API 文档: https://cloud.tencent.com/document/api/583/18584

    调用示例：
    
    ```php
    $funcManager->getFunction("functionName");
    ```
    
    返回示例：
    
    ```json
    {
        "Response": {
            "ModTime": "2018-06-07 09:52:23",
            "Environment": {
                "Variables": []
            },
            "CodeError": "",
            "Description": "",
            "VpcConfig": {
                "SubnetId": "",
                "VpcId": ""
            },
            "Triggers": [],
            "ErrNo": 0,
            "UseGpu": "FALSE",
            "CodeSize": 0,
            "MemorySize": 128,
            "Namespace": "default",
            "FunctionVersion": "$LATEST",
            "Timeout": 3,
            "RequestId": "a1ffbba5-5489-45bc-89c5-453e50d5386e",
            "CodeResult": "failed",
            "Handler": "scfredis.main_handler",
            "Runtime": "Python2.7",
            "FunctionName": "ledDummyAPITest",
            "CodeInfo": "",
            "Role": ""
        }
    }
    ```
    
* `invoke(string $functionName, array $options = [])` - 调用云函数
    
    * `$functionName: string` - 函数名称
    * `$options: array` - 可选参数
        * `InvocationType: string` - `RequestResponse` (同步) 和 `Event` (异步)，默认为同步
        * `ClientContext: string` - 运行函数时的参数，以 `JSONString` 格式传入，最大支持的参数长度是 `1M`
        * `LogType: string` - 同步调用时指定该字段，返回值会包含 `4K` 的日志，可选值为 `None` 和 `Tail`，默认值为 `None`。
                      当该值为 `Tail` 时，返回参数中的 `logMsg` 字段会包含对应的函数执行日志
    
    返回字段及更多说明见 API 文档: https://cloud.tencent.com/document/api/583/17243

    调用示例：
    
    ```php
    $jsonString = "{\"userInfo\":{\"appId\":\"\",\"openId\":\"oaoLb4qz0R8STBj6ipGlHkfNCO2Q\"}}";
    $funcManager->invoke("functionName", [
            "InvocationType" => "RequestResponse",
            "ClientContext" => json_encode($jsonString),
            "LogType" => "Tail"
        ]);
    ```
    
    返回示例：
    
    ```json
    {
        "Response": {
            "Result": {
                "MemUsage": 3207168,
                "Log": "",
                "RetMsg": "hello from scf",
                "BillDuration": 100,
                "FunctionRequestId": "6add56fa-58f1-11e8-89a9-5254005d5fdb",
                "Duration": 0.826,
                "ErrMsg": "",
                "InvokeResult": 0
            },
            "RequestId": "c2af8a64-c922-4d55-aee0-bd86a5c2cd12"
        }
    }
    ```
    
* `getFunctionLogs(string $functionName, array $options = [])` - 获取云函数调用日志
    
    * `$functionName: string` - 函数名称
    * `$options: array` - 可选参数
        * `FunctionRequestId: string` - 执行该函数对应的 requestId
        * `Offset: number` - 数据的偏移量，Offset+Limit 不能大于 10000
        * `Limit: number` - 返回数据的长度，Offset+Limit 不能大于 10000
        * `Order: string` - 以升序还是降序的方式对日志进行排序，可选值 desc 和 asc
        * `OrderBy: string` - 根据某个字段排序日志,支持以下字段：function_name, duration, mem_usage, start_time
        * `StartTime: string` - 查询的具体日期，例如：2017-05-16 20:00:00，只能与 EndTime 相差一天之内
        * `EndTime: string` - 查询的具体日期，例如：2017-05-16 20:59:59，只能与 StartTime 相差一天之内

    返回字段及更多说明见 API 文档: https://cloud.tencent.com/document/api/583/18583

    调用示例：
    
    ```php
    $funcManager->getFunctionLogs("functionName", [
        "Offset" => 0,
        "Limit" => 3
    ]);
    ```

    返回示例：
    
    ```json
    {
        "Response": {
            "TotalCount": 1,
            "Data": [
                {
                    "MemUsage": 3174400,
                    "RetCode": 1,
                    "RetMsg": "Success",
                    "Log": "",
                    "BillDuration": 100,
                    "InvokeFinished": 1,
                    "RequestId": "bc309eaa-6d64-11e8-a7fe-5254000b4175",
                    "StartTime": "2018-06-11 18:46:45",
                    "Duration": 0.532,
                    "FunctionName": "APITest"
                }
            ],
            "RequestId": "e2571ff3-da04-4c53-8438-f58bf057ce4a"
        }
    }
    ```

## 概念

### Runtime

运行时，PHP运行时目前可填写 `Php7`，注意大小写

### Handler

执行方法表明了调用云函数时需要从哪个文件中的哪个函数开始执行。

* 一段式格式为 "[文件名]"，`Golang` 环境时使用，例如 "main";
* 两段式格式为 "[文件名].[函数名]"，`Python，Node.js，PHP` 环境时使用，例如 "index.main_handler";
* 三段式格式为 "[package].[class]::[method]"，`Java` 环境时使用，例如 "example.Hello::mainHandler";

两段式的执行方法，前一段指向代码包中不包含后缀的文件名，后一段指向文件中的入口函数名。
需要确保代码包中的文件名后缀与语言环境匹配，如 Python 环境为 .py 文件，Node.js 环境为 .js 文件。

