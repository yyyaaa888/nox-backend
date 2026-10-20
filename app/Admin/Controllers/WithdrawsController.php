<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\WithdrawAudite;
use App\Admin\Extensions\WithdrawReject;
use App\Models\WalletLog;
use App\Models\Withdraw;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 */
class WithdrawsController extends AdminController
{
    protected $title = '提现记录';

    protected function grid()
    {
        return Grid::make(new Withdraw(['user']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user.address', '用户地址')->address();
            $grid->column('sn', '单号');
            $grid->column('symbol', '提现账户')->using(WalletLog::SYMBOL_MAP);
            // $grid->column('withdraw_mode', '提现模式')->using(Withdraw::WITHDRAW_MODE_MAP);
            $grid->column('amount', '提现金额')->number();
            $grid->column('real_am', '到手金额')->number()->help('到手金额 = 提现金额 - 手续费');
            $grid->column('fee', '手续费')->number();
            $grid->column('fee_rate', '手续费率(%)')->number();
            $grid->column('status', '状态')->using(Withdraw::STATUS_MAP)->dot(Withdraw::STATUS_COLOR);
            $grid->column('hash', '交易号')->hash();

            $grid->column('created_at')->sortable();

            $grid->export()->rows(function ($rows) { // 导出
                foreach ($rows as &$row) {
                    if (isset($row['user'])) {
                        $row['user.address'] = $row['user']['address'] ?? null;
                    } else {
                        $row['user.address'] = '';
                    }
                    $row['symbol']   = WalletLog::SYMBOL_MAP[$row['symbol']] ?? null;
                    $row['amount']   = removeTrailingZeros($row['amount']);
                    $row['real_am']  = removeTrailingZeros($row['real_am']);
                    $row['fee']      = removeTrailingZeros($row['fee']);
                    $row['fee_rate'] = removeTrailingZeros($row['fee_rate']);
                    $row['status']   = Withdraw::STATUS_MAP[$row['status']] ?? null;
                }

                return $rows;
            })->filename('提现记录_'.date('Ymd_His'));

            $grid->addTableClass(['table-text-center']);
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用行选择按钮
            $grid->disableViewButton(); // 禁用详情按钮
            $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            $grid->disableActions(); // 禁用操作按钮
            // $grid->actions([
            //     new WithdrawAudite(),
            //     new WithdrawReject(),
            // ]);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('user.name', '用户名')->width(2);
                $filter->like('user.address', '用户地址')->width(2);
                $filter->like('sn', '单号')->width(2);
                $filter->equal('symbol', '提现账户')->select([
                    WalletLog::SYMBOL_USDT => WalletLog::SYMBOL_MAP[WalletLog::SYMBOL_USDT],
                    WalletLog::SYMBOL_NOX  => WalletLog::SYMBOL_MAP[WalletLog::SYMBOL_NOX],
                ])->width(2);
                // $filter->equal('withdraw_mode', '提现模式')->select(Withdraw::WITHDRAW_MODE_MAP)->width(2);
                $filter->equal('status', '状态')->select(Withdraw::STATUS_MAP)->width(2);
                $filter->like('hash', '交易号')->width(2);
                $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }
}
