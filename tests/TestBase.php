<?php


namespace TcbManager\Tests;


use TcbManager\TcbManager;

class TestBase
{
    public static $secretId;
    public static $secretKey;
    public static $envId;

    /**
     * @var TcbManager
     */
    public static $tcb;

    public static function init()
    {
        self::$secretId = Config::$secretId;
        self::$secretKey = Config::$secretKey;
        self::$envId = Config::$envId;
        static::$tcb = TcbManager::init([
            "secretId" => Config::$secretId,
            "secretKey" => Config::$secretKey,
            "envId" => Config::$envId
        ]);
    }
}
