<?php

namespace App\Admin\Controllers;

use App\Models\UserData;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 * @property int $node_id
 */
class UserDatasController extends AdminController
{
    protected $title = '用户数据';

    protected function grid()
    {
        return Grid::make(new UserData(['user']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user.address', '用户')->address();
            $grid->column('node_num', '节点数');
            $grid->column('current_slot', '额度槽')->number();
            $grid->column('alpha_quota_used', '额度槽已用')->number();
            $grid->column('alpha_frozen', '额度槽冻结')->number();

            $grid->column('total_stake', '累计质押')->number();
            $grid->column('curr_stake', '当前质押')->number();
            $grid->column('total_power', '累计算力值')->number();
            $grid->column('curr_power', '算力值')->number();
            $grid->column('total_usdt', '累计USDT')->number();
            $grid->column('total_nox', '累计NOXO')->number();
            $grid->column('node_reward', '节点分红')->number();
            $grid->column('stake_reward', '质押收益')->number();
            $grid->column('invite_reward', '邀请奖励')->number();
            $grid->column('team_reward', '团队奖励')->number();
            $grid->column('fee_fh', '手续费分红')->number();

            $grid->column('created_at')->sortable();

            $grid->addTableClass(['table-text-center']);
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用复选框
            $grid->disableViewButton(); // 禁用详情按钮
            $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            $grid->disableActions(); // 禁用

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id', '用户ID')->width(2);
                $filter->like('user.address', '用户钱包')->width(2);
                $filter->like('user.name', '用户名')->width(2);
                $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }
}
