<?php

namespace TcbManager;


use Exception;

use TcbManager\Api\Endpoint;
use TencentCloudClient\TCClient;
use TencentCloudClient\Credential;
use TencentCloudClient\Http\HttpClientProfile;


class Api
{
    /**
     * @var Endpoint
     */
    protected $endpoint;

    /**
     * @var Credential secret key
     */
    private $credential;

    /**
     * @var TCClient
     */
    private $tcClient;

    /**
     * Api constructor.
     * @param string            $secretId
     * @param string            $secretKey
     * @param Endpoint          $endpoint
     * @param string            $version
     * @param string            $region 地域
     * @param HttpClientProfile $profile client配置
     */
    function __construct(
        string $secretId,
        string $secretKey,
        Endpoint $endpoint,
        string $version,
        string $region = "",
        $profile = null
    ) {
        $this->endpoint = $endpoint;
        $this->credential = new Credential($secretId, $secretKey);

        $this->tcClient = new TCClient(
            $endpoint,
            $version,
            $this->credential,
            $region,
            $profile
        );
    }

    /**
     * @param string $action
     * @param array  $params
     * @return mixed
     * @throws Exception
     */
    public function request(string $action, array $params)
    {
        return $this->tcClient->request($action, $params);
    }

    /**
     * 基于当前API的认证信息，创建新的API
     *
     * @param Endpoint          $endpoint
     * @param string            $version
     * @param string            $region
     * @param HttpClientProfile $profile client配置
     * @return Api
     */
    public function clone(
        Endpoint $endpoint,
        string $version,
        string $region = "",
        $profile = null
    )
    {
        $api = new Api(
            $this->credential->getSecretId(),
            $this->credential->getSecretKey(),
            $endpoint,
            $version,
            $region,
            $profile
        );
        return $api;
    }
}
