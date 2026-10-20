<?php

namespace App\Services;

class Service
{
    //
    public function __construct()
    {
        if (method_exists(get_class($this), '_init')) {
            $this->_init();
        }
    }

    // public function _init() {}
}
