<?php


namespace TcbManager;


class Runtime
{
    /**
     * 判断当前环境是否在云函数中
     * @return bool
     */
    public static function isInSCF(): bool
    {
        return getenv(Constants::ENV_RUNENV) === 'SCF';
    }
}
