<?php

namespace App\Admin\Extensions\GridSortable;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Tools\AbstractTool;

class SaveOrderButton extends AbstractTool
{
    protected $sortColumn;

    protected $url;

    public function __construct($column, $url)
    {
        $this->sortColumn = $column;
        $this->url        = $url;
    }

    protected function script()
    {
        $script = <<<JS
$('.grid-save-order-btn').click(function () {
    $.post({
        url: '{$this->url}',
        data: {
            _sort: $(this).data('sort'),
            _column: '{$this->sortColumn}',
        },
        success: function(data){
            if (data.status) {
                Dcat.success(data.message);
                Dcat.reload();
            } else {
                Dcat.error(data.message);
            }
        }
    });
});
    
JS;
        Admin::script($script);
    }

    public function render()
    {
        $this->script();

        $text = admin_trans_label('Save order');

        return <<<HTML
<button type="button" class="btn btn-primary btn-custom grid-save-order-btn" style="margin-left:8px;display:none;">
    <i class="fa fa-save"></i><span class="hidden-xs">&nbsp;&nbsp;{$text}</span>
</button>
HTML;
    }
}
