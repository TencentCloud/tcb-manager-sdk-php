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
    private $tcbDataApi;

    /**
     * @var TcbManager
     */
    private $tcb;

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
     * @param TcbManager $tcb
     * @throws EnvException
     * @throws \TencentCloudBase\Utils\TcbException
     */
    public function __construct(string $id, TcbManager $tcb)
    {
        $this->id = $id;
        $this->tcb = $tcb;
        $this->api = $tcb->getApi();

        $result = $this->describe();

        if (count($result->EnvList) === 0) {
            throw new EnvException(EnvException::ENV_ID_NOT_EXISTS);
        }

        $this->tcbDataApi = new TCB([
            "secretId" => $tcb->getApi()->getCredential()->getSecretId(),
            "secretKey" => $tcb->getApi()->getCredential()->getSecretKey(),
            "env" => $id
        ]);

        if (isset($result->EnvList) and count($result->EnvList) === 1) {
            $envInfo = $result->EnvList[0];
            $this->functionManager = new FunctionManager($this->tcb, $envInfo->Functions[0]);
            $this->databaseManager = new DatabaseManager($this->tcb, $envInfo->Databases[0]);
            $this->storageManager = new StorageManager($this->tcb, $envInfo->Storages[0]);
        }
    }

    /**
     * @return TCB
     */
    public function getTcbDataApi()
    {
        return $this->tcbDataApi;
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
