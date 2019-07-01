<?php

namespace TcbManager;


use TcbManager\Api\RequestAble;
use TcbManager\Exceptions\EnvException;
use TcbManager\Exceptions\TcbException;

/**
 * Class EnvironmentManager
 * @package TcbManager
 */
class EnvironmentManager {
    use RequestAble;

    /**
     * 环境列表
     * @var Environment[]
     */
    private $envs = [];

    /**
     * 当前环境
     * @var Environment
     */
    private $current = null;

    /**
     * @var TcbManager
     */
    private $tcb;

    public function __construct(TcbManager $tcb)
    {
        $this->tcb = $tcb;
        $this->api = $tcb->getApi();
    }

    /**
     * 获取当前环境
     * @return Environment
     * @throws EnvException
     */
    public function getCurrent(): Environment
    {
        if (is_null($this->current)) {
            throw new EnvException(EnvException::CURRENT_ENVIRONMENT_IS_NULL);
        }
        return $this->current;
    }

    /**
     * @param string $envId
     *
     * @return bool
     * @throws EnvException
     */
    public function add(string $envId)
    {
        if (!array_key_exists($envId, $this->envs)) {
            $this->envs[$envId] = new Environment($envId, $this->tcb);;
        }
        if (is_null($this->current)) {
            $this->current = $this->envs[$envId];
        }
        return True;
    }

    /**
     * @param string $envId 环境ID
     */
    public function remove(string $envId)
    {
        unset($this->envs[$envId]);
    }

    /**
     * 获取指定环境
     * @param string $envId
     * @return Environment|null
     */
    public function get(string $envId)
    {
        if (array_key_exists($envId, $this->envs)) {
            return $this->envs[$envId];
        }
        return null;
    }

    /**
     * 切换默认环境
     * @param string $envId 环境ID
     * @return bool 成功返回 True，失败返回 False，环境为添加的情况会失败。
     */
    public function switchEnv(string $envId): bool
    {
        if (array_key_exists($envId, $this->envs)) {
            $this->current = $this->envs[$envId];
            return True;
        }
        return False;
    }

    /**
     * 检查环境ID是否被占用，为保持一致，该接口不处理数据返回数据
     *
     * @param string $envId
     * @return array
     */
    public function checkEnvId(string $envId): \stdClass
    {
        $result = $this->request("CheckEnvId", [
            "EnvId" => $envId,
        ]);
        return $result;
    }

    /**
     * 获取环境信息
     * @param string [$envId]
     * @return mixed
     * @throws
     */
    public function describeEnvs(string $envId)
    {
        $options = [];
        if (isset($envId)) {
           $options = ["EnvId" => $envId];
        }
        return $this->request("DescribeEnvs", $options);
    }
}
