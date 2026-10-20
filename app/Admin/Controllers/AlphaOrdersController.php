<?php

namespace App\Admin\Controllers;

use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;

/**
 * @property string $rel_reward
 * @property string $quota_reward
 */
class AlphaOrdersController extends AdminController
{
    protected $title = '资产包订单';

    protected $description = [
        'index' => '已支付',
    ];

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(Content $content)
    {
        $request = request();
        $tab     = $request->input('tab', 'index');
        if ($tab == 'index') {
            $description = '已支付';
            $grid        = $this->indexTab();
        } else {
            $description = '未支付';
            $grid        = $this->nopayTab();
        }

        return $content
            ->translation($this->translation())
            ->title($this->title())
            ->description($description)
            ->body($grid);
    }

    protected function indexTab()
    {
        $grid = new Grid(new AlphaOrder());
        $grid->model()->orderByDesc('id');
        $grid->model()->whereIn('status', [AlphaOrder::STATUS_PAID, AlphaOrder::STATUS_DONE, AlphaOrder::STATUS_EXITED]);

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '用户地址')->address();
        $grid->column('sn', '单号')->copyable();
        $grid->column('product.name', '产品')->trim();
        $grid->column('amount', '本金')->number();
        $grid->column('power_am', '算力值')->number();
        $grid->column('progress', '进度')->display(function () {
            return removeTrailingZeros($this->rel_reward).' / '.removeTrailingZeros($this->quota_reward);
        });
        $grid->column('status', '状态')->using(AlphaOrder::STATUS_MAP)->dot(AlphaOrder::STATUS_COLOR);
        $grid->column('trial_end', '试用截止');
        $grid->column('created_at', '创建时间')->sortable();

        $grid->addTableClass(['table-text-center']);
        $grid->withBorder(); // 显示边框
        $grid->disableRowSelector(); // 禁用行选择按钮
        $grid->disableViewButton(); // 禁用详情按钮
        $grid->disableEditButton(); // 禁用编辑按钮
        $grid->disableDeleteButton(); // 禁用删除按钮
        $grid->disableCreateButton(); // 禁用创建按钮
        $grid->disableBatchDelete(); // 禁用批量删除按钮
        $grid->disableActions(); // 禁用操作按钮

        $grid->filter(function (Grid\Filter $filter) {
            $filter->like('user.name', '用户名')->width(2);
            $filter->like('user.address', '用户地址')->width(2);
            $filter->like('sn', '单号')->width(2);
            $filter->equal('product.id', '产品')->select(AlphaProduct::query()->pluck('name', 'id'))->width(2);
            $filter->equal('status', '状态')->select(AlphaOrder::STATUS_MAP)->width(2);
            $filter->between('created_at', '创建时间')->datetime()->width(4);
        });
        $this->makeTools($grid, 'index');

        return $grid;
    }

    protected function nopayTab()
    {
        $grid = new Grid(new AlphaOrder());
        $grid->model()->orderByDesc('id');
        $grid->model()->whereIn('status', [AlphaOrder::STATUS_WAIT, AlphaOrder::STATUS_CANCEL]);

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '用户地址')->address();
        $grid->column('sn', '单号')->copyable();
        $grid->column('product.name', '产品')->trim();
        $grid->column('amount', '本金')->number();
        $grid->column('power_am', '算力值')->number();
        $grid->column('status', '状态')->using(AlphaOrder::STATUS_MAP)->dot(AlphaOrder::STATUS_COLOR);
        $grid->column('fail_at', '取消时间');
        $grid->column('created_at', '创建时间')->sortable();

        $grid->addTableClass(['table-text-center']);
        $grid->withBorder(); // 显示边框
        $grid->disableRowSelector(); // 禁用行选择按钮
        $grid->disableViewButton(); // 禁用详情按钮
        $grid->disableEditButton(); // 禁用编辑按钮
        $grid->disableDeleteButton(); // 禁用删除按钮
        $grid->disableCreateButton(); // 禁用创建按钮
        $grid->disableBatchDelete(); // 禁用批量删除按钮
        $grid->disableActions(); // 禁用操作按钮

        $grid->filter(function (Grid\Filter $filter) {
            $filter->like('user.name', '用户名')->width(2);
            $filter->like('user.address', '用户地址')->width(2);
            $filter->like('sn', '单号')->width(2);
            $filter->equal('product.id', '产品')->select(AlphaProduct::query()->pluck('name', 'id'))->width(2);
            $filter->equal('status', '状态')->select(AlphaOrder::STATUS_MAP)->width(2);
            $filter->between('fail_at', '取消时间')->datetime()->width(4);
            $filter->between('created_at', '创建时间')->datetime()->width(4);
        });
        $this->makeTools($grid, 'nopay');

        return $grid;
    }

    private function makeTools(Grid $grid, string $tab)
    {
        $tabs = [
            [
                'tab'   => 'index',
                'title' => '已支付',
            ],
            [
                'tab'   => 'nopay',
                'title' => '未支付',
            ],
        ];
        $tools = '<div class="btn-group">';
        foreach ($tabs as $item) {
            $url = admin_url('alphaOrders').'?tab='.$item['tab'];
            if ($item['tab'] == $tab) {
                $tools .= '<a class="btn btn-success" href="'.$url.'">'.$item['title'].'</a>';
            } else {
                $tools .= '<a class="btn btn-default" href="'.$url.'">'.$item['title'].'</a>';
            }
        }
        $tools .= '</div>';
        $grid->tools($tools);
    }
}
