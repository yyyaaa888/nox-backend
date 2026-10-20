<?php

namespace App\Exceptions;

use Exception;

class ServiceException extends Exception
{
    protected $message;

    protected $code;

    public function __construct($message, $code = 10400)
    {
        parent::__construct($message, $code);
    }

    public function report()
    {
        // 不记录日志
        return false;
    }

    public function render($request)
    {
        $err = [
            'code' => $this->code,
            'msg'  => $this->message,
        ];

        return response()->json($err);
    }
}
