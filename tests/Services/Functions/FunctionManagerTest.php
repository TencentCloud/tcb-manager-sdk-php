<?php

namespace TcbManager\Tests\Services\Functions;

use PHPUnit\Framework\TestCase;
use TcbManager\Constants;
use TcbManager\Services\Functions\FunctionManager;
use TcbManager\TcbManager;
use TcbManager\Tests\TestBase;
use TcbManager\Utils;

const DS = DIRECTORY_SEPARATOR;

class FunctionManagerTest extends TestCase
{
    private $tmpFunctionName;

    /**
     * @var FunctionManager
     */
    private $funcManager;

    /**
     * @var string
     */
    private $sourceBeforeFilePath = __DIR__.DS."source".DS."before".DS."index.js";
    private $sourceAfterFilePath = __DIR__.DS."source".DS."after".DS;

    private $sourceFilePathForPHP = __DIR__.DS."source".DS."hello-tcb-php".DS;
    private $sourceFilePathForPHPZipFile = __DIR__.DS."source".DS."hello-tcb-php.zip";

    protected function setUp(): void
    {
        parent::setUp();

        TestBase::init();

        $this->funcManager = TestBase::$tcb->getFunctionManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function assertHasRequestId($result)
    {
        $this->assertObjectHasAttribute("RequestId", $result);
    }

    public function testFunctionCompleteLifeCycleCanBeSuccess()
    {

        $tmpFunctionName = "unit_test_".Utils::generateRandomString(6);

        $defaultConfiguration = [
             "Description" => "this is function description",
             "Environment" => [
                 "Variables" => [
                     ["Key" => "Key", "Value" => "Value"]
                 ]
             ]
        ];
        // 创建函数
        $result = $this->funcManager->createFunction(
            $tmpFunctionName,
            [
                "SourceFilePath" => $this->sourceBeforeFilePath
            ],
            "index.main",
            "Nodejs8.9",
            $defaultConfiguration
        );
        $this->assertHasRequestId($result);

        // 获取函数信息：验证函数信息
        $result = $this->funcManager->getFunction($tmpFunctionName);
        $this->assertHasRequestId($result);
        $this->assertEquals($result->FunctionName, $tmpFunctionName);
        $this->assertEquals($result->Namespace, $this->funcManager->getNamespace());
        $this->assertEquals($result->Description, $defaultConfiguration["Description"]);
        $this->assertEquals($result->Status, "Active");
        $this->assertEquals($result->Runtime, "Nodejs8.9");
        $this->assertEquals($result->InstallDependency, "TRUE");
        $this->assertEquals($result->Handler, "index.main");
        $this->assertEquals($result->MemorySize, 256);
        $this->assertEquals($result->Timeout, 3);

        // 调用云函数：第一次的上传的函数
        $jsonString = "{\"userInfo\":{\"appId\":\"\",\"openId\":\"oaoLb4qz0R8STBj6ipGlHkfNCO2Q\"}}";
        $result = $this->funcManager->invoke($tmpFunctionName, [
            // "Qualifier" => "\$LATEST",
            "InvocationType" => "RequestResponse",
            "ClientContext" => $jsonString,
            "LogType" => "Tail"
        ]);
        $this->assertHasRequestId($result);
        $this->assertObjectHasAttribute("Result", $result);
        $this->assertObjectHasAttribute("FunctionRequestId", $result->Result);
        $this->assertEquals("", $result->Result->ErrMsg);
        $this->assertEquals($jsonString, $result->Result->RetMsg);

        // 更新函数代码
        $result = $this->funcManager->updateFunctionCode(
            $tmpFunctionName,
            [
                "SourceFilePath" => $this->sourceAfterFilePath
            ],
            "index.main"
        );
        $this->assertHasRequestId($result);

        // 更新函数配置
        $newConfiguration = [
            "Description" => "this is new function description.",
            "Timeout" => 10,
            "Environment" => [
                "Variables" => [
                    ["Key" => "Key", "Value" => "NewValue"]
                ]
            ]
        ];
        $result = $this->funcManager->updateFunctionConfiguration(
            $tmpFunctionName,
            $newConfiguration
        );
        $this->assertHasRequestId($result);

        // 获取函数信息：验证函数信息
        $result = $this->funcManager->getFunction($tmpFunctionName);
        $this->assertHasRequestId($result);
        $this->assertEquals($result->FunctionName, $tmpFunctionName);
        $this->assertEquals($result->Namespace, $this->funcManager->getNamespace());
        $this->assertEquals($result->Description, $newConfiguration["Description"]);
        $this->assertEquals($result->Status, "Active");
        $this->assertEquals($result->Runtime, "Nodejs8.9");
        $this->assertEquals($result->InstallDependency, "TRUE");
        $this->assertEquals($result->Handler, "index.main");
        $this->assertEquals($result->MemorySize, 256);
        $this->assertEquals($result->Timeout, $newConfiguration["Timeout"]);

        // 调用云函数：第一次的上传的函数
        $result = $this->funcManager->invoke($tmpFunctionName, [
            "InvocationType" => "RequestResponse",
            "Qualifier" => "\$LATEST",
            "ClientContext" => "{}",
            "LogType" => "Tail"
        ]);

        $this->assertHasRequestId($result);
        $this->assertObjectHasAttribute("Result", $result);
        $this->assertObjectHasAttribute("FunctionRequestId", $result->Result);
        $this->assertEquals("", $result->Result->ErrMsg);
        // $this->assertEquals("{\"a\":1,\"b\":2,\"c\":3}", $result->Result->RetMsg);

        $retMsg = Utils::fromJSONString($result->Result->RetMsg);

        echo $retMsg->env->TENCENTCLOUD_SECRETID, PHP_EOL;
        echo $retMsg->env->TENCENTCLOUD_SECRETKEY, PHP_EOL;
        echo $retMsg->env->TENCENTCLOUD_SESSIONTOKEN, PHP_EOL;

        // 获取临时凭证比较麻烦，在这里测试临时凭证
        putenv(Constants::ENV_RUNENV_SCF);
        putenv(Constants::ENV_SECRETID."=".$retMsg->env->TENCENTCLOUD_SECRETID);
        putenv(Constants::ENV_SECRETKEY."=".$retMsg->env->TENCENTCLOUD_SECRETKEY);
        putenv(Constants::ENV_SESSIONTOKEN."=".$retMsg->env->TENCENTCLOUD_SESSIONTOKEN);

        $tcb = new TcbManager([
             "secretId" => $retMsg->env->TENCENTCLOUD_SECRETID,
             "secretKey" => $retMsg->env->TENCENTCLOUD_SECRETKEY,
             "secretToken" => $retMsg->env->TENCENTCLOUD_SESSIONTOKEN,
            "envId" => TestBase::$envId
        ]);

        $result = $tcb->getFunctionManager()->getFunctionLogs($tmpFunctionName, [
            "Offset" => 0,
            "Limit" => 3
        ]);
        $this->assertHasRequestId($result);

        // 获取函数列表
        $result = $tcb->getFunctionManager()->listFunctions();
        $this->assertHasRequestId($result);
        $this->assertGreaterThan(1, $result->TotalCount);

        // 删除函数
        $result = $tcb->getFunctionManager()->deleteFunction($tmpFunctionName);
        $this->assertObjectHasAttribute("RequestId", $result);
    }

    private function tryDeleteFunction(string $functionName)
    {
        try {
            $this->funcManager->deleteFunction($functionName);
        }
        catch (\Exception $e) {
            echo "tryDeleteFunction: ", $e->getMessage();
        }
        finally {
            echo "";
        }
    }

    public function testCreateFunctionByZipFile() {
        $code = [
            "SourceFilePath" => $this->sourceFilePathForPHP
        ];

        $functionName = "function_create_by_zip_file";

        $this->tryDeleteFunction($functionName);

        $result = $this->funcManager->createFunction(
            $functionName,
            [
                "ZipFile" => FunctionManager::makeZipFile($code)
            ],
            "index.main_handler",
            "Php7"
        );
        $this->assertHasRequestId($result);

        $result = $this->funcManager->deleteFunction($functionName);
        $this->assertObjectHasAttribute("RequestId", $result);
    }

    public function testCreateFunctionByZipFilePath() {
        if (!file_exists($this->sourceFilePathForPHPZipFile)) {
            Utils::makeZipFile(
                $this->sourceFilePathForPHP,
                $this->sourceFilePathForPHPZipFile
            );
        }

        $functionName = "function_create_by_zip_file_path";
        $this->tryDeleteFunction($functionName);

        $result = $this->funcManager->createFunction(
            $functionName,
            [
                "ZipFilePath" => $this->sourceFilePathForPHPZipFile
            ],
            "index.main_handler",
            "Php7"
        );
        $this->assertHasRequestId($result);

        $result = $this->funcManager->deleteFunction($functionName);
        $this->assertObjectHasAttribute("RequestId", $result);
    }

    public function testCreatePHPFunctionCanBeSuccess()
    {
        $phpFunctionName = "hello_tcb_php";
        $defaultConfiguration = [
            "Description" => "this is function description",
            "Environment" => [
                "Variables" => [
                    ["Key" => "Key", "Value" => "Value"]
                ]
            ]
        ];

        $result = $this->funcManager->createFunction(
            $phpFunctionName,
            [
                "SourceFilePath" => $this->sourceFilePathForPHP
            ],
            "index.main_handler",
            "Php7",
            $defaultConfiguration
        );
        $this->assertHasRequestId($result);

        $result = $this->funcManager->deleteFunction($phpFunctionName);
        $this->assertObjectHasAttribute("RequestId", $result);
    }
}
