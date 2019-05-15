<?php


namespace TcbManager\Services\Database;


use TcbManager\Api\Endpoint;
use TcbManager\Services\AbstractService;
use TcbManager\TcbManager;

class DatabaseManager extends AbstractService
{
    /**
     * @var string API endpoint
     */
    protected $endpoint = "";

    /**
     * @var string API version
     */
    protected $version = "2018-11-27";

    /**
     * @var string
     */
    protected $region = "";

    /**
     * @var string 实例Id
     */
    public $instanceId;

    /**
     * @var string 实例状态
     */
    public $status;

    public function __construct(TcbManager $tcb, \stdClass $instanceInfo)
    {
        parent::__construct($tcb);

        $this->instanceId = $instanceInfo->InstanceId;
        $this->region = $instanceInfo->Region;
        $this->status = $instanceInfo->Status;
    }
}
