<?php

namespace TcbManager\Tests;

use PHPUnit\Framework\TestCase;
use TcbManager\EnvironmentManager;
use TcbManager\Exceptions\EnvException;


class EnvironmentManagerTest extends TestCase
{
    /**
     * @var EnvironmentManager
     */
    private $envManager;

    protected function setUp(): void
    {
        parent::setUp();

        TestBase::init();

        $this->envManager = new EnvironmentManager(TestBase::$tcb);
    }

    public function testAddExistsEnvSuccess()
    {
        $ok = $this->envManager->add(TestBase::$envId);
        $this->assertTrue($ok);
    }

    public function testAddNotExistsEnvFailure()
    {
        $this->expectException(EnvException::class);
        $this->envManager->add("not_exist_env_id");
    }

    public function testRemoveEnvSuccess()
    {
        $this->envManager->add(TestBase::$envId);
        $this->envManager->remove(TestBase::$envId);
        $this->assertEmpty($this->envManager->get(TestBase::$envId));
    }

    public function testSwitchEnv()
    {
        $this->envManager->add(TestBase::$envId);

        $ok = $this->envManager->switchEnv(TestBase::$envId);

        $this->assertTrue($ok);
        $this->assertEquals(TestBase::$envId, $this->envManager->getCurrent()->getId());

        $ok = $this->envManager->switchEnv("not_exists_env_id");
        $this->assertTrue(!$ok);
    }

    public function testCheckEnvId()
    {
        $result = $this->envManager->checkEnvId(TestBase::$envId);
        $this->assertObjectHasAttribute("RequestId", $result);
    }

    public function testDescribeEnvs()
    {
        $result = $this->envManager->describeEnvs(TestBase::$envId);
        $this->assertObjectHasAttribute("RequestId", $result);
    }
}
