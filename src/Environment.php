<?php

namespace TcbManager;


use TcbManager\Api\RequestAble;
use TcbManager\Exceptions\EnvException;
use TencentCloudBase\TCB;
use TencentCloudClient\Exception\TCException;
use TcbManager\Services\Database\DatabaseManager;
use TcbManager\Services\Storage\StorageManager;
use TcbManager\Services\Functions\FunctionManager;

/**
 * Class Environment
 * @package TcbManager
 */
class Environment {
    use RequestAble;

    /**
     * @var string
     */
    private $id;

    /**
     * @var TCB
     */
    private $tcb;

    /**
     * @var TcbManager
     */
    private $tcbManager;

    /**
     * @var FunctionManager
     */
    private $functionManagers = [];
    private $functionManager;

    /**
     * @var StorageManager
     */
    private $storageManagers = [];
    private $storageManager;

    /**
     * @var DatabaseManager
     */
    private $databaseManagers = [];
    private $databaseManager;

    /**
     * Environment constructor.
     * @param string $id
     * @param TcbManager $tcbManager
     * @throws EnvException
     * @throws \TencentCloudBase\Utils\TcbException
     */
    public function __construct(string $id, TcbManager $tcbManager)
    {
        $this->id = $id;
        $this->tcbManager = $tcbManager;
        $this->api = $tcbManager->getApi();

        $result = $this->describe();

        if (count($result->EnvList) === 0) {
            throw new EnvException(EnvException::ENV_ID_NOT_EXISTS);
        }

        $this->tcb = new TCB([
            "secretId" => $tcbManager->getApi()->getCredential()->getSecretId(),
            "secretKey" => $tcbManager->getApi()->getCredential()->getSecretKey(),
            "env" => $id
        ]);

        if (isset($result->EnvList) and count($result->EnvList) === 1) {
            $envInfo = $result->EnvList[0];
            $this->functionManager = new FunctionManager($this->tcbManager, $envInfo->Functions[0]);
            $this->databaseManager = new DatabaseManager($this->tcbManager, $envInfo->Databases[0]);
            $this->storageManager = new StorageManager($this->tcbManager, $envInfo->Storages[0]);
        }
    }

    /**
     * @return TCB
     */
    public function getTcb()
    {
        return $this->tcb;
    }

    /**
     * @param string $action
     * @param array $params
     * @return mixed
     * @throws TCException
     */
    public function requestWithEnv(string $action, array $params = [])
    {
        $params["EnvId"] = $this->id;
        return $this->request($action, $params);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return mixed
     * @throws
     */
    public function describe()
    {
        return $this->requestWithEnv("DescribeEnvs");
    }

    /**
     * @param string $namespace
     * @return FunctionManager
     */
    public function getFunctionManager(string $namespace = ""): FunctionManager
    {
        return $this->functionManager;
    }

    /**
     * @param string $bucket
     * @return StorageManager
     */
    public function getStorageManager(string $bucket = ""): StorageManager
    {
        return $this->storageManager;
    }

    /**
     * @param string $instanceId
     * @return DatabaseManager
     */
    public function getDatabaseManager(string $instanceId): DatabaseManager
    {
        return $this->databaseManager;
    }
}
