<?php


namespace TcbManager\Tests;


use TcbManager\TcbManager;

class TestBase
{
    public static $secretId;
    public static $secretKey;
    public static $secretToken = "";
    public static $envId;

    /**
     * @var TcbManager
     */
    public static $tcb;

    public static function init()
    {
        self::$secretId = Config::$secretId;
        self::$secretKey = Config::$secretKey;
        self::$secretToken = Config::$secretToken;

        self::$envId = Config::$envId;
        static::$tcb = TcbManager::init([
            "secretId" => Config::$secretId,
            "secretKey" => Config::$secretKey,
            "secretToken" => Config::$secretToken,
            "envId" => Config::$envId
        ]);
    }
}
