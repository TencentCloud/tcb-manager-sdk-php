<?php

namespace TcbManager\Tests;

use TcbManager\Constants;
use TcbManager\Environment;
use TcbManager\Exceptions\EnvException;
use TcbManager\Exceptions\TcbException;
use TcbManager\Services\Functions\FunctionManager;
use TcbManager\TcbManager;
use PHPUnit\Framework\TestCase;

final class TcbManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestBase::init();
    }

    public function test__construct_ExceptionFor_INVALID_PARAM()
    {
        putenv(Constants::ENV_RUNENV);
        putenv(Constants::ENV_SECRETID."=");
        putenv(Constants::ENV_SECRETKEY."=");
        putenv(Constants::ENV_SESSIONTOKEN."=");
        $this->expectExceptionMessage(TcbException::MISS_SECRET_INFO_IN_ARGS);
        new TcbManager([]);
    }

    public function test__construct_ExceptionFor_INVALID_RUNTIME_ENVIRONMENT()
    {
        putenv(Constants::ENV_RUNENV_SCF);
        putenv(Constants::ENV_SECRETID."=");
        putenv(Constants::ENV_SECRETKEY."=");
        putenv(Constants::ENV_SESSIONTOKEN."=");
        $this->expectExceptionMessage(TcbException::MISS_SECRET_INFO_IN_ENV);
        new TcbManager([]);
    }

    public function test__constructShouldSuccess()
    {
        // 1. 普通环境下参数初始化
        $this->assertInstanceOf(TcbManager::class, new TcbManager([
            "secretId" => TestBase::$secretId,
            "secretKey" => TestBase::$secretKey,
        ]));

        // 2. 云函数环境下参数初始化
        putenv(Constants::ENV_RUNENV_SCF);
        $this->assertInstanceOf(TcbManager::class, new TcbManager([
            "secretId" => TestBase::$secretId,
            "secretKey" => TestBase::$secretKey,
        ]));

        // 3. 云函数环境下环境变量初始化
        putenv(Constants::ENV_RUNENV_SCF);
        putenv(Constants::ENV_SECRETID."=".TestBase::$secretId);
        putenv(Constants::ENV_SECRETKEY."=".TestBase::$secretKey);
        putenv(Constants::ENV_SESSIONTOKEN."=".TestBase::$secretToken);
        $this->assertInstanceOf(TcbManager::class,  new TcbManager([]));
    }

    public function testAddEnvironmentShouldSuccess() {
        $tcb = new TcbManager([
            "secretId" => TestBase::$secretId,
            "secretKey" => TestBase::$secretKey,
            "secretToken" => TestBase::$secretToken,
        ]);

        $tcb->addEnvironment(TestBase::$envId);
        $env = $tcb->currentEnvironment();
        $this->assertInstanceOf(Environment::class, $env);
    }

//    public function testCurrentEnvironmentThrowsEnvException() {
//        $tcb = new TcbManager([
//            "secretId" => TestBase::$secretId,
//            "secretKey" => TestBase::$secretKey,
//        ]);
//        $this->expectExceptionMessage(EnvException::CURRENT_ENVIRONMENT_IS_NULL);
//        $tcb->currentEnvironment();
//    }

    public function testCurrentEnvironmentShouldSuccess()
    {
        $this->assertInstanceOf(
            Environment::class,
            TestBase::$tcb->currentEnvironment()
        );
    }

    public function testGetFunctionManagerShouldSuccess()
    {
        $this->assertInstanceOf(
            FunctionManager::class,
            TestBase::$tcb->getFunctionManager()
        );
    }
}
