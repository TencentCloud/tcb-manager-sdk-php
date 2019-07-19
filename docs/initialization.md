
## 初始化

通过腾讯云 API 密钥初始化，示例代码如下：

```php
$tcbManager = TcbManager::init([
    "secretId" => "Your SecretId",
    "secretKey" => "Your SecretKey",
    "secretToken" => "Your SecretToken", // 使用临时凭证需要此字段
    "envId" => "Your envId"  // TCB环境ID，可在腾讯云TCB控制台获取
]);
```

>!需要提前开通 TCB 服务并创建环境，否则 SDK 无法使用。


在云函数环境下，支持免密钥初始化，示例代码如下：

```php
$tcbManager = TcbManager::init([
    "envId" => "Your envId"
]);
```

初始化后得到一个 TcbManager 实例。（该实例是单例的，多次调用 TcbManager::init 只会初始化一次。）

您也可以通过 new TcbManager 创建实例，示例代码如下：
```php
$tcbManager = new TcbManager([
    "secretId" => "Your SecretId",
    "secretKey" => "Your SecretKey",
    "secretToken" => "Your SecretToken", // 使用临时凭证需要此字段
    "envId" => "Your envId"  // TCB环境ID，可在腾讯云TCB控制台获取
])
```
每次初始化都会得到一个全新的 TcbManager 实例，如果需要管理多个腾讯云账号下的 TCB 服务，可通过此种方式创建多个 TcbManager 实例。

初始化完成之后，即可使用相关功能。

### 完整示例

**list-functions**（[源码](https://github.com/TencentCloudBase/tcb-manager-php/blob/master/samples/list-functions.php)）：

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

**输出示例**：

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

### TcbManager - 入口类

同一腾讯云TCB账户对应一个类实例

构造方法：

#### `new TcbManager(array $options)`

* `$options: array` - 【可选】初始化参数，如果SDK运行在云函数中，可省略，显式传递的参数优先级更高
  * `$secretId: string` - 腾讯云凭证 SecretId，`$secretId` 与 `$secretKey` 必须同时传递
  * `$secretKey: string` - 腾讯云凭证 SecretKey，`$secretId` 与 `$secretKey` 必须同时传递
  * `$secretToken: string` - 【可选】腾讯云临时凭证 `token`，传递此字段时意味着使用的是临时凭证，如果显式传递临时凭证，则此参数必传
  * `$envId: string` - 【可选】环境Id，因为后续的很多接口依赖于环境，在未传递的情况下，需要通过 `addEnvironment()` 添加环境方可进行后续接口调用

静态方法：

#### `static function init(array $options): TcbManager`

初始化默认 `TcbManager` 对象实例，单例的。

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
    
    * `getEnvironmentManager(): EnvironmentManager` 获取环境管理器实例，可对多个 `Environment` 进行管理，存在一个当前的     `Environment` 对应于当前环境
    * `addEnvironment(string $envId): void` 增加环境的实例，如果不存在当前环境，新增加的环境实例自动成为当前环境。注意，该方法不会在腾讯云    TCB服务中创建环境，所以 `$envId` 对应的环境需要预先存在
    * `currentEnvironment(): Environment` 获取当前环境 `Environment` 的实例

2. 能力相关：

    能力是与环境 `Environment` 相关联的，所以以下函数都是获取当前 `Environment` 环境下的资源管理对象。

    在没有切换当前环境的情况下，对应于初始化 `TcbManger` 时的 `envId` 所对应的环境。

   * `getFunctionManager(): FunctionManager` - 获取当前环境下的 `FunctionManager` 对象实例，通过该对象实例可以管理云函数
   * `getDatabaseManager(): DatabaseManager` - 获取当前环境下的 `DatabaseManager` 对象实例，通过该对象实例可以管理云函数
   * `getStorageManager(): StorageManager` - 获取当前环境下的 `StorageManager` 对象实例，通过该对象实例可以管理云函数
