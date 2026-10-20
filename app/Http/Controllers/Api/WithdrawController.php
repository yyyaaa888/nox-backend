<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Chain;
use App\Models\Level;
use App\Models\User;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Models\Withdraw;
use App\Services\UserService;
use App\Services\Web3\EthService;
use App\Services\Web3\SignService;
use App\Services\WithdrawOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WithdrawController extends Controller
{
    const CACHE_KEY = 'user_withdraw_';

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|in:USDT,NOX',
        ], [
            'symbol.required' => __('提现类型不能为空'),
            'symbol.in'       => __('提现类型不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $symbol  = $request->input('symbol');
        $user    = $request->user();
        $user_id = $user->id;

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            throw new LogicException(__('用户信息不存在'));
        }

        if ($symbol == WalletLog::SYMBOL_USDT) {
            $current_am = removeTrailingZeros($userData->current_usdt);
            $min_amount = (int) getGlobalConfig('site_w_min_usdt', '0'); // USDT最小提现金额
            $fee_rate   = getGlobalConfig('site_w_fee_rate_usdt', '0'); // USDT提现手续费(%)
        } elseif ($symbol == WalletLog::SYMBOL_NOX) {
            $current_am = removeTrailingZeros($userData->current_nox);
            $min_amount = (int) getGlobalConfig('site_w_min_nox', '0'); // NOX最小提现金额
            $fee_rate   = getGlobalConfig('site_w_fee_rate_nox', '0'); // NOX提现手续费(%)
        } else {
            return apiError(__('提现类型不正确'));
        }

        return apiSuccess([
            'current_am' => $current_am,
            'min_amount' => $min_amount,
            'fee_rate'   => removeTrailingZeros($fee_rate),
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|decimal:0,6|gt:0',
            'symbol' => 'required|in:USDT,NOX',
        ], [
            'amount.required' => __('提现金额不能为空'),
            'amount.decimal'  => __('提现金额不正确'),
            'amount.gt'       => __('提现金额必须大于0'),
            'symbol.required' => __('提现类型不能为空'),
            'symbol.in'       => __('提现类型不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $amount  = $request->input('amount');
        $symbol  = $request->input('symbol');
        $user    = $request->user();
        $user_id = $user->id;
        $to_addr = $user->address;
        if (empty($to_addr)) {
            return apiError(__('请先绑定钱包地址'));
        }

        // 判断是否是异常用户
        if ($user->status == 0) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        if ($symbol == WalletLog::SYMBOL_USDT) {
            if ($user->allow_w_usdt == 0) {
                return apiError(__('系统繁忙，请稍后再试'));
            }
            $allow      = getGlobalConfig('site_allow_w_usdt'); // 是否开启提现
            $min_num    = getGlobalConfig('site_w_min_usdt', '0'); // 最小提现金额
            $fee_rate   = getGlobalConfig('site_w_fee_rate_usdt', '0'); // 提现手续费率 2位小数
            $asset_code = Asset::CODE_USDT;
        } elseif ($symbol == WalletLog::SYMBOL_NOX) {
            if ($user->allow_w_nox == 0) {
                return apiError(__('系统繁忙，请稍后再试'));
            }
            $allow      = getGlobalConfig('site_allow_w_nox'); // 是否开启提现
            $min_num    = getGlobalConfig('site_w_min_nox', '0'); // 最小提现金额
            $fee_rate   = getGlobalConfig('site_w_fee_rate_nox', '0'); // 提现手续费率 2位小数
            $asset_code = Asset::CODE_NOX;
        } else {
            return apiError(__('提现类型不正确'));
        }

        if (empty($allow)) {
            return apiError(__('未开启 XX 提现功能，请稍后再试', ['symbol' => $symbol]));
        }

        if (bccomp($amount, $min_num, 6) < 0) {
            return apiError(__('提现金额不能小于 XX', ['min' => removeTrailingZeros($min_num)]));
        }

        $fee_rate = bcadd($fee_rate, 0, 2); // 小数点后两位
        if ($fee_rate < 0 || $fee_rate > 100) {
            return apiError(__('提现手续费率配置错误'));
        }

        // 获取代币合约
        try {
            [$asset, $withdraw_contract] = $this->getAsset($asset_code);
        } catch (LogicException $e) {
            return apiError($e->getMessage());
        }
        $contract_address = $asset->contract_address; // 合约地址
        $withdraw_address = $asset->withdraw_address; // 打款地址
        $withdraw_key     = $asset->withdraw_key; // 打款私钥
        $deadline         = Carbon::now()->addMinutes(5); // 签名过期时间

        $lock = Cache::lock(self::CACHE_KEY.$user_id, 15);
        if (! $lock->get()) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            $lock->release();

            return apiError(__('用户信息不存在'));
        }

        if ($symbol == WalletLog::SYMBOL_USDT) {
            $current_amount = $userData->current_usdt;
        } elseif ($symbol == WalletLog::SYMBOL_NOX) {
            $current_amount = $userData->current_nox;
        } else {
            $lock->release();

            return apiError(__('提现类型不正确'));
        }
        if (bccomp($amount, $current_amount, 6) > 0) {
            $lock->release();

            return apiError(__('XX 余额不足', ['symbol' => $symbol]));
        }

        // 处理提现限制
        try {
            $this->checkWithdrawGlobalLimit($amount, $symbol);
            $this->checkWithdrawUserLimit($user_id, $amount, $symbol);
            $this->checkWithdrawWeeklyLimit($user_id, $amount, $symbol);
        } catch (LogicException $e) {
            $lock->release();

            return apiError($e->getMessage());
        } catch (\Exception $e) {
            $lock->release();
            Log::error('withdraw_limit_error', [$e->getMessage(), $e->getTraceAsString()]);
            throw new LogicException(__('服务器异常'));
        }

        // 提现模式
        $withdraw_mode = Withdraw::WITHDRAW_MODE_CONTRACT; // 合约提现

        // 计算手续费(保留6位小数)
        $fee = bcmul($amount, bcdiv($fee_rate, 100, 4), 6);
        // 到手金额(保留6位小数)
        $real_am = bcsub($amount, $fee, 6);

        if (bccomp($real_am, '0', 6) <= 0) {
            return apiError(__('提现金额太小'));
        }

        $signature = null;
        if ($withdraw_mode == Withdraw::WITHDRAW_MODE_CONTRACT) {
            try {
                $sign_data = (new SignService())->signAdd($to_addr, $contract_address, $real_am, $asset_code, $withdraw_key);
            } catch (\Exception $e) {
                $lock->release();

                return apiError(__('服务器异常'));
            }
            $signature = [
                'deadline'          => $deadline,               // 签名过期时间
                'project_signer'    => $withdraw_address,       // 项目方签名地址
                'sign'              => $sign_data['sign'],      // 签名
                'message'           => $sign_data['message'],   // 签名信息
                'withdraw_contract' => $withdraw_contract,      // 提现合约地址
            ];
            $sign_data['deadline']          = $deadline->toDateTimeString();
            $sign_data['token_addr']        = $contract_address;
            $sign_data['decimal']           = $asset->decimals;
            $sign_data['withdraw_contract'] = UserService::hideStr($withdraw_contract);
        }

        $param = [
            'symbol'        => $symbol,
            'amount'        => $amount,
            'real_am'       => $real_am,
            'fee'           => $fee,
            'fee_rate'      => $fee_rate,
            'withdraw_mode' => $withdraw_mode,
        ];
        DB::beginTransaction();
        try {
            $order = WithdrawOrderService::createOrder($user, $param, $signature);
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();

            return apiError($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('withdraw_create_error', [$e->getMessage(), $e->getTraceAsString()]);
            $lock->release();

            return apiError(__('服务器异常'));
        }

        DB::commit();
        $lock->release();

        return apiSuccess([
            'sn' => $order->sn,
            // 'is_signature'  => $withdraw_mode == 3 ? 1 : 0,
            'sign_data' => $sign_data ?? null,
        ]);
    }

    private function checkWithdrawGlobalLimit(string $amount, string $symbol)
    {
        if ($symbol == WalletLog::SYMBOL_USDT) {
            $global_limit = (int) getGlobalConfig('site_w_usdt_global_limit'); // 全站每日提现额度
        } elseif ($symbol == WalletLog::SYMBOL_NOX) {
            $global_limit = (int) getGlobalConfig('site_w_nox_global_limit'); // 全站每日提现额度
        } else {
            throw new LogicException(__('资产类型不正确'));
        }

        if ($global_limit < 0) { // 不能提现
            $global_limit = 0;
        }

        // 获取全站今日累计提现数目
        $today          = Carbon::today();
        $total_withdraw = Withdraw::getDateAllTotalWithdrawAmount($symbol, $today->toDateString());

        // 剩余可提现金额
        $remaining = bcsub($global_limit, $total_withdraw, 6);
        if (bccomp($remaining, '0', 6) <= 0) {
            throw new LogicException(__('提现金额超限，剩余全站可提现金额为 :amount', ['amount' => 0]));
        }

        if (bccomp($remaining, $amount, 6) < 0) {
            throw new LogicException(__('提现金额超限，剩余全站可提现金额为 :amount', ['amount' => $remaining]));
        }
    }

    private function checkWithdrawUserLimit(int $user_id, string $amount, string $symbol)
    {
        if ($symbol == WalletLog::SYMBOL_USDT) {
            $user_limit = (int) getGlobalConfig('site_w_usdt_user_limit'); // 用户每日提现额度
        } elseif ($symbol == WalletLog::SYMBOL_NOX) {
            $user_limit = (int) getGlobalConfig('site_w_nox_user_limit'); // 用户每日提现额度
        } else {
            throw new LogicException(__('资产类型不正确'));
        }

        if ($user_limit < 0) { // 不能提现
            $user_limit = 0;
        }

        // 获取用户今日累计提现数目
        $today          = Carbon::today();
        $total_withdraw = Withdraw::getDateUserTotalWithdrawAmount($user_id, $symbol, $today->toDateString());

        // 剩余可提现金额
        $remaining = bcsub($user_limit, $total_withdraw, 2);
        if (bccomp($remaining, '0', 6) <= 0) {
            throw new LogicException(__('提现金额超限，剩余个人可提现金额为 :amount', ['amount' => 0]));
        }

        if (bccomp($remaining, $amount, 6) < 0) {
            throw new LogicException(__('提现金额超限，剩余个人可提现金额为 :amount', ['amount' => $remaining]));
        }
    }

    private function checkWithdrawWeeklyLimit(int $user_id, string $amount, string $symbol)
    {
        if ($symbol != WalletLog::SYMBOL_USDT && $symbol != WalletLog::SYMBOL_NOX) {
            throw new LogicException(__('资产类型不正确'));
        }
        // 无等级按 V1
        $level_id     = User::query()->where('id', $user_id)->value('level_id') ?: 1;
        $level        = Level::query()->find($level_id);
        $weekly_limit = '0.000000';
        if ($level) {
            $weekly_limit = $symbol == WalletLog::SYMBOL_USDT ? $level->weekly_limit_usdt : $level->weekly_limit_nox;
        }
        if ($weekly_limit === null) {
            $weekly_limit = '0.000000';
        }

        $weekStart      = Carbon::now()->startOfWeek()->toDateString(); // 周一
        $total_withdraw = Withdraw::getWeekUserTotalWithdrawAmount($user_id, $symbol, $weekStart);

        $remaining = bcsub($weekly_limit, $total_withdraw, 2);
        if (bccomp($remaining, '0', 6) <= 0) {
            throw new LogicException(__('本周提现金额超限，剩余可提现金额为 :amount', ['amount' => 0]));
        }
        if (bccomp($remaining, $amount, 6) < 0) {
            throw new LogicException(__('本周提现金额超限，剩余可提现金额为 :amount', ['amount' => $remaining]));
        }
    }

    // 提现成功回调
    public function done(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sn'   => 'required|numeric|digits:19',
            'hash' => 'required|size:66',
        ], [
            'sn.required'   => __('单号不能为空'),
            'sn.numeric'    => __('单号格式不正确'),
            'sn.digits'     => __('单号格式不正确'),
            'hash.required' => __('交易号不能为空'),
            'hash.size'     => __('交易号格式不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $sn      = $request->input('sn');
        $hash    = $request->input('hash');
        $user    = $request->user();
        $user_id = $user->id;

        [$chain, $withdraw_contract] = $this->getChain();

        $lock = Cache::lock(self::CACHE_KEY.$user_id, 15);
        if (! $lock->get()) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        /** @var Withdraw $order */
        $order = Withdraw::getBySn($sn);
        if (empty($order)) {
            $lock->release();

            return apiError(__('订单不存在'));
        }
        // 检查用户是否是订单用户
        if ($order->user_id != $user_id) {
            $lock->release();

            return apiError(__('用户异常'));
        }

        // 提现模式必须是合约提现
        if ($order->withdraw_mode != Withdraw::WITHDRAW_MODE_CONTRACT) {
            $lock->release();

            return apiError(__('提现模式异常'));
        }

        if ($order->status == Withdraw::STATUS_DONE) {
            $lock->release();

            return apiSuccess(__('订单已完成'));
        }

        if ($order->status != Withdraw::STATUS_PENDING) {
            $lock->release();

            return apiError(__('订单状态异常'));
        }

        // 检查交易是否成功
        $eth = new EthService([
            'rpc' => $chain->rpc_url,
        ]);
        $tx = $eth->getTransactionInfo($hash);
        if (empty($tx) || $tx->status != '0x1') {
            throw new LogicException(__('验证无效'));
        }

        DB::beginTransaction();
        try {
            WithdrawOrderService::doneCallback($order, $hash);
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();

            return apiError($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            $lock->release();
            Log::error('withdraw_done_error', [$e->getMessage(), $e->getTraceAsString()]);

            return apiError(__('服务器异常'));
        }

        DB::commit();
        $lock->release();

        return apiSuccess(__('验证成功'));
    }

    /**
     * @return array{Asset,string}
     */
    private function getAsset(string $asset_code): array
    {
        [$chain, $withdraw_contract] = $this->getChain();

        $asset = Asset::getPayInfo($asset_code, $chain->chain_id);
        if (! $asset) {
            throw new LogicException(__('资产(XX)未配置', ['asset_code' => $asset_code]));
        }

        if (! $asset->contract_address) {
            throw new LogicException(__('出金合约未配置'));
        }

        if (! $asset->withdraw_address) {
            throw new LogicException(__('出金信息未配置'));
        }

        return [$asset, $withdraw_contract];
    }

    /**
     * @return array{Chain,string}
     */
    private function getChain(): array
    {
        $chain_id = config('site.asset.chain_id');
        if (! $chain_id) {
            throw new LogicException(__('区块链id未配置'));
        }

        $chain = Chain::getInfoByChainId($chain_id);
        if (! $chain) {
            throw new LogicException(__('区块链信息未配置'));
        }

        // 获取提现合约
        $withdraw_contract = config('site.withdraw_contract');
        if (! $withdraw_contract) {
            throw new LogicException(__('提现合约未配置'));
        }

        return [$chain, $withdraw_contract];
    }
}
