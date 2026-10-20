<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Traits\HasFormResponse;

class BaseController extends AdminController
{
    use HasFormResponse;

    protected function successReturn($msg)
    {
        return $this->sendResponse(
            $this->response()
                ->success($msg)
                ->refreshIf(true)
        );
    }

    protected function errorReturn($msg)
    {
        return $this->sendResponse(
            $this->response()
                ->error($msg)
                ->refreshIf(true)
        );
    }
}
