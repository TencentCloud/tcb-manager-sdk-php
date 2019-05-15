<?php


namespace TcbManager\Exceptions;


use Exception;

class EnvException extends Exception
{
    const CURRENT_ENVIRONMENT_IS_NULL = "CURRENT_ENVIRONMENT_IS_NULL";

    const ENV_ID_NOT_EXISTS           = "ENV_ID_NOT_EXISTS";
}
