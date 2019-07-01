<?php


namespace TcbManager\Api;


use TencentCloudClient\Exception\TCException;


trait RequestAble
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @param string $action
     * @param array $params
     * @return mixed
     * @throws TCException
     */
    public function request(string $action, array $params)
    {
        return $this->api->request($action, $params);
    }
}
