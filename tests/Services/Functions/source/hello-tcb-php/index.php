<?php

function main_handler($event, $context) {
    var_dump($event);
    var_dump($context);
    return "hello tcb from php";
}
