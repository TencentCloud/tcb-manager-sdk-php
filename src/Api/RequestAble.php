<?php


namespace TcbManager\Api;


use Exception;
use TcbManager\Api;
use TcbManager\ApiRequest;


trait RequestAble
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @param string $action
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function request(string $action, array $params)
    {
        return $this->api->request($action, $params);
    }
}
