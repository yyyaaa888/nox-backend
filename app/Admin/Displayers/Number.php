<?php

namespace App\Admin\Displayers;

use Dcat\Admin\Grid\Displayers\AbstractDisplayer;
use Dcat\Admin\Support\Helper;

class Number extends AbstractDisplayer
{
    public function display()
    {
        $this->value = Helper::htmlEntityEncode($this->value);

        return removeTrailingZeros($this->value);
    }
}
