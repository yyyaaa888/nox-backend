<?php

namespace App\Admin\Forms;

use App\Models\Recharge;
use App\Models\User;
use App\Models\UserData;
use App\Models\WalletLog;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RechargeForm extends Form
{
    /**
     * Handle the form request.
     *
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        $validator = Validator::make($input, [
            'asset'   => 'required|in:'.implode(',', array_keys(Recharge::ASSET_MAP)),
            'user_id' => 'required|integer',
            'amount'  => 'required|decimal:0,6',
            'remark'  => 'nullable|string|max:100',
        ], [
            'asset.required'   => __('参数不能为空'),
            'asset.in'         => __('资产类型错误'),
            'user_id.required' => __('用户ID不能为空'),
            'user_id.integer'  => __('用户ID必须是数字'),
            'amount.required'  => __('充值金额不能为空'),
            'amount.numeric'   => __('充值金额必须是数字'),
            'remark.string'    => __('备注必须是字符串'),
            'remark.max'       => __('备注最多100个字符'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator->errors()->first());
        }
        $input = $validator->validated();

        $asset  = $input['asset'];
        $uid    = $input['user_id'];
        $amount = $input['amount'];
        $remark = $input['remark'] ?? null;

        if (bccomp($amount, 0, 6) == 0) {
            return $this->response()->error('充值金额不能为空');
        }

        $user = User::getInfo($uid);
        if (! $user) {
            return $this->response()->error('用户不存在');
        }

        $userData = UserData::getInfo($uid);
        if (! $userData) {
            return $this->response()->error('用户信息不存在');
        }

        try {
            DB::beginTransaction();
            $admin_id = auth('admin')->user()->id;

            if (bccomp($amount, 0, 6) > 0) {
                $type = Recharge::TYPE_RECHARGE; // 充值
            } else {
                $type   = Recharge::TYPE_OUT; // 扣款
                $amount = abs($amount);
            }

            if ($asset == Recharge::ASSET_USDT) { // USDT
                if ($type == Recharge::TYPE_RECHARGE) { // 充值
                    WalletLog::addLog($user->id, $amount, WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);
                } else { // 扣款
                    WalletLog::addLog($user->id, $amount, WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_FAST_OUT, WalletLog::TYPE_RECHARGE_OUT);
                }
            } elseif ($asset == Recharge::ASSET_NOX) { // NOXO
                if ($type == Recharge::TYPE_RECHARGE) { // 充值
                    WalletLog::addLog($user->id, $amount, WalletLog::SYMBOL_NOX, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);
                } else { // 扣款
                    WalletLog::addLog($user->id, $amount, WalletLog::SYMBOL_NOX, WalletLog::CHANGE_TYPE_FAST_OUT, WalletLog::TYPE_RECHARGE_OUT);
                }
            } else {
                throw new \Exception('资产类型错误');
            }

            $recharge           = new \App\Models\Recharge();
            $recharge->user_id  = $user->id;
            $recharge->admin_id = $admin_id;
            $recharge->amount   = $amount;
            $recharge->remark   = $remark;
            $recharge->type     = $type;
            $recharge->asset    = $asset;
            $recharge->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->response()->error($e->getMessage());
        }

        return $this
            ->response()
            ->success('充值成功')
            ->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->select('user_id', '用户')->options(function ($id) {
            $user = \App\Models\User::find($id);
            if ($user) {
                return [$user->id => $user->name.'('.$user->remark.')'];
            }

            return [];
        })->ajax('/api/users')->required();
        $this->radio('asset', '资产类型')->options(Recharge::ASSET_MAP)->default(Recharge::ASSET_USDT);
        $this->text('amount', '充值金额')->help('大于0为充值，小于0为扣款，最多支持6位小数')->required();
        $this->text('remark', '充值备注 100字以内')->maxLength(100);
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [];
    }

    /**
     * 是否有权限判断.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable|\Dcat\Admin\Traits\HasPermissions|null  $user
     */
    protected function authorize($user): bool
    {
        return $user->isRole('recharge') || $user->isAdministrator();
    }
}
