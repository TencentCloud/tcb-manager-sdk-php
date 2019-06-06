<?php


namespace TcbManager\Exceptions;


use Exception;

class TcbException extends Exception
{
    const MISS_SECRET_INFO_IN_ENV = "MISS_SECRET_INFO_IN_ENV";
    const MISS_SECRET_INFO_IN_ARGS = "MISS_SECRET_INFO_IN_ARGS";
}
