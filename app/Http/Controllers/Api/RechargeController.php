<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Chain;
use App\Models\PayOrder;
use App\Models\UserData;
use App\Models\UserRecharge;
use App\Models\WalletLog;
use App\Services\UserRechargeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RechargeController extends Controller
{
    const CACHE_KEY = 'user_recharge_';

    // 充值
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|decimal:0,6|gt:0',
            'symbol' => 'required|in:'.implode(',', UserRecharge::allowSymbols()),
        ], [
            'amount.required' => __('充值金额不能为空'),
            'amount.decimal'  => __('充值金额不正确'),
            'amount.gt'       => __('充值金额必须大于0'),
            'symbol.required' => __('充值类型不能为空'),
            'symbol.in'       => __('充值类型不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $amount  = $request->input('amount');
        $symbol  = $request->input('symbol');
        $user    = $request->user();
        $user_id = $user->id;

        // 判断是否是异常用户
        if ($user->status == 0) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        if ($symbol == WalletLog::SYMBOL_USDT) {
            $asset_code = Asset::CODE_USDT;
            $allow      = getGlobalConfig('site_allow_recharge_usdt'); // 是否开启充值
            $min_num    = getGlobalConfig('site_recharge_min_usdt', '0'); // 最小充值金额
        } elseif ($symbol == WalletLog::SYMBOL_NOX) {
            $asset_code = Asset::CODE_NOX;
            $allow      = getGlobalConfig('site_allow_recharge_nox'); // 是否开启充值
            $min_num    = getGlobalConfig('site_recharge_min_nox', '0'); // 最小充值金额
        } else {
            return apiError(__('充值类型不正确'));
        }

        if (empty($allow)) {
            return apiError(__('未开启 XX 充值功能，请稍后再试', ['symbol' => $symbol]));
        }

        if (bccomp($amount, $min_num, 6) < 0) {
            return apiError(__('充值金额不能小于 XX', ['num' => removeTrailingZeros($min_num)]));
        }

        $lock = Cache::lock(self::CACHE_KEY.$user_id, 5);
        if (! $lock->get()) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            $lock->release();
            throw new LogicException(__('用户信息不存在'));
        }

        try {
            [$asset, $chain, $pay_contract] = $this->getAsset($asset_code);
        } catch (LogicException $e) {
            $lock->release();
            throw $e;
        }

        if (empty($asset->contract_address)) {
            $lock->release();
            throw new LogicException(__('合约地址未配置'));
        }

        if (empty($asset->wallet_address)) {
            $lock->release();
            throw new LogicException(__('收款钱包地址未配置'));
        }

        $now       = Carbon::now();
        $last_time = $now->addMinutes(5); // 支付5分钟过期
        $param     = [
            'amount' => $amount,
            'symbol' => $symbol,
            'asset'  => $asset_code,
        ];
        $json = [
            // 'pay_contract'  => $pay_contract,
            // 'pay_addr'      => $user->address,
            'token_addr' => $asset->contract_address,
            // 'decimals'      => $asset->decimals,
            // 'dest_addr'     => $asset->wallet_address,
            'last_time' => $last_time->timestamp,
        ];
        $pay = [
            'pay_contract' => $pay_contract,
            'pay_addr'     => $user->address,
            'token_addr'   => $asset->contract_address,
            'dest_addr'    => $asset->wallet_address,
            'last_time'    => $last_time,
            'decimals'     => $asset->decimals,
        ];
        DB::beginTransaction();
        try {
            /** @var UserRecharge $order */
            /** @var PayOrder $payOrder */
            [$order, $payOrder] = UserRechargeService::createOrder($user, $param, $pay);
            // mock 支付回调
            if (! config('app.check.payOrder')) {
                $payInfo = [
                    'payTotal' => $payOrder->getPayAmount(),
                ];
                UserRechargeService::payCallback($order, $payOrder, null, $payInfo['payTotal']);
            }
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();

            return apiError($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            $lock->release();
            Log::error('recharge_create_error', [$e->getMessage(), $e->getTraceAsString()]);

            return apiError(__('服务器异常'));
        }

        DB::commit();
        $lock->release();
        $json['sn']     = $payOrder->getSn();
        $json['amount'] = $payOrder->getPayAmountForCoin();

        return apiSuccess($json);
    }

    // 支付回调
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

        $lock = Cache::lock(self::CACHE_KEY.$user_id, 15);
        if (! $lock->get()) {
            Log::error("recharge get lock fail for {$user_id}");

            return apiError(__('系统繁忙，请稍后再试'));
        }

        if (PayOrder::checkHashExist($hash)) {
            Log::channel('paylog')->error("recharge {$sn} fail: 交易号已存在");
            $lock->release();

            return apiError(__('交易号已存在'));
        }

        $payOrder = PayOrder::getBySn($sn);
        if (empty($payOrder)) {
            $lock->release();

            return apiError(__('支付单不存在'));
        }
        // 检查用户是否是订单用户
        if ($payOrder->user_id != $user_id) {
            $lock->release();

            return apiError(__('用户异常'));
        }

        $order = UserRecharge::getBySn($sn);
        if (empty($order)) {
            $lock->release();

            return apiError(__('订单不存在'));
        }

        if ($order->symbol == WalletLog::SYMBOL_USDT) {
            $asset_code = Asset::CODE_USDT;
        } elseif ($order->symbol == WalletLog::SYMBOL_NOX) {
            $asset_code = Asset::CODE_NOX;
        } else {
            $lock->release();

            return apiError(__('充值类型不正确'));
        }

        if ($payOrder->status == PayOrder::STATUS_PAID) {
            Log::channel('paylog')->info("recharge {$sn} fail: 订单已支付");
            $lock->release();

            return apiSuccess(__('订单已支付'));
        }

        if ($payOrder->status != PayOrder::STATUS_WAIT) {
            Log::channel('paylog')->error("recharge {$sn} fail: 支付状态异常");
            $lock->release();

            return apiError(__('支付状态异常'));
        }

        if ($order->status != UserRecharge::STATUS_NOPAY) {
            $lock->release();

            return apiError(__('订单状态异常'));
        }

        // add paylog
        Log::channel('paylog')->info('recharge', [
            'uid'  => $user_id,
            'sn'   => $sn,
            'hash' => $hash,
        ]);

        try {
            [$asset, $chain, $pay_contract] = $this->getAsset($asset_code);
        } catch (LogicException $e) {
            $lock->release();
            throw $e;
        }
        $pay_config = [
            'rpc'      => $chain->rpc_url,
            'address'  => $pay_contract,
            'decimals' => $asset->decimals,
        ];

        // 从链上获取支付信息
        $payInfo = $this->getPayInfoFromContract($payOrder, $pay_config);
        if (! $payInfo || ! $payInfo['isPaid']) {
            $lock->release();
            Log::channel('paylog')->error('recharge not paid', [
                'uid'  => $user_id,
                'sn'   => $sn,
                'hash' => $hash,
            ]);

            return apiError(__('链上未找到支付信息或订单未支付'));
        }

        try {
            if (PayOrder::checkHashExist($hash)) {
                throw new LogicException(__('交易号已存在'));
            }

            // 验证支付信息
            $this->validatePayment($payOrder, $payInfo);

            DB::beginTransaction();
            UserRechargeService::payCallback($order, $payOrder, $hash, $payInfo['payTotal']);
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();
            Log::channel('paylog')->error('recharge fail', [
                'uid'  => $user_id,
                'sn'   => $sn,
                'hash' => $hash,
                'msg'  => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            Log::error('recharge_callback_fail', [$e->getMessage()]);

            return apiError($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            $lock->release();
            Log::channel('paylog')->error('recharge error', [
                'uid'  => $user_id,
                'sn'   => $sn,
                'hash' => $hash,
                'msg'  => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            Log::error('recharge_callback_error', [$e->getMessage(), $e->getTraceAsString()]);

            return apiError(__('服务器异常'));
        }

        DB::commit();
        Log::channel('paylog')->info('recharge success', [
            'uid'  => $user_id,
            'sn'   => $sn,
            'hash' => $hash,
        ]);

        $lock->release();

        return apiSuccess(__('验证成功'));
    }

    // 取消支付
    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sn' => 'required|numeric|digits:19',
        ], [
            'sn.required' => __('单号不能为空'),
            'sn.numeric'  => __('单号格式不正确'),
            'sn.digits'   => __('单号格式不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $sn      = $request->input('sn');
        $user    = $request->user();
        $user_id = $user->id;

        $lock = Cache::lock(self::CACHE_KEY.$user_id, 5);
        if (! $lock->get()) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        $payOrder = PayOrder::getBySn($sn);
        if (empty($payOrder)) {
            $lock->release();

            return apiError(__('支付单不存在'));
        }
        // 检查用户是否是订单用户
        if ($payOrder->user_id != $user_id) {
            $lock->release();

            return apiError(__('用户异常'));
        }

        $order = UserRecharge::getBySn($sn);
        if (empty($order)) {
            $lock->release();

            return apiError(__('订单不存在'));
        }

        if ($payOrder->status == PayOrder::STATUS_CANCEL) {
            $lock->release();

            return apiSuccess(__('订单已取消'));
        }

        if ($payOrder->status != PayOrder::STATUS_WAIT) {
            $lock->release();

            return apiError(__('支付状态异常'));
        }

        if ($order->status != UserRecharge::STATUS_NOPAY) {
            $lock->release();

            return apiError(__('订单状态异常'));
        }

        DB::beginTransaction();
        try {
            UserRechargeService::payCancel($order, $payOrder, '用户取消支付');
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();
            Log::error('recharge_cancel_fail', [$e->getMessage()]);

            return apiError($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            $lock->release();
            Log::error('recharge_cancel_error', [$e->getMessage(), $e->getTraceAsString()]);

            return apiError(__('服务器异常'));
        }

        DB::commit();
        $lock->release();

        return apiSuccess(__('取消成功'));
    }

    private function getAsset(string $asset_code)
    {
        $chain_id = config('site.asset.chain_id');
        if (! $chain_id) {
            throw new LogicException(__('区块链id未配置'));
        }
        $chain = Chain::getInfoByChainId($chain_id);
        if (! $chain) {
            throw new LogicException(__('区块链信息未配置'));
        }
        $asset = Asset::getPayInfo($asset_code, $chain_id);
        if (! $asset) {
            throw new LogicException(__('资产(XX)未配置', ['asset_code' => $asset_code]));
        }
        $pay_contract = config('site.pay_contract');
        if (! $pay_contract) {
            throw new LogicException(__('支付合约地址未配置'));
        }

        return [$asset, $chain, $pay_contract];
    }

    /**
     * 从合约获取支付信息
     */
    private function getPayInfoFromContract(PayOrder $payOrder, array $pay_config): ?array
    {
        $sn = $payOrder->sn;
        try {
            // mock 数据
            if (! config('app.check.pay')) {
                $result = [
                    'orderId'         => $payOrder->id,
                    'contractAddress' => $payOrder->token_addr,
                    'fromAddress'     => $payOrder->pay_addr,
                    'toAddress'       => $payOrder->dest_addr,
                    'payTotal'        => $payOrder->amount,
                    'payTime'         => $payOrder->created_at->timestamp,
                    'isPaid'          => true,
                ];

                return $result;
            }

            $service = new \App\Services\Web3\PaymentContractService($pay_config);
            $result  = $service->getPayInfo($sn);

            if (! $result) {
                Log::warning('合约返回空数据', ['sn' => $sn]);

                return null;
            }

            // 处理数据格式
            if (! empty($result['payTotal'])) {
                $result['payTotal'] = bcdiv($result['payTotal'], pow(10, $payOrder->decimals), $payOrder->decimals);
            }
            if (! empty($result['payTime'])) {
                $result['payTime'] = date('Y-m-d H:i:s', $result['payTime']);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('获取合约支付信息失败', [
                'sn'    => $sn,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * 验证支付信息
     */
    private function validatePayment(PayOrder $payOrder, array $payInfo)
    {
        // 验证金额
        $payTotal   = $payInfo['payTotal'];
        $orderTotal = $payOrder->getPayAmount();
        if (bccomp($payTotal, $orderTotal, 6) < 0) {
            throw new LogicException(__('支付金额不匹配'));
        }

        // 验证对应的合约地址
        $expectedContract = $payOrder->token_addr;
        if (strtolower($payInfo['contractAddress']) !== strtolower($expectedContract)) {
            throw new LogicException(__('合约地址验证失败'));
        }

        // 验证付款地址
        $expectedPayAddress = $payOrder->pay_addr;
        if (strtolower($payInfo['fromAddress']) !== strtolower($expectedPayAddress)) {
            throw new LogicException(__('付款地址验证失败'));
        }
    }
}
