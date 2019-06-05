<?php

namespace TcbManager\Api;


use TcbManager\Version;
use TencentCloudClient\Exception\TCException;
use TencentCloudClient\TCClient;
use TencentCloudClient\Credential;
use TencentCloudClient\Http\HttpClientProfile;


class Api
{
    /**
     * @var string
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
     * @param Credential        $credential 凭证
     * @param string            $endpoint
     * @param string            $version
     * @param string            $region     地域
     * @param HttpClientProfile $profile client配置
     */
    function __construct(
        Credential $credential,
        string $endpoint,
        string $version,
        string $region = "",
        $profile = null
    ) {
        TCClient::setVersion(Version::VERSION);

        $this->endpoint = $endpoint;
        $this->credential = $credential;

        $this->tcClient = new TCClient(
            $endpoint,
            $version,
            $credential,
            $region,
            $profile
        );
    }

    public function getCredential()
    {
        return $this->credential;
    }

    /**
     * @param string $action
     * @param array  $params
     * @return mixed
     * @throws TCException
     */
    public function request(string $action, array $params)
    {
        return $this->tcClient->request($action, $params);
    }

    /**
     * 基于当前API的认证信息，创建新的API
     *
     * @param string            $endpoint
     * @param string            $version
     * @param string            $region
     * @param HttpClientProfile $profile client配置
     * @return Api
     */
    public function clone(
        string $endpoint,
        string $version,
        string $region = "",
        $profile = null
    )
    {
        $api = new Api(
            $this->credential,
            $endpoint,
            $version,
            $region,
            $profile
        );
        return $api;
    }
}
