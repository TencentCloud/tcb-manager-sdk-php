<?php


namespace TcbManager\Services;

use TcbManager\Api\Endpoint;
use TcbManager\Api\RequestAble;
use TcbManager\TcbManager;

class AbstractService
{
    use RequestAble;

    protected $endpoint;
    protected $version;
    protected $region = "";
    protected $profile = null;

    /**
     * @var TcbManager
     */
    protected $tcb;

    public function __construct(TcbManager $tcb)
    {
        $this->tcb = $tcb;
        $this->api = $tcb->getApi()->clone(
            $this->endpoint,
            $this->version,
            $this->region,
            $this->profile
        );
    }
}
