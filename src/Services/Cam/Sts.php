<?php


namespace TcbManager\Services\Cam;


use TcbManager\Api\Endpoint;
use TcbManager\Services\AbstractService;
use TcbManager\TcbManager;

class Sts extends AbstractService
{
    /**
     * @var string API endpoint
     */
    protected $endpoint = Endpoint::SCF;

    /**
     * @var string API version
     */
    protected $version = "2018-08-13";

    /**
     * @var string 实例Id
     */
    public $namespace;

    /**
     * @var string
     */
    protected $region = "ap-shanghai";

    /**
     * @var string 实例状态
     */
    public $status;

    public function __construct(TcbManager $tcb, \stdClass $instanceInfo)
    {
        parent::__construct($tcb);

        $this->namespace = $instanceInfo->Namespace;
    }
}
