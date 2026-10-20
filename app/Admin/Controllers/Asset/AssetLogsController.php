<?php

namespace App\Admin\Controllers\Asset;

use App\Models\WalletLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 * @property string $before_amount
 * @property string $after_amount
 * @property string $before_freeze
 * @property string $after_freeze
 * @property string $change_type
 * @property string $amount
 */
class AssetLogsController extends AdminController
{
    protected $title = '资产日志';

    protected function grid()
    {
        return Grid::make(new WalletLog(['user']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user.address', '用户地址')->address();
            // $grid->column('user.name', '用户名')->display(function () {
            //     if (!$this->user) {
            //         return null;
            //     }
            //     if ($this->user->name && $this->user->remark) {
            //         return $this->user->name . '(' . $this->user->remark . ')';
            //     }
            //     return $this->user->name ?? null;
            // });
            $grid->column('symbol', '资产类型')->using(WalletLog::SYMBOL_MAP);
            $grid->column('change_type', '变动类型')->using(WalletLog::CHANGE_TYPE_MAP)->label(WalletLog::CHANGE_TYPE_COLOR);
            $grid->column('amount', '变动金额')->display(function () {
                $amount = removeTrailingZeros($this->amount);
                switch ($this->change_type) {
                    case WalletLog::CHANGE_TYPE_IN:
                        return '+'.$amount;
                    case WalletLog::CHANGE_TYPE_OUT:
                        return '-'.$amount;
                    case WalletLog::CHANGE_TYPE_FAST_OUT:
                        return '-'.$amount;
                    case WalletLog::CHANGE_TYPE_FREEZE:
                        return $amount;
                    case WalletLog::CHANGE_TYPE_BACK:
                        return $amount;
                }

                return $amount;
            });
            $grid->column('before_amount', '变动前可用金额')->number();
            $grid->column('before_freeze', '变动前冻结金额')->number();
            $grid->column('after_amount', '变动后可用金额')->number();
            $grid->column('after_freeze', '变动后冻结金额')->number();
            $grid->column('type', '事件类型')->using(WalletLog::TYPE_MAP);

            $grid->column('created_at')->sortable();

            $grid->export()->rows(function ($rows) { // 导出
                foreach ($rows as &$row) {
                    if (isset($row['user'])) {
                        if ($row['user']['remark']) {
                            $row['user.name'] = $row['user']['name'].'('.$row['user']['remark'].')';
                        } else {
                            $row['user.name'] = $row['user']['name'];
                        }
                    } else {
                        $row['user.name'] = '';
                    }
                    $row['symbol']        = WalletLog::SYMBOL_MAP[$row['symbol']] ?? null;
                    $row['change_type']   = WalletLog::CHANGE_TYPE_MAP[$row['change_type']] ?? null;
                    $row['type']          = WalletLog::TYPE_MAP[$row['type']] ?? null;
                    $row['amount']        = removeTrailingZeros($row['amount']);
                    $row['before_amount'] = removeTrailingZeros($row['before_amount']);
                    $row['before_freeze'] = removeTrailingZeros($row['before_freeze']);
                    $row['after_amount']  = removeTrailingZeros($row['after_amount']);
                    $row['after_freeze']  = removeTrailingZeros($row['after_freeze']);
                }

                return $rows;
            })->filename('资产日志_'.date('Ymd_His'));

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
                $filter->like('user.address', '用户地址')->width(2);
                $filter->like('user.name', '用户名')->width(2);
                $filter->equal('symbol', '资产类型')->select(WalletLog::SYMBOL_MAP)->width(2);
                $filter->equal('change_type', '变动类型')->select(WalletLog::CHANGE_TYPE_MAP)->width(2);
                $filter->equal('type', '事件类型')->select(WalletLog::TYPE_MAP)->width(2);
                $filter->between('created_at', '创建时间')->datetime()->width(4); // 创建时间筛选
            });
        });
    }
}
