# tcb-manager-php

<div id="badges">

[![Build Status](https://travis-ci.org/TencentCloudBase/tcb-manager-php.svg?branch=master)](https://travis-ci.org/tencentcloudbase/tcb-manager-php)
[![Latest Stable Version](https://poser.pugx.org/tencentcloudbase/tcb-manager-php/version)](https://packagist.org/packages/tencentcloudbase/tcb-manager-php)
[![Latest Unstable Version](https://poser.pugx.org/tencentcloudbase/tcb-manager-php/v/unstable)](//packagist.org/packages/tencentcloudbase/tcb-manager-php)
[![Total Downloads](https://poser.pugx.org/tencentcloudbase/tcb-manager-php/downloads)](https://packagist.org/packages/tencentcloudbase/tcb-manager-php)

</div>

* [tcb-manager-php](#tcb-manager-php)
  * [使用步骤](#使用步骤)
    * [安装SDK](#安装SDK)
    * [引入SDK](#引入SDK)
    * [使用SDK](#使用SDK)
    * [初始化SDK](#初始化SDK)
    * [完整示例](#完整示例)
  * [API](#API)
    * [TcbManager - 入口类](#TcbManager---入口类)
    * [FunctionManager - 云函数管理](#FunctionManager---云函数管理)
    * [DatabaseManager - 云数据库管理](#DatabaseManager---云数据库管理)
    * [StorageManager - 对象存储管理](#StorageManager---对象存储管理)

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


## API

约定：

1. 所有对应于云API的函数命名都和云API一致，但是采用小驼峰风格；
2. 必选参数都对应都出现在函数签名上，可选的透传参数与云API参数保持一致，且大小写一致，非小驼峰风格；
3. 为与API接口保持一致，所有 API 调用返回的 `JSON` 数据全部反序列化为 PHP 的 `stdClass` 对象，非数组对象。

公共返回参数：

Argument  | Type   | Description
----------|--------|------------------
RequestId | String | 唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。

如无特殊说明，接口返回结果均为 PHP 普通对象，且包含以上字段。

### TcbManager - 入口类

同一腾讯云TCB账户对应一个类实例

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

### FunctionManager - 云函数管理

`FunctionManager` 实例可以对云函数进行管理，包括创建、删除、更新、调用等云函数管理功能。

获得当前环境下的 `FunctionManager` 实例：

```php
$funcManager = $tcbManager->getFunctionManager();
```

* `listFunctions()` - 获取云函数列表

    调用示例：
    
    ```php
    $funcManager->listFunctions();
    ```
    
    返回示例：
    
    ```json
    {
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
    ```
    
    返回字段描述：
    
    Argument                 |  Type  | Description
    ------------------------ | ------ | -----------
    RequestID                | string | 请求唯一标识
    TotalCount               | number | 总数
    Functions                | array  | 总数
    Functions[].FunctionId   | string | 函数ID
    Functions[].FunctionName | string | 函数名称
    Functions[].Namespace    | string | 命名空间
    Functions[].Runtime      | string | 运行时
    Functions[].AddTime      | string | 创建时间
    Functions[].ModTime      | string | 修改时间

* `createFunction(string $functionName, array $code, string $handler, string $runtime, array $options = [])` - 创建函数

    * `$functionName: string` - 函数名称
    * `$code: array` - 源码资源，压缩包限制 `50M`，以下参数必选一种方式上传源码文件，请注意 `入口文件路径` 与 `handler` 参数相对应
                    因入口文件只能在根目录中，所以自压缩的 `Zip`包 请注意入口文件要在压缩包的根路径
        * `$ZipFile: string` - 包含函数代码文件及其依赖项的 `zip` 格式文件 经过 `base64` 编码后的字符串
        * `$ZipFilePath: string` - 包含函数代码文件及其依赖项的 `zip` 格式文件路径
        * `$SourceFilePath: string` - 源码文件路径
    * `$handler: string` - 函数调用入口，指明调用云函数时需要从哪个文件中的哪个函数开始执行。注意：入口文件只能在根目录中。
                   通常写为 `index.main_handler`，指向的是 `index.[ext]` 文件内的 `main_handler` 函数方法。
                   包含 `入口文件名` 和 `入口函数名`，格式：`入口文件名.入口函数名`，例如：`index.main_handler`，文件名后缀省略
    * `$runtime: string` - 函数运行时，`Php7`，请注意运行时与函数源文件对应，否则无法执行
    * `$options: array` - 可选参数
        * `Description: string` - 函数描述
        * `Timeout: number` - 函数超时时间
        * `MemorySize: number` - 函数运行时内存大小，单位 `MB`，默认为 `256`，可选值 `256` | `512`
        * `Environment: array` - 函数运行环境，见调用示例
          * `Variables: array` - 环境变量，在函数运行时可通过在环境变量里获取到响应的值，PHP 中获取环境变量函数为 `getenv`。
            * `Key: string` - 环境变量名，注意：避免使用系统常用的环境变量名导致系统环境变量出问题，建议用户设置的环境变量名采用统一前缀且大写，例如：`ENV_PROJECTNAME_[KeyName]`
            * `Value: string` - 环境变量值

    概念：

    * Runtime - 运行时

        运行时，PHP运行时目前可填写 `Php7`，注意大小写

    * Handler - 云函数入口

        执行方法表明了调用云函数时需要从哪个文件中的哪个函数开始执行。

        * 一段式格式为 "[文件名]"，`Golang` 环境时使用，例如 "main";
        * 两段式格式为 "[文件名].[函数名]"，`Python，Node.js，PHP` 环境时使用，例如 "index.main_handler";
        * 三段式格式为 "[package].[class]::[method]"，`Java` 环境时使用，例如 "example.Hello::mainHandler";

        两段式的执行方法，前一段指向代码包中不包含后缀的文件名，后一段指向文件中的入口函数名。
        需要确保代码包中的文件名后缀与语言环境匹配，如 Python 环境为 .py 文件，Node.js 环境为 .js 文件。

    注意：请在测试时在 TCB 控制台确认函数创建并部署成功，有可能创建成功，`createFunction` 成功返回，但是部署失败，部署失败的原因通常为 `$handler` 参数与源码包不对应。

    Zip压缩包文件示例：
    
    入口文件为：`index.js`，必须在压缩包根目录。

    代码文件路径示例：
    
    ```sh                                                                                                             
    .
    ├── README.md
    ├── index.js
    └── src
        └── index.js
    
    1 directory, 3 files
    ```
 
    压缩 zip 文件：
    
    请注意，该步骤是在源码根目录执行压缩，而不是在源码根目录的上级目录压缩源码目录
    
    ```sh
    zip -r code.zip .
      adding: README.md (stored 0%)
      adding: index.js (deflated 14%)
      adding: src/ (stored 0%)
      adding: src/index.js (stored 0%)
    ```
 
    查看 zip 包：
    
    ```sh
    ➜ unzip -l code.zip
    Archive:  code.zip
      Length      Date    Time    Name
    ---------  ---------- -----   ----
            8  05-20-2019 16:19   README.md
          122  06-10-2019 21:06   index.js
            0  05-20-2019 16:19   src/
            0  05-20-2019 16:19   src/index.js
    ---------                     -------
          130                     4 files
    ```
    
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
                   ["Key" => "ENV_PROJNAME_VERSION", "Value" => "v1.3.5"],
                   ["Key" => "ENV_PROJNAME_ENDPOINT", "Value" => "api.your-domain.com"]
                   ["Key" => "ENV_PROJNAME_ES_HOST", "Value" => "es-cluster.your-domain.com"]
               ]
           ]
        ]
    );
    ```
    
    返回示例：
    
    ```json
    {
        "RequestId": "eac6b301-a322-493a-8e36-83b295459397"
    }
    ```

    返回字段描述：
    
    Argument                 |  Type  | Description
    ------------------------ | ------ | -----------
    RequestID                | string | 请求唯一标识

    以JSON对象描述，在PHP中为对应的数组结构，其他函数返回格式相同

* `updateFunctionCode(string $functionName, string $code, string $handler, array $options = [])` - 更新云函数代码
    
    * `$functionName: string` - 函数名称
    * `$code: array` - 源码资源，压缩包限制 `50M`，以下参数必选一种方式上传源码文件，请注意 `入口文件路径` 与 `handler` 参数相对应
                    因入口文件只能在根目录中，所以自压缩的 `Zip`包 请注意入口文件要在压缩包的根路径
        * `$ZipFile: string` - 包含函数代码文件及其依赖项的 `zip` 格式文件 经过 `base64` 编码后的字符串
        * `$ZipFilePath: string` - 包含函数代码文件及其依赖项的 `zip` 格式文件路径
        * `$SourceFilePath: string` - 源码文件路径
    * `$handler: string` - 函数调用入口，同创建函数说明

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
        "RequestId": "eac6b301-a322-493a-8e36-83b295459397"
    }
    ```

    返回字段描述：
    
    Argument                 |  Type  | Description
    ------------------------ | ------ | -----------
    RequestID                | string | 请求唯一标识

* `updateFunctionConfiguration(string $functionName, array $options = [])` - 更新云函数配置
    
    * `$functionName: string` - 函数名称
    * `$options: array` - 可选参数，同 `createFunction`

    调用示例：
    
    ```php
    $funcManager->updateFunctionConfiguration(
        "functionName",
        [
            "Description" => "this is new description.",
            "Timeout" => 10,
            "Environment" => [
                "Variables" => [
                   ["Key" => "ENV_PROJNAME_VERSION", "Value" => "v1.3.5"],
                   ["Key" => "ENV_PROJNAME_ENDPOINT", "Value" => "api.your-domain.com"]
                   ["Key" => "ENV_PROJNAME_ES_HOST", "Value" => "es-cluster.your-domain.com"]
                ]
            ]
        ]
    );
    ```
    
    返回示例：
    
    ```json
    {
        "RequestId": "eac6b301-a322-493a-8e36-83b295459397"
    }
    ```

    返回字段描述：
    
    Argument                 |  Type  | Description
    ------------------------ | ------ | -----------
    RequestID                | string | 请求唯一标识

* `deleteFunction(string $functionName)` - 删除云函数

    * `$functionName: string` - 函数名称

    调用示例：
    
    ```php
    $funcManager->deleteFunction("functionName");
    ```
    
    返回示例：
    
    ```json
    {
        "RequestId": "eac6b301-a322-493a-8e36-83b295459397"
    }
    ```

    返回字段描述：
    
    Argument                 |  Type  | Description
    ------------------------ | ------ | -----------
    RequestID                | string | 请求唯一标识

* `getFunction(string $functionName)` - 获取云函数详情
    
    * `$functionName: string` - 函数名称

    调用示例：
    
    ```php
    $funcManager->getFunction("functionName");
    ```
    
    返回示例：
    
    ```json
    {
        "RequestId": "a1ffbba5-5489-45bc-89c5-453e50d5386e",
        "FunctionName": "ledDummyAPITest",
        "FunctionVersion": "$LATEST",
        "Namespace": "default",
        "Runtime": "Python2.7",
        "Handler": "scfredis.main_handler",
        "Description": "",
        "ModTime": "2018-06-07 09:52:23",
        "Environment": {
            "Variables": []
        },
        "VpcConfig": {
            "SubnetId": "",
            "VpcId": ""
        },
        "Triggers": [],
        "ErrNo": 0,
        "UseGpu": "FALSE",
        "MemorySize": 128,
        "Timeout": 3,
        "CodeSize": 0,
        "CodeResult": "failed",
        "CodeInfo": "",
        "CodeError": "",
        "Role": ""
    }
    ```

    返回字段描述：
    
    Argument                      |  Type  |  Description
    ----------------------------- | ------ | --------------
    RequestId                     | string | 请求唯一标识
    FunctionName                  | string | 函数名称
    Namespace                     | string | 命名空间
    Runtime                       | string | 运行时
    Handler                       | string | 函数入口
    Description                   | string | 函数的描述信息
    ModTime                       | string | 函数的入口
    Environment                   | object | 函数的环境变量
    Environment.Variables         | array  | 环境变量数组
    Environment.Variables[].Key   | string | 变量的Key
    Environment.Variables[].Value | string | 变量的Value
    MemorySize                    | number | 函数的最大可用内存
    Timeout                       | number | 函数的超时时间

* `invoke(string $functionName, array $options = [])` - 调用云函数
    
    * `$functionName: string` - 函数名称
    * `$options: array` - 可选参数
        * `InvocationType: string` - `RequestResponse` (同步) 和 `Event` (异步)，默认为同步
        * `ClientContext: string` - 运行函数时的参数，以 `JSONString` 格式传入，最大支持的参数长度是 `1M`
        * `LogType: string` - 同步调用时指定该字段，返回值会包含 `4K` 的日志，可选值为 `None` 和 `Tail`，默认值为 `None`。
                      当该值为 `Tail` 时，返回参数中的 `logMsg` 字段会包含对应的函数执行日志

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
    ```

    返回字段描述：
    
    Argument                 |  Type  |                    Description
    ------------------------ | ------ | --------------------------------------------------
    RequestId                | string | 请求唯一标识
    Result                   | object | 运行函数的返回
    Result.FunctionRequestId | string | 此次函数执行的Id
    Result.Duration          | number | 表示执行函数的耗时，单位是毫秒，异步调用返回为空
    Result.BillDuration      | number | 表示函数的计费耗时，单位是毫秒，异步调用返回为空
    Result.MemUsage          | number | 执行函数时的内存大小，单位为Byte，异步调用返回为空
    Result.InvokeResult      | number | 0为正确，异步调用返回为空
    Result.RetMsg            | string | 表示执行函数的返回，异步调用返回为空
    Result.ErrMsg            | string | 表示执行函数的错误返回信息，异步调用返回为空
    Result.Log               | string | 表示执行过程中的日志输出，异步调用返回为空

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
    ```

    返回字段描述：
    
    Argument                 |  Type  |                    Description
    ------------------------ | ------ | --------------------------------------------------
    RequestId                | string | 请求唯一标识
    TotalCount               | string | 函数日志的总数
    Data[]                   | array  | 运行函数的返回
    Data[].RequestId         | string | 执行该函数对应的requestId
    Data[].FunctionName      | string | 函数的名称
    Data[].RetCode           | number | 函数执行结果，如果是 0 表示执行成功，其他值表示失败
    Data[].InvokeFinished    | number | 函数调用是否结束，如果是 1 表示执行结束，其他值表示调用异常
    Data[].StartTime         | string | 函数开始执行时的时间点
    Data[].Duration          | number | 表示执行函数的耗时，单位是毫秒，异步调用返回为空
    Data[].BillDuration      | number | 表示函数的计费耗时，单位是毫秒，异步调用返回为空
    Data[].MemUsage          | number | 执行函数时的内存大小，单位为Byte，异步调用返回为空
    Data[].RetMsg            | string | 表示执行函数的返回，异步调用返回为空
    Data[].Log               | string | 表示执行过程中的日志输出，异步调用返回为空

### DatabaseManager - 云数据库管理

`DatabaseManager` 实例可以对数据库进行管理，以下表或集合为相同概念。

获得当前环境下的 `DatabaseManager` 实例：

```php
$databaseManager = $tcbManager->getDatabaseManager();
```

* `createCollection(string $collectionName): object` - 创建集合，如果集合已经存在，则会抛出异常

    * `$collectionName: string` - 集合名

    调用示例：
    
    ```php
    $result = $databaseManager->createCollection("collectionName")
    ```

    返回示例：
    
    ```json
    {
        "RequestId": "C563943B-3BEA-FE92-29FE-591EAEB7871F"
    }
    ```

    返回字段描述：
    
    Argument     | Type    | Description
    -------------|---------|------------------
    RequestId    | string  | 请求唯一标识

* `createCollectionIfNotExists(string $collectionName): object` - 创建集合，如果集合已存在，则不会创建集合

    * `$collectionName: string` - 表名
    
    调用示例：
    
    ```php
    $result = $databaseManager->createCollectionIfNotExists("collectionName")
    ```

    返回示例：
    
    ```json
    {
      "RequestId": "bdc5e528-6f06-42cf-95ac-53cc57413abf",
      "IsCreated": true,
      "ExistsResult": {
        "RequestId": "5187bd57-5746-4074-82c6-79e56f0290a3",
        "Exists": false
      }
    }
    ```

    返回字段描述：

    Argument     | Type    | Description
    -------------|---------|------------------
    RequestId    | string  | 请求唯一标识
    IsCreated    | Boolean | 是否创建集合
    ExistsResult | Object  | 检查集合是否存在返回结果

* `checkCollectionExists(string $collectionName): object` - 检查集合是否存在

    * `$collectionName: string` - 集合名
    
    调用示例：
    
    ```php
    $result = $databaseManager->checkCollectionExists("collectionAlreadyExists")
    ```

    返回示例：

    ```json
    {
        "RequestId": "ddd80891-528d-428d-bc14-5cf022084533",
        "Exists": true
    }
    ```

    返回字段描述：

    Argument     | Type    | Description
    -------------|---------|------------------
    RequestId    | string  | 请求唯一标识
    Exists       | Boolean | 集合是否已经存在


* `deleteCollection(string $collectionName): object` - 删除集合 - 如果集合不存在，也会正常返回

    * `$collectionName: string` - 集合名

    调用示例：
    
    ```php
    $result = $databaseManager->deleteCollection("collectionAlreadyExists")
    ```

    返回示例：
    
    ```json
    {
        "RequestId": "d145a61f-1eb4-49c9-88af-8d6c3940593a"
    }
    ```

    返回字段描述：

    Argument     | Type    | Description
    -------------|---------|------------------
    RequestId    | string  | 请求唯一标识

* `updateCollection(string $collectionName, array $options): object` - 更新集合

    * `$collectionName: string` - 集合名
    * `$options: array` - 选项
        * `$CreateIndexes: array` - 需要创建的索引列表
            * `$IndexName: string` - 索引名称
            * `$MgoKeySchema: array` - 索引模式：含 `唯一性` 和 `字段列表`
                * `$MgoIsUnique: bool = false` - 是否是唯一索引
                * `$MgoIndexKeys: array` - 索引字段列表
                    * `$Name: string` - 索引字段名称
                    * `$Direction: string` - 索引方向，`1`：`ASC`，`-1`：`DESC`，`2d`：双向，如果有 `2d`，`2d` 必须放最前面
        * `$DropIndexes array` - 需要删除的索引列表
            * `$IndexName: string` - 索引名称

    目前该接口只能更新索引，包括创建和删除，注意：
    
    1. 索引创建时如果已经存在，则会先删除再创建索引
    2. 因为一次接口调用可同时创建多个索引，所以可能部分索引创建失败，部分创建成功，接口报异常

    调用示例
    
    ```php
    $result = $databaseManager->updateCollection("collectionAlreadyExists", [
        "CreateIndexes" => [
            [
                "IndexName" => "index_a",
                "MgoKeySchema" => [
                    "MgoIndexKeys" => [
                        // 2d要放最前面
                        ["Name" => "a_2d", "Direction" => "2d"],
                        ["Name" => "a_1", "Direction" => "1"],
                        ["Name" => "a_-1", "Direction" => "-1"],
                    ],
                    "MgoIsUnique" => false
                ]
            ],
            [
                "IndexName" => "index_b",
                "MgoKeySchema" => [
                    "MgoIndexKeys" => [
                        ["Name" => "b_1", "Direction" => "2d"]
                    ],
                    "MgoIsUnique" => true
                ]
            ],
            [
                "IndexName" => "index_to_be_delete",
                "MgoKeySchema" => [
                    "MgoIndexKeys" => [
                        ["Name" => "xxx", "Direction" => "2d"]
                    ],
                    "MgoIsUnique" => true
                ]
            ],
        ],
    ]);
    ```
    
    ```php
    $result = $databaseManager->updateCollection("collectionAlreadyExists", [
        "DropIndexes" => [
            ["IndexName" => "index_to_be_delete"]
        ]
    ]);
    ```

    返回示例：

    ```json
    {
      "RequestId": "c32d717d-4092-487a-bb32-aa28bab06563"
    }
    ```

    返回字段描述：

    Argument     | Type    | Description
    -------------|---------|------------------
    RequestId    | string  | 请求唯一标识
    Exists       | Boolean | 集合是否已经存在


* `describeCollection(string $collectionName): object` - 查询集合详细信息

    * `$collectionName: string` - 集合名

    ```php
    $result = $databaseManager->describeCollection("collectionAlreadyExists");
    ```
    
    ```json
    {
      "Indexes": [
        {
          "Name": "_id_",
          "Size": 4096,
          "Keys": [
            {
              "Name": "_id",
              "Direction": "1"
            }
          ],
          "Unique": false,
          "Accesses": {
            "Ops": 0,
            "Since": "2019-06-11T15:09:04.037+08:00"
          }
        }
      ],
      "IndexNum": 1,
      "RequestId": "16e6ca3a-c342-49bc-ae2f-2fe657a93c64"
    }
    ```

    返回字段描述：

    Argument                      |  Type  |      Description
    ----------------------------- | ------ | ----------------------
    RequestId                     | string | 请求唯一标识
    IndexNum                      | Number | 索引个数
    Indexes                       | Array  | 索引列表
    Indexes[N].Name               | String | 索引名称
    Indexes[N].Size               | String | 索引大小，单位: 字节
    Indexes[N].Unique             | String | 是否为唯一索引
    Indexes[N].Keys               | Array  | 索引键值
    Indexes[N].Keys[N].Name       | String | 键名
    Indexes[N].Keys[N].Direction  | String | 索引方向，1: ASC, -1: DESC, 2d: 双向
    Indexes[N].Accesses           | Array  | 索引使用信息
    Indexes[N].Accesses[N].Ops    | Number | 索引命中次数
    Indexes[N].Accesses[N].Since  | String | 命中次数从何时开始计数

* `listCollections(array $options = []): object` - 查询所有集合信息

    * `$options: array = []` - 可选，偏移量
      * `$MgoOffset: number = 0` - 可选，偏移量
      * `$MgoLimit: number = 100` - 可选，数量限制

    调用示例：
    
    ```php
    $result = $databaseManager->listCollections([
        "MgoOffset" => 100,
        "MgoLimit" => 10,
    ])
    ```

    返回示例：
    
    ```
    {
        "RequestId": "d812272a-ae93-489b-aaa7-d6c8a1b2b753",
        "Collections": [
            {
                "CollectionName": "users",
                "Count": 2,
                "Size": 131,
                "IndexCount": 1,
                "IndexSize": 4096
            }
        ],
        "Pager": {
            "Offset": 0,
            "Limit": 100,
            "Total": 4
        }
    }
    ```

    返回字段描述：

    Argument                      |  Type  | Description
    ----------------------------- | ------ | ----------------------------
    RequestId                     | string | 请求唯一标识
    Collections                   | Array  | 集合列表
    Collections[N].CollectionName | String | 集合名称
    Collections[N].Count          | Number | 集合中文档数量
    Collections[N].Size           | Number | 集合占用空间大小，字节
    Collections[N].IndexCount     | Number | 集合中索引个数
    Collections[N].IndexSize      | Number | 集合中索引占用空间大小，字节
    Pager                         | Object | 本次查询分页信息
    Pager.Offset                  | Number | 偏移量
    Pager.Limit                   | Number | 限制数量
    Pager.Total                   | Number | 集合数量

* `checkIndexExists(string $collectionName, string $indexName): object` - 检查索引是否存在
        
    * `$collectionName: string` - 集合名
    * `$indexName: string` - 索引名
    
    调用示例：
    
    ```php
    $result = $databaseManager->checkIndexExists(
        "collectionAlreadyExists",
        "index_to_be_delete"
    )
    ```

    返回示例：
    
    ```json
    {
      "RequestId": "ac507001-a145-452a-bdf1-9b8190daa2de",
      "Exists": true
    }
    ```

    返回字段描述：

    Argument  |  Type   | Description
    ----------| ------- | ----------------------------
    RequestId | string  | 请求唯一标识
    Exists    | Boolean | 索引是否存在


* `import(string $collectionName, array $file, array $options = []): object` - 导入数据，立即返回，迁移状态（成功|失败）可通过 `migrateStatus` 查询

    导入数据需要先将上传到该环境（同一个EnvId）下的对象存储中，所以会在对象存储中创建对象。
    因为该函数成功返回只意味着上传成功，导入操作在上传后开始，所以该接口无法判断导入是否完成，所以该对象用完后需要手动删除，可以通过使用代码轮询迁移状态完成后，通过对象存储接口删除。
    
    * `$collectionName: string` - 集合名
    * `$file: array` - 数据，以下方式必选一种
      * `$FilePath: string` - 本地数据文件路径
      * `$ObjectKey: string` - 本 TCB 环境下对象存储Key
    * `$options: array` - 可选参数
      * `$ObjectKeyPrefix: string = tmp/db-imports/` - 对象存储 `Key` 前缀
      * `$FileType: string` - 文件类型：`csv` 或 `json`，如果为传递此参数，默认为文件后缀名，注意使用正确的后缀名。
      * `$StopOnError: boolean` - 遇到错误时是否停止导入。
      * `$ConflictMode: array` - 冲突处理方式：`insert` 或 `upsert`
    
    调用示例：
    
    ```php
    $databaseManager->import(
        $this->collectionAlreadyExists,
        [
            "ObjectKey" => "data.csv"
        ],
        [
            // "FileType" => "csv",
            "StopOnError" => true,
            "ConflictMode" => "upsert"
        ]
    )
    ```

    返回示例：
    
    ```json
    {
      "RequestId": "ac507001-a145-452a-bdf1-9b8190daa2de",
      "JobId": 200755
    }
    ```

    返回字段描述：

    Argument  |  Type   |  Description
    ----------| ------- | ----------------------------
    RequestId | string  | 请求唯一标识
    JobId     | Number  | 任务ID，用于在 `migrateStatus` 接口查询迁移状态

* `export(string $collectionName, array $file, array $options = []): object` - 导出数据，立即返回，迁移状态（成功|失败）可通过 `migrateStatus` 查询

    * `$collectionName: string` - 集合名
    * `$file: array` - 数据，以下方式必选一种
      * `$ObjectKey: string` - 本 TCB 环境下对象存储Key
    * `$options: array` - 可选参数
      * `$FileType: string` - 文件类型：`csv` 或 `json`，如果为传递此参数，默认为文件后缀名，注意使用正确的后缀名
      * `$Query: string` - JSON字符串，支持mongo指令。例如：'{ a: { $gte: 3 } }'。与 `mongodb` 查询语法兼容
      * `$Skip: number` - 偏移量
      * `$Limit: number` - 限制数目
      * `$Sort: number` - JSON 字符串，如果有索引则不支持排序，数据集的长度必须少于32兆
      * `$Fields: string` - 字符串，字段以逗号分割。`FileType=csv` 时必填

    请求示例：

    ```php
    $result = $databaseManager->export(
        "users",
        [
            "ObjectKey" => "users.json"
        ],
        [
             "Fields" => "_id,name",
             "Query" => '{"name":{"$exists":true}}',
             "Sort" => '{"name": -1}',
             "Skip" => 0,
             "Limit" => 1000
        ]
    )
    ```

    返回示例：
    
    ```json
    {
      "RequestId": "c64007fb-45b6-427d-9993-b9d9aaab06b5",
      "JobId": 100093276
    }
    ```

    返回字段描述：

    Argument  |  Type   |  Description
    ----------| ------- | ----------------------------
    RequestId | string  | 请求唯一标识
    JobId     | Number  | 任务ID，用于在 `migrateStatus` 接口查询迁移状态

* `migrateStatus(int $jobId): object` - 迁移（导入|导出）状态查询
    
    * `$jobId: int` - 任务Id，`import` 和 `export` 接口返回的 `JobId`

    请求示例：

    ```php
    $result = $databaseManager->migrateStatus(100093275);
    ```

    返回示例：
    
    ```json
    {
      "ErrorMsg": "导出完成.",
      "FileUrl": "https://tcb-mongodb-data-1254135806.cos.ap-shanghai.myqcloud.com/469835132/tcb_already_exists.json?q-sign-algorithm=sha1&q-ak=AKIDsp8NUoE8C8yd9TvEeyoX5g6LUmXUh9Wy&q-sign-time=1560263593;1560267193&q-key-time=1560263593;1560267193&q-header-list=&q-url-param-list=&q-signature=5fed574f9c459030cba2bf46eb329d4e6b2b4a72",
      "RecordFail": 0,
      "RecordSuccess": 2,
      "RequestId": "e5ce7401-c3ce-4724-8e2e-b3449ae537df",
      "Status": "success"
    }
    ```

    失败示例：

    ```json
    {
      "ErrorMsg": "导出数据记录条数为0，请确认是否存在满足导出条件的数据.",
      "FileUrl": "",
      "RecordFail": 0,
      "RecordSuccess": 0,
      "RequestId": "8cc3e698-9dbc-4dcf-bcec-372b3f0922cf",
      "Status": "fail"
    }
    ```

    返回字段描述：

    Argument      |  Type   |  Description
    ------------- | ------- | ---------------------------------------
    RequestId     | string  | 请求唯一标识
    Status        | String  | 任务状态。可能值：waiting：等待中，reading：读，writing：写，migrating：转移中，success：成功，fail：失败
    RecordSuccess | Integer | 迁移成功的数据条数
    RecordFail    | Integer | 迁移失败的数据条数
    ErrorMsg      | String  | 迁移失败的原因
    FileUrl       | String  | 文件下载链接，仅在数据库导出中有效

* `distribution(string $collectionName, array $file, array $options = []): object` - 数据分布
    
    请求示例：

    ```php
    $result = $databaseManager->distribution();
    ```

    返回示例：
     
    ```json
    {
      "Collections": [
        {
          "CollectionName": "users",
          "DocCount": 8
        },
        {
          "CollectionName": "tcb_test_collection_3",
          "DocCount": 0
        },
        {
          "CollectionName": "test_collection",
          "DocCount": 0
        },
        {
          "CollectionName": "tcb_already_exists",
          "DocCount": 0
        }
      ],
      "RequestId": "206b6795-559f-4aca-b1d7-31bc9557351a"
    }
    ```

    返回字段描述：

    Argument                      |  Type  | Description
    ----------------------------- | ------ | -----------
    RequestId                     | string  | 请求唯一标识
    Collections                   | Array  | 集合列表
    Collections[N].CollectionName | String | 集合名称
    Collections[N].DocCount       | Number | 文档数量

* `db()` - 获取数据库实例

    该SDK内嵌 `tcb-php-sdk`，该函数返回 `TencentCloudBase\Database\Db` 实例。
    
    调用示例：
    
    ```php
    $db = $databaseManager->db();
    $db->createCollection("users");
    $collection = $db->collection("users");
    $countResult = $collection->count();
    $collection->add(['name' => 'ben']);
    $queryResult = $collection->where([
        'name'=> "ben"
    ])->get();
    ```
    
    说明文档见：https://github.com/TencentCloudBase/tcb-php-sdk/blob/master/docs/database.md


### StorageManager - 对象存储管理

`StorageManager` 实例可以对文件（对象存储）进行管理。

获得当前环境下的 `StorageManager` 实例：

```php
$stroageManager = $tcbManager->getStorageManager();
```

`key` and `prefix`

每个对象都有一个唯一的 `key`，对应于函数签名中的 `key` 参数，类似于文件路径，可通过分隔符 `/` 分隔 `key`，例如：`images/avatar/head.jpg`，`images/avatar/` 也是一个合法的 `key`，但是这个 `key` 对应的对象没有实际内容。注意：在对象存储中没有文件、文件夹等文件系统概念。

从 `ObjectKey` 的第一个字符开始到任意字符，构成的字符串被称为 `prefix`，例如：`images/avatar/head.jpg` 的 `prefix` 有 `images/avatar/` 或 `images/ava` 等，甚至 `images/avatar/head.jpg` 也可以是一个合法的 `prefix`。

> 对象存储部分是一套独立的API，采用 `RESTFul` 风格的API，部分对象元信息通过HTTP头返回，所以响应接口也会有不同。

* `putObject(string $key, string $path, array $options = []): object` - 上传单个对象

    * `$key: string` - `ObjectKey`
    * `$path: string` - 文件路径，如果该路径是一个目录，则会在该目录下查找 `$key` 文件上传，`join($path, $key)`
                        通常该参数为文件根目录
    * `$options: array = []` - 可选参数

    调用示例：

    ```php
    $storageManager->putObject("/path/to/file", "path/to/asserts")
    $storageManager->putObject("/image/head.ico", "/workspace/projcect")
    ```

    返回示例（删除了公共响应字段）：

    ```
    stdClass Object
    (
        [RequestId] => NWQxMzM1YjdfMmE5ZDA4MDlfNTRmXzc5NmJiNA==
        [Headers] => Array
            (
                [ETag] => "afed0acbedb862908dcccccd8c375e0e"
            )
    
        [Body] => 
    )
    ```

    返回字段描述：

    Argument        |  Type  | Description
    --------------- | ------ | -----------
    RequestId       | string | 请求唯一标识
    Headers[N].ETag | string | 对象的 ETag 值
    Body            | null   | 该接口无 body

* `deleteObject(string $key): object` - 删除单个对象

    * `$key: string` - `ObjectKey`

    调用示例：

    ```php
    $storageManager->deleteObject($key)
    ```

    返回示例（删除了公共响应字段）：

    ```
    stdClass Object
    (
        [RequestId] => NWQxMzM1MjFfN2RjNTFjMDlfMjFmYjdfN2U0Mjk5
        [Headers] => Array
            (
            )
    
        [Body] => 
    )
    ```

    返回字段描述：

    Argument        |  Type  | Description
    --------------- | ------ | -----------
    RequestId       | string | 请求唯一标识
    Headers         | array  | 无特定的头部字段
    Body            | null   | 该接口无 body

* `getObject(string $key): object` - 下载单个对象

    * `$key: string` - `ObjectKey`
    * `$target: string` - 下载文件保存地址

    ```php
    $storageManager->getObject($key, $target)
    ```
    
    返回值示例：
    
    该接口会同时将对象写入 `$target` 指定路径
    
    ```txt
    stdClass Object
    (
        [RequestId] => NWQxMzU0NTFfMzRhNzAzMDlfYTFmOF84NDAxMmQ=
        [Headers] => Array
            (
                [Accept-Ranges] => bytes
                [ETag] => "401b30e3b8b5d629635a5c613cdb7919"
                [Last-Modified] => Wed, 26 Jun 2019 19:17:37 GMT
            )
    
        [Body] => 
    )
    ```

    返回字段描述：

    Argument              |  Type  |    Description
    --------------------- | ------ | ------------------
    RequestId             | string | 请求唯一标识
    Headers.ETag          | string | 对象的 ETag 值
    Headers.Last-Modified | string | 对象的最后修改时间
    Body                  | null   | 该接口无 body

    同时，该接口会将对象内容写入文件

* `listObjects(array $options = []): object` - 获取对象列表

    * `$options: array = []` - 可选参数
      * `$options.prefix: string` - 对象键匹配前缀，限定响应中只包含指定前缀的对象键，例如：`src/`，表示以 `src` 或 `dist` 为前缀的对象
      * `$options.delimiter: string` - 一个字符的分隔符，用于对 `prefix` 进行分组
      * `$options.max-keys: number` - 单次返回最大的条目数量，默认值为 `1000`，最大为 `1000`
      * `$options.marker: string` - `ObjectKey`，所有列出条目从 `marker` 开始，如果不能一次全部返回，则可通过此字段跳过

    调用示例：

    ```php
    $storageManager->listObjects([
        "prefix" => "src/",
        "delimiter" => "",
        "max-keys" => 1000
    ]);
    ```

    返回示例：

    ```
    stdClass Object
    (
        [Name] => test-1251267563
        [Prefix] => src/
        [Marker] => 
        [MaxKeys] => 1000
        [Delimiter] => /
        [IsTruncated] => false
        [Contents] => Array
            (
                [0] => stdClass Object
                    (
                        [Key] => src/
                        [LastModified] => 2019-06-12T07:08:33.000Z
                        [ETag] => "d41d8cd98f00b204e9800998ecf8427e"
                        [Size] => 0
                        [Owner] => stdClass Object
                            (
                                [ID] => 1251267563
                                [DisplayName] => 1251267563
                            )

                        [StorageClass] => STANDARD
                    )
                [1] => stdClass Object
                    (
                        [Key] => src/index.ts
                        [LastModified] => 2019-06-12T07:08:44.000Z
                        [ETag] => "4d212baa186498091dd7628d21540b1f"
                        [Size] => 25
                        [Owner] => stdClass Object
                            (
                                [ID] => 1251267563
                                [DisplayName] => 1251267563
                            )

                        [StorageClass] => STANDARD
                    )

            )

    )
    ```

    返回字段描述：

    Argument                     |  Type   | Description
    ---------------------------- | ------- | ---------------------------------------------
    Name                         | string  | 说明 Bucket 的信息
    Prefix                       | string  | 对象键匹配前缀，对应请求中的 prefix 参数
    Marker                       | string  | 默认以 UTF-8 二进制顺序列出条目，所有列出条目从 marker 开始
    NextMarker                   | string  | 假如返回条目被截断，则返回 NextMarker 就是下一个条目的起点
    MaxKeys                      | string  | 单次响应请求内返回结果的最大的条目数量
    Delimiter                    | string  | 分隔符，对应请求中的 delimiter 参数
    IsTruncated                  | boolean | 响应请求条目是否被截断，布尔值：true，false
    Contents                     | array   | 元数据信息
    Contents[].Key               | string  | Object 的 Key
    Contents[].LastModified      | string  | 说明 Object 最后被修改时间
    Contents[].ETag              | string  | 文件的 MD5 算法校验值
    Contents[].Size              | string  | 说明文件大小，单位是 Byte
    Contents[].Owner             | string  | Bucket 持有者信息
    Contents[].Owner.ID          | string  | Bucket 的 APPID
    Contents[].Owner.DisplayName | string  | Object 持有者的名称
    Contents[].StorageClass      | string  | Object 的存储类型，枚举值：STANDARD，STANDARD_IA，ARCHIVE。详情请参阅 存储类型 文档
    CommonPrefixes               | array   | 只有指定了 delimiter 参数的情况下才有可能包含该元素
    CommonPrefixes[].Prefix      | string  | 单条 Common Prefix 的前缀

* `getTemporaryObjectUrl(string $key, array $options): string` - 获取临时访问地址
    
    * `$key: string` - `ObjectKey`
    * `$options: array = []` - 可选参数
      * `$expires: string = 10 minutes` - 有效期，请注意设置合理的有效期，格式为 [strtotime](https://php.net/manual/en/function.strtotime.php) 函数所接受的字符串
      * `$checkObjectExists: boolean = true` - 是否检查对象是否存在，默认为 `true`。如果为 `true`，则在对象不存在时，返回的 `url` 为空，如果为 `false`，则返回的 `url` 不为空，Url 在访问时会 `404`。检查对象是否存在需要发起网络请求，所以相对会慢一些，如果确认对象一定存在，可关闭检查。

    请注意：对象的访问权限需要对外开放，否则拿不到URL

    调用示例：

    ```php
    $url = $stroageManager->getTemporaryObjectUrl("functionName", [
        "expires" => "10 minutes",
        "checkObjectExists" => true
    ]);
    ```

    返回示例：
    
    ```
    https://6465-demo-619e0a-1251267563.tcb.qcloud.la/MapOS%E6%A0%87%E5%87%86%E5%8C%96%E6%8C%87%E5%BC%95.png?sign=q-sign-algorithm%3Dsha1%26q-ak%3DAKIDORiMgDgJrLPjxvKDm9F77NGixduHpm0o%26q-sign-time%3D1560173580%3B1560174240%26q-key-time%3D1560173580%3B1560174240%26q-header-list%3Dhost%26q-url-param-list%3D%26q-signature%3Dd5265213143344679462866948f25e834feb7c87
    ```

* `upload(string $src, array $options = []): void` - 上传目录
    
    上传本地目录 `$src` 中的文件到对象存储桶的 `$options["prefix"]` 路径下

    * `$src: string` - 本地路径
    * `$options: array = []` - 可选参数
        * `$prefix: string` - 对象存储的指定 `key` 前缀，即路径，默认为根路径

   调用示例：
   
   ```php
   $storageManager->upload($src, ["prefix" => "abc"])
   ```
    
    该接口无返回值

* `download(string $dst, array $options = []): void` - 下载目录
    
    下载目录 `$options["prefix"]` 中的文件到本地的 `$dst` 路径下

    * `$src: string` - 本地路径
    * `$options: array = []` - 可选参数
        * `$prefix: string` - 对象存储的指定 `key` 前缀，即路径，默认为根路径

   调用示例：
   
   ```php
   $storageManager->upload($src, ["prefix" => "src/"])
   ```
    
    该接口无返回值

* `remove(array $options = []): void` - 移除目录
    
    删除 `$options["prefix"]` 中的对象

    * `$options: array = []` - 可选参数
        * `$prefix: string` - 对象存储的指定 `key` 前缀，即路径，默认为根路径

    调用示例：
    
    ```php
    $storageManager->remove(["prefix" => "src/"])
    ```
    
    该接口无返回值

* `keys(array $options = []): array` - 列出对象

    列出 `$options["prefix"]` 中的对象

    * `$options: array = []` - 可选参数
        * `$prefix: string` - 对象存储的指定 `key` 前缀，即路径，默认为根路径

    调用示例：
    
    ```php
    $storageManager->keys(["prefix" => "src/"])
    ```
    
    返回示例：
    
    ```
    Array
    (
        [0] => upload/.gitignore
        [1] => upload/index.js
        [2] => upload/lib/index.js
        [3] => upload/文档.doc
    )
    ```
