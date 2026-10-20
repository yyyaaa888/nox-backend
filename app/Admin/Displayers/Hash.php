<?php

namespace App\Admin\Displayers;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Displayers\AbstractDisplayer;
use Dcat\Admin\Support\Helper;

class Hash extends AbstractDisplayer
{
    protected function addScript()
    {
        $script = <<<'JS'
$('.hash-more').click(function () {
    $(this).parent('.hash-text').toggleClass('d-none').siblings().toggleClass('d-none');
});
$('.grid-column-copyable').off('click').on('click', function (e) {
    
    var content = $(this).data('content');
    
    var $temp = $('<input>');
    
    $("body").append($temp);
    $temp.val(content).select();
    document.execCommand("copy");
    $temp.remove();
    
    $(this).tooltip('show');
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

        if (strlen($this->value) > 12) {
            $value = substr($this->value, 0, 6).'...'.substr($this->value, -4);
        } else {
            $value = $this->value;
        }

        // if ($value == $this->value) {
        //     return $value;
        // }

        $html = <<<HTML
<div class="hash-text">
    <span class="text">{$value}</span>
    <a href="javascript:void(0);" class="grid-column-copyable text-muted" data-content="{$this->value}" data-title="{$this->trans('copied')}" data-placement="bottom">
        <i class="fa fa-copy"></i>
    </a>
    <a href="javascript:void(0);" class="hash-more"><i class="fa fa-angle-double-down"></i></a>
</div>
<div class="hash-text d-none">
    <span class="text">{$this->value}</span>
    <a href="javascript:void(0);" class="grid-column-copyable text-muted" data-content="{$this->value}" data-title="{$this->trans('copied')}" data-placement="bottom">
        <i class="fa fa-copy"></i>
    </a>
    <a href="javascript:void(0);" class="hash-more"><i class="fa fa-angle-double-up"></i></a>
</div>
HTML;
        if ($this->value !== '' && $this->value !== null) {
            return $html;
        } else {
            return $this->value;
        }
    }
}
