<?php

namespace App\Admin\Extensions\GridSortable;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;

class SortableAction extends RowAction
{
    protected static $js = [
        'vendor/dcat-admin-extensions/grid-sortable/sortable.min.js',
    ];

    protected function script()
    {
        $id = $this->parent->getTableId();
        Admin::js(static::$js);

        return <<<JS
new Sortable($("#{$id} tbody")[0], {
    handle: '.grid-sortable-handle', // handle's class
    animation: 150,
    onUpdate: function () {
        var sorts = [], tb = $('#{$id}');
        tb.find('.grid-sortable-handle').each(function () {
            sorts.push($(this).data());
        });
        tb.closest('.row').first().find('.grid-save-order-btn').data('sort', sorts).show();
    },
});
JS;
    }

    protected function getRowSort($sortName)
    {
        return $this->row->{$sortName};
    }

    /**
     * @return string
     */
    protected function html()
    {
        $this->script();

        $key  = $this->getKey();
        $sort = $this->getRowSort('order');

        return <<<HTML
<a class="grid-sortable-handle" style="cursor:move;" data-key="{$key}" data-sort="{$sort}">
   <i class="fa fa-ellipsis-v"></i>
   <i class="fa fa-ellipsis-v"></i>
</a>
HTML;
    }
}
