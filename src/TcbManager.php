<?php

namespace TcbManager;

use InvalidArgumentException;
use TcbManager\Exceptions\EnvException;
use TcbManager\Api\Api;
use TcbManager\Api\Endpoint;
use TcbManager\Exceptions\TcbException;
use TcbManager\Services\Database\DatabaseManager;
use TcbManager\Services\Functions\FunctionManager;
use TencentCloudClient\Credential;
use TcbManager\Services\Storage\StorageManager;

/**
 * Class TcbManager
 * @package TcbManager
 */
class TcbManager
{
    private $config = [
        "secretId" => "",
        "secretKey" => "",
        "secretToken" => ""
    ];

    /**
     * @return Api
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @var EnvironmentManager
     */
    private $environmentManager;

    /**
     * @return EnvironmentManager
     */
    public function getEnvironmentManager(): EnvironmentManager
    {
        return $this->environmentManager;
    }

    /**
     * @var TcbManager
     */
    public static $tcb = null;

    /**
     * 初始化默认实例，注意
     * @param array $options
     * @return TcbManager
     * @throws TcbException | InvalidArgumentException
     */
    public static function init(array $options): TcbManager
    {
        if (!is_null(static::$tcb)) {
            return self::$tcb;
        }

        static::$tcb = new TcbManager($options);
        return static::$tcb;
    }

    /**
     * TcbManager constructor.
     *
     * @param array $options
     *
     * @throws EnvException
     * @throws TcbException
     */
    public function __construct(array $options)
    {
        // NOTE: 参数中必须同时存在 secretId 和 secretKey
        if (array_key_exists("secretId", $options)
            && array_key_exists("secretKey", $options)) {
            $this->config["secretId"] = $options['secretId'];
            $this->config["secretKey"] = $options['secretKey'];

            if (array_key_exists("secretToken", $options)) {
                $this->config["secretToken"] = $options['secretToken'];
            }
        }
        else {
            if (Runtime::isInSCF()) {
                // 云函数运行环境中应保证
                $this->config["secretId"] = getenv(Constants::ENV_SECRETID);
                $this->config["secretKey"] = getenv(Constants::ENV_SECRETKEY);

                if (getenv(Constants::ENV_SESSIONTOKEN)) {
                    $this->config["secretToken"] = getenv(Constants::ENV_SESSIONTOKEN);
                }

                if (empty($this->config["secretId"])
                    || empty($this->config["secretKey"])) {
                    throw new TcbException(TcbException::MISS_SECRET_INFO_IN_ENV);
                }
            }
            else {
                throw new TcbException(TcbException::MISS_SECRET_INFO_IN_ARGS);
            }
        }

        $this->api = new Api(
            new Credential(
                $this->config["secretId"],
                $this->config["secretKey"],
                $this->config["secretToken"]
            ),
            Endpoint::TCB,
            "2018-06-08"
        );

        $this->environmentManager = new EnvironmentManager($this);

        // 如果参数中存在 envId，则设置增加环境
        if (array_key_exists("envId", $options)) {
            $this->environmentManager->add($options['envId']);
        }
    }

    /**
     * 增加环境
     * @param string $envId
     * @throws TcbException
     */
    public function addEnvironment(string $envId)
    {
        $this->environmentManager->add($envId);
    }

    /**
     * 获取当前环境
     * @return Environment
     * @throws EnvException
     */
    public function currentEnvironment(): Environment
    {
        return $this->getEnvironmentManager()->getCurrent();
    }

    /**
     * @param string $namespace
     * @return FunctionManager
     * @throws EnvException
     */
    public function getFunctionManager(string $namespace = ""): FunctionManager
    {
        return $this->currentEnvironment()->getFunctionManager($namespace);
    }

    /**
     * @param string $bucket
     * @return StorageManager
     * @throws EnvException
     */
    public function getStorageManager(string $bucket = ""): StorageManager
    {
        return $this->currentEnvironment()->getStorageManager($bucket);
    }

    /**
     * @param string $instanceId
     * @return DatabaseManager
     * @throws EnvException
     */
    public function getDatabaseManager(string $instanceId = ""): DatabaseManager
    {
        return $this->currentEnvironment()->getDatabaseManager($instanceId);
    }
}
