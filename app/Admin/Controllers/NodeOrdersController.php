<?php

namespace App\Admin\Controllers;

use App\Models\NodeOrder;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;

class NodeOrdersController extends AdminController
{
    protected $title = '节点订单';

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
        $grid = new Grid(new NodeOrder());
        $grid->model()->orderByDesc('id');
        $grid->model()->where('status', NodeOrder::STATUS_PAID);

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '用户地址')->address();
        $grid->column('sn', '单号')->copyable();
        $grid->column('amount', '总金额')->number();
        $grid->column('num', '数量')->number();
        $grid->column('price', '单价')->number();
        $grid->column('pay_at', '支付时间');
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
            $filter->between('pay_at', '支付时间')->datetime()->width(4);
            $filter->between('created_at', '创建时间')->datetime()->width(4);
        });
        $this->makeTools($grid, 'index');

        return $grid;
    }

    protected function nopayTab()
    {
        $grid = new Grid(new NodeOrder());
        $grid->model()->orderByDesc('id');
        $grid->model()->whereIn('status', [NodeOrder::STATUS_WAIT, NodeOrder::STATUS_CANCEL]);

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '用户地址')->address();
        $grid->column('sn', '单号')->copyable();
        $grid->column('amount', '总金额')->number();
        $grid->column('num', '数量')->number();
        $grid->column('price', '单价')->number();
        $grid->column('status', '状态')->using(NodeOrder::STATUS_MAP)->dot(NodeOrder::STATUS_COLOR);
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
            $filter->equal('status', '状态')->select(NodeOrder::STATUS_MAP)->width(2);
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
            $url = admin_url('nodeOrders').'?tab='.$item['tab'];
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
