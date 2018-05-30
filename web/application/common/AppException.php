<?php

namespace app\common;

use think\Exception;

class AppException extends Exception {

    public function __construct($message)
    {
        $this->message  = $message;
        $this->code     = 0;
    }
}