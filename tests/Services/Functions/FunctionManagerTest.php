<?php

namespace TcbManager\Tests\Services\Functions;

use PHPUnit\Framework\TestCase;
use TcbManager\Services\Functions\FunctionManager;
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

    protected function setUp(): void
    {
        parent::setUp();

        TestBase::init();

        $this->funcManager = TestBase::$tcb->getFunctionManager();
        $this->tmpFunctionName = "unit_test_".Utils::generateRandomString(6);
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
        $defaultConfiguration = [
            // "Description" => "123",
            // "Environment" => [
            //     ["Key" => "", "Value" => ""]
            // ]
        ];
        // 创建函数
        $result = $this->funcManager->createFunction(
            $this->tmpFunctionName,
            $this->sourceBeforeFilePath,
            "index.main",
            "Nodejs8.9",
            $defaultConfiguration
        );
        $this->assertHasRequestId($result);

        // 获取函数信息：验证函数信息
        $result = $this->funcManager->getFunction($this->tmpFunctionName);
        $this->assertHasRequestId($result);
        $this->assertEquals($result->FunctionName, $this->tmpFunctionName);
        $this->assertEquals($result->Namespace, $this->funcManager->getNamespace());
        $this->assertEquals($result->Description, "");
        $this->assertEquals($result->Status, "Active");
        $this->assertEquals($result->Runtime, "Nodejs8.9");
        $this->assertEquals($result->InstallDependency, "TRUE");
        $this->assertEquals($result->Handler, "index.main");
        $this->assertEquals($result->MemorySize, 256);
        $this->assertEquals($result->Timeout, 3);

        // 调用云函数：第一次的上传的函数
        $jsonString = "{\"userInfo\":{\"appId\":\"\",\"openId\":\"oaoLb4qz0R8STBj6ipGlHkfNCO2Q\"}}";
        $result = $this->funcManager->invoke($this->tmpFunctionName, [
            "InvocationType" => "RequestResponse",
            "Qualifier" => "\$LATEST",
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
            $this->tmpFunctionName,
            $this->sourceAfterFilePath,
            "index.main",
            []
        );
        $this->assertHasRequestId($result);

        // 更新函数配置
        $newConfiguration = [
            "Description" => "this is new description.",
            "Timeout" => 10
        ];
        $result = $this->funcManager->updateFunctionConfiguration(
            $this->tmpFunctionName,
            $newConfiguration
        );
        $this->assertHasRequestId($result);

        // 获取函数信息：验证函数信息
        $result = $this->funcManager->getFunction($this->tmpFunctionName);
        $this->assertHasRequestId($result);
        $this->assertEquals($result->FunctionName, $this->tmpFunctionName);
        $this->assertEquals($result->Namespace, $this->scf->getNamespace());
        $this->assertEquals($result->Description, $newConfiguration["Description"]);
        $this->assertEquals($result->Status, "Active");
        $this->assertEquals($result->Runtime, "Nodejs8.9");
        $this->assertEquals($result->InstallDependency, "TRUE");
        $this->assertEquals($result->Handler, "index.main");
        $this->assertEquals($result->MemorySize, 256);
        $this->assertEquals($result->Timeout, $newConfiguration["Timeout"]);

        // 调用云函数：第一次的上传的函数
        $result = $this->funcManager->invoke($this->tmpFunctionName, [
            "InvocationType" => "RequestResponse",
            "Qualifier" => "\$LATEST",
            "ClientContext" => "{}",
            "LogType" => "Tail"
        ]);
        $this->assertHasRequestId($result);
        $this->assertObjectHasAttribute("Result", $result);
        $this->assertObjectHasAttribute("FunctionRequestId", $result->Result);
        $this->assertEquals("", $result->Result->ErrMsg);
        $this->assertEquals("{\"a\":1,\"b\":2,\"c\":3}", $result->Result->RetMsg);

        $result = $this->funcManager->getFunctionLogs($this->tmpFunctionName, [
            "Offset" => 0,
            "Limit" => 3,
            "Qualifier" => "\$LATEST",
//            "Filter" => [
//                "RetCode" => "is0",
////                "RetCode" => "not0",
//            ],
//            "StartTime" => date("Y-m-d H:i:s", strtotime("-24 hours")),
//            "EndTime" => date("Y-m-d H:i:s"),
        ]);
        $this->assertHasRequestId($result);
//        var_dump($result);

        // 获取函数列表
        $result = $this->funcManager->listFunctions();
        $this->assertHasRequestId($result);
        $this->assertGreaterThan(1, $result->TotalCount);

        // 删除函数
        $result = $this->funcManager->deleteFunction($this->tmpFunctionName);
        $this->assertObjectHasAttribute("RequestId", $result);
    }
}
