<?php

namespace App\Exceptions;

use App\ErrorCode;
use Exception;

class LogicException extends Exception
{
    //
    public function __construct(string $message = 'Logic exception.', $code = ErrorCode::CODE_DEFAULT)
    {

        parent::__construct($message, $code);
    }

    public function report()
    {
        return false;
    }

    public function render()
    {
        $result = [
            'code' => $this->getCode(),
            'msg'  => $this->getMessage(),
            'data' => [],
        ];

        return response()->json($result);
    }
}
