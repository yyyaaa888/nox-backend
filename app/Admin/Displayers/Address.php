<?php

namespace App\Admin\Displayers;

use App\Services\UserService;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Displayers\AbstractDisplayer;
use Dcat\Admin\Support\Helper;

class Address extends AbstractDisplayer
{
    protected function addScript()
    {
        $script = <<<'JS'
$('.address-more').click(function () {
    $(this).parent('.address-text').toggleClass('d-none').siblings().toggleClass('d-none');
});
JS;

        Admin::script($script);
    }

    public function display()
    {
        $this->value = Helper::htmlEntityEncode($this->value);

        // // 数组
        // if ($this->value !== null && ! is_scalar($this->value)) {
        //     return $this->value;
        // }

        // 字符串
        $this->addScript();

        $value = UserService::hideStr($this->value);

        // if ($value == $this->value) {
        //     return $value;
        // }

        return <<<HTML
<div class="address-text">
    <span class="text">{$value}</span>
    &nbsp;<a href="javascript:void(0);" class="address-more">&nbsp;<i class="fa fa-angle-double-down"></i></a>
</div>
<div class="address-text d-none">
    <span class="text">{$this->value}</span>
    &nbsp;<a href="javascript:void(0);" class="address-more">&nbsp;<i class="fa fa-angle-double-up"></i></a>
</div>
HTML;
    }
}
