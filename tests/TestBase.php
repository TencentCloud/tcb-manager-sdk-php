<?php


namespace TcbManager\Tests;


use TcbManager\Constants;
use TcbManager\TcbManager;

class TestBase
{
    public static $secretId;
    public static $secretKey;
    public static $secretToken;
    public static $envId;

    /**
     * @var TcbManager
     */
    public static $tcb;

    public static function init()
    {
        if (getenv("CI", true) == "true"
            && getenv("TRAVIS", true) == "true") {
            // CI 环境
            self::$secretId = getenv(Constants::ENV_SECRETID, true);
            self::$secretKey = getenv(Constants::ENV_SECRETKEY, true);
            self::$secretToken = getenv(Constants::ENV_SESSIONTOKEN, true);
            self::$envId = getenv(Constants::ENV_TCB_ENV_ID, true);
        }
        else {
            // 本地环境
            self::$secretId = Config::$secretId;
            self::$secretKey = Config::$secretKey;
            self::$secretToken = Config::$secretToken;
            self::$envId = Config::$envId;
        }

        static::$tcb = TcbManager::init([
            "secretId" => Config::$secretId,
            "secretKey" => Config::$secretKey,
            "secretToken" => Config::$secretToken,
            "envId" => Config::$envId
        ]);
    }
}
