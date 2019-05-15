<?php

namespace TcbManager\Tests;

use TcbManager\Environment;
use PHPUnit\Framework\TestCase;
use TcbManager\Exceptions\EnvException;
use TcbManager\Services\Functions\FunctionManager;

class EnvironmentTest extends TestCase
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @throws EnvException
     */
    protected function setUp(): void
    {
        parent::setUp();

        TestBase::init();

        $this->env = new Environment(TestBase::$envId, TestBase::$tcb);
    }

    /**
     * @throws EnvException
     */
    public function test__constructSuccess()
    {
        $env = new Environment(TestBase::$envId, TestBase::$tcb);
        $this->assertInstanceOf(Environment::class, $env);
    }

    /**
     * @throws EnvException
     */
    public function test__constructFailure()
    {
        $this->expectExceptionMessage(EnvException::ENV_ID_NOT_EXISTS);
        new Environment("not_exist_env_id", TestBase::$tcb);
    }

    public function testGetId()
    {
        $this->assertEquals(TestBase::$envId, $this->env->getId());
    }

    public function testGetFunctionManager()
    {
        $this->assertInstanceOf(FunctionManager::class, $this->env->getFunctionManager());
    }
}
