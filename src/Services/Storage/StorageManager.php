<?php


namespace TcbManager\Services\Storage;


use TcbManager\Api\Endpoint;
use TcbManager\Services\AbstractService;
use TcbManager\TcbManager;

class StorageManager extends AbstractService
{
    /**
     * @var strin API endpoint
     */
    protected $endpoint = "";

    /**
     * @var string API version
     */
    protected $version = "2018-11-27";

    /**
     * @var string 实例Id
     */
    public $bucket;

    /**
     * @var string CDN加速域名
     */
    public $cdnDomain;

    /**
     * @var string AppId
     */
    public $appId;
    /**
     * @var string 实例状态
     */
    public $status;

    public function __construct(TcbManager $tcb, \stdClass $instanceInfo)
    {
        parent::__construct($tcb);

        $this->bucket = $instanceInfo->Bucket;
        $this->region = $instanceInfo->Region;
        $this->cdnDomain = $instanceInfo->CdnDomain;
        $this->appId = $instanceInfo->AppId;
    }
}
