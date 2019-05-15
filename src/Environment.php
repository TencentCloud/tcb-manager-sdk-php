<?php

namespace TcbManager;


use TcbManager\Api\RequestAble;
use TcbManager\Exceptions\EnvException;
use TcbManager\Exceptions\TcbException;
use TcbManager\Services\Database\DatabaseManager;
use TcbManager\Services\Storage\StorageManager;
use TcbManager\Services\Functions\FunctionManager;

/**
 * Class Environment
 * @package TcbManager
 */
class Environment {
    use RequestAble;

    private $id;
    private $tcb;

    /**
     * @var FunctionManager
     */
    private $functionManagers = [];
    private $functionManager;

    /**
     * Environment constructor.
     * @param string $id
     * @param TcbManager $tcb
     * @throws EnvException
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

        if (isset($result->EnvList) and count($result->EnvList) === 1) {
            $envInfo = $result->EnvList[0];
            // $this->database = new Database($this->tcb, $envInfo['Databases'][0]);
            // $this->storage = new Storage($this->tcb, $envInfo['Storages'][0]);
            $this->functionManager = new FunctionManager($this->tcb, $envInfo->Functions[0]);
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $namespace
     * @return FunctionManager
     */
    public function getFunctionManager(string $namespace = "")
    {
        return $this->functionManager;
    }

    /**
     * @return mixed
     */
    public function describe()
    {
        return $this->request("DescribeEnvs", [
            "EnvId" => $this->id
        ]);
    }
}
