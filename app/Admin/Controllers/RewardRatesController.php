<?php

namespace App\Admin\Controllers;

use App\Models\Level;
use App\Models\RewardRate;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;

class RewardRatesController extends AdminController
{
    protected $title = '奖励比例';

    protected $description = [
        'invite' => '推广收益',
        'team'   => '团队收益',
    ];

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(Content $content)
    {
        $request = request();
        $tab     = $request->input('tab', 'invite');
        if ($tab == 'invite') {
            $description = '推广收益';
            $grid        = $this->inviteTab();
        } else {
            $description = '团队收益';
            $grid        = $this->teamTab();
        }

        return $content
            ->translation($this->translation())
            ->title($this->title())
            ->description($description)
            ->body($grid);
    }

    protected function inviteTab()
    {
        $grid = new Grid(new RewardRate());
        $grid->model()->where('type', RewardRate::TYPE_INVITE);

        // $grid->column('id', 'ID')->sortable();
        $grid->column('type', '类型')->sortable()->using(RewardRate::TYPE_MAP);
        $grid->column('rank', '等级')->sortable()->using(Level::LEVEL_MAP);
        $grid->column('has_node', '是否节点')->sortable()->using([0 => '基础', 1 => '持节点']);
        $grid->column('layer', '层级')->sortable();
        $grid->column('rate', '比例(%)');

        $grid->addTableClass(['table-text-center']);
        $grid->withBorder(); // 显示边框
        $grid->disableRowSelector(); // 禁用行选择按钮
        $grid->disableViewButton(); // 禁用详情按钮
        // $grid->disableEditButton(); // 禁用编辑按钮
        $grid->disableDeleteButton(); // 禁用删除按钮
        $grid->disableCreateButton(); // 禁用创建按钮
        $grid->disableBatchDelete(); // 禁用批量删除按钮
        // $grid->disableActions(); // 禁用操作按钮

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('type', '类型')->select(RewardRate::TYPE_MAP)->width(2);
            $filter->equal('rank', '等级')->select(Level::LEVEL_MAP)->width(2);
            $filter->equal('has_node', '是否节点')->select([0 => '基础', 1 => '持节点'])->width(2);
        });

        $this->makeTools($grid, 'invite');

        return $grid;
    }

    protected function teamTab()
    {
        $grid = new Grid(new RewardRate());
        $grid->model()->where('type', RewardRate::TYPE_TEAM);

        // $grid->column('id', 'ID')->sortable();
        $grid->column('type', '类型')->sortable()->using(RewardRate::TYPE_MAP);
        $grid->column('rank', '等级')->sortable()->using(Level::LEVEL_MAP);
        $grid->column('has_node', '是否节点')->sortable()->using([0 => '基础', 1 => '持节点']);
        $grid->column('layer', '层级')->sortable();
        $grid->column('rate', '比例(%)');

        $grid->addTableClass(['table-text-center']);
        $grid->withBorder(); // 显示边框
        $grid->disableRowSelector(); // 禁用行选择按钮
        $grid->disableViewButton(); // 禁用详情按钮
        // $grid->disableEditButton(); // 禁用编辑按钮
        $grid->disableDeleteButton(); // 禁用删除按钮
        $grid->disableCreateButton(); // 禁用创建按钮
        $grid->disableBatchDelete(); // 禁用批量删除按钮
        // $grid->disableActions(); // 禁用操作按钮

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('type', '类型')->select(RewardRate::TYPE_MAP)->width(2);
            $filter->equal('rank', '等级')->select(Level::LEVEL_MAP)->width(2);
            $filter->equal('has_node', '是否节点')->select([0 => '基础', 1 => '持节点'])->width(2);
        });

        $this->makeTools($grid, 'team');

        return $grid;
    }

    protected function form()
    {
        return Form::make(new RewardRate(), function (Form $form) {
            if ($form->isEditing()) {
                $form->display('type', '类型')->customFormat(function ($value) {
                    return RewardRate::TYPE_MAP[$value] ?? $value;
                });
                $form->display('rank', '等级')->customFormat(function ($value) {
                    return Level::LEVEL_MAP[$value] ?? $value;
                });
                $form->display('has_node', '是否节点')->customFormat(function ($value) {
                    return $value === 0 ? '基础' : '持节点';
                });
                $form->display('layer', '层级');
                $form->decimal('rate', '比例(%)')->required()->rules('required|decimal:0,2|min:0|max:100');
            }
            // 不显示删除按钮
            $form->disableDeleteButton();
            // 不显示查看按钮
            $form->disableViewButton();
            // 底部不显示查看操作
            $form->disableViewCheck();
            $form->saving(function (Form $form) {
                if (! $form->isEditing()) {
                    return $form->response()->error('只支持编辑');
                }
            });
        });
    }

    private function makeTools(Grid $grid, string $tab)
    {
        $tabs = [
            [
                'tab'   => 'invite',
                'title' => '推广收益',
            ],
            [
                'tab'   => 'team',
                'title' => '团队收益',
            ],
        ];
        $tools = '<div class="btn-group">';
        foreach ($tabs as $item) {
            $url = admin_url('rewardRates').'?tab='.$item['tab'];
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
