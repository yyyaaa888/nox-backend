<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Jobs\UserUpgradeJob;
use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\Asset;
use App\Models\Chain;
use App\Models\PayOrder;
use App\Models\User;
use App\Models\UserData;
use App\Models\UserRelationClosure;
use App\Services\AlphaOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PowerController extends Controller
{
    const CACHE_KEY = 'user_power_';

    // 资产包首页
    public function home(Request $request)
    {
        $user    = $request->user();
        $user_id = $user->id;

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            throw new LogicException(__('用户信息不存在'));
        }

        $multiple = getGlobalConfig('alpha_quota_multiple', '2.5');

        // 基础算力包
        $basic_product = AlphaProduct::query()->where('tier', AlphaProduct::TIER_BASIC)->first();
        if ($basic_product) {
            $basic_product = [
                'id'         => $basic_product->id,
                'min_amount' => removeTrailingZeros($basic_product->min_amount),
                'max_amount' => removeTrailingZeros($basic_product->max_amount),
                'min_rate'   => removeTrailingZeros($basic_product->min_rate),
                'max_rate'   => removeTrailingZeros($basic_product->max_rate),
                'base_rate'  => removeTrailingZeros($basic_product->max_rate),
                'max_reward' => removeTrailingZeros($multiple),
            ];
        }
        // 高额算力包
        $high_product = AlphaProduct::query()->where('tier', AlphaProduct::TIER_HIGH)->first();
        if ($high_product) {
            $high_product = [
                'id'         => $high_product->id,
                'min_amount' => removeTrailingZeros($high_product->min_amount),
                'max_amount' => removeTrailingZeros($high_product->max_amount),
                'min_rate'   => removeTrailingZeros($high_product->min_rate),
                'max_rate'   => removeTrailingZeros($high_product->max_rate),
                'base_rate'  => removeTrailingZeros($high_product->max_rate),
                'max_reward' => removeTrailingZeros($multiple),
            ];
        }

        // 全网算力
        $total_power = '0';
        // 个人算力
        $user_power = '0';
        // 大区算力
        $big_power = '0';
        // 小区算力
        $small_power = '0';

        $first_user = User::query()->orderBy('id', 'asc')->first();
        if ($first_user) {
            $perfs = UserRelationClosure::getTeamPerformances($first_user->id);
            $total_power = $perfs['all'];
            $user_power  = $perfs['user_perf'];
            $big_power   = $perfs['big_total'];
            $small_power = $perfs['small_total'];
        }

        // 读取修正数据
        $home_power       = (int)getGlobalConfig('site_home_power', 0);
        $home_big_power   = (int)getGlobalConfig('site_home_power_big', 0);
        $home_small_power = $home_power - $home_big_power;
        if ($home_small_power < 0) {
            $home_big_power   = $home_power;
            $home_small_power = 0;
        }

        $total_power = bcadd($total_power, $home_power, 0);
        $big_power   = bcadd($big_power, $home_big_power, 0);
        $small_power = bcadd($small_power, $home_small_power, 0);

        return apiSuccess([
            'total_power' => removeTrailingZeros($total_power),
            'user_power'  => removeTrailingZeros($user_power),
            'big_power'   => removeTrailingZeros($big_power),
            'small_power' => removeTrailingZeros($small_power),
            'products'    => [
                'basic' => $basic_product,
                'high'  => $high_product,
            ],
        ]);
    }

    // 申购：创建订单 + 支付单，返回支付信息
    public function create(Request $request)
    {
        $allow = getGlobalConfig('site_allow_power_buy'); // 是否开启算力购买
        if (empty($allow)) {
            return apiError(__('未开启算力购买功能，请稍后再试'));
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'amount'     => 'required|decimal:0,6|gt:0',
        ], [
            'product_id.required' => __('产品不能为空'),
            'amount.required'     => __('金额不能为空'),
            'amount.decimal'      => __('金额不正确'),
            'amount.gt'           => __('金额必须大于0'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user    = $request->user();
        $user_id = $user->id;

        // 判断是否是异常用户
        if ($user->status == 0) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        $product = AlphaProduct::query()->where('id', $request->input('product_id'))->where('status', AlphaProduct::STATUS_ON)->first();
        if (! $product) {
            return apiError(__('产品不存在或已停用'));
        }
        $amount = $request->input('amount');
        if (bccomp($amount, $product->min_amount, 6) < 0 || bccomp($amount, $product->max_amount, 6) > 0) {
            return apiError(__('金额不在申购区间内'));
        }

        $lock = Cache::lock(self::CACHE_KEY.$user_id, 5);
        if (! $lock->get()) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        try {
            [$asset, $chain, $pay_contract] = $this->getAsset(Asset::CODE_USDT);
            $token_price = $this->getTokenPrice(Asset::CODE_NOX);
        } catch (LogicException $e) {
            $lock->release();
            throw $e;
        }

        $last_time = Carbon::now()->addMinutes(5);
        $pay       = [
            'pay_contract' => $pay_contract,
            'pay_addr'     => $user->address,
            'token_addr'   => $asset->contract_address,
            'token_price'  => $token_price,
            'dest_addr'    => $asset->wallet_address,
            'last_time'    => $last_time,
            'decimals'     => $asset->decimals,
        ];

        DB::beginTransaction();
        try {
            $order = AlphaOrderService::createOrder($user, $product, $amount, $pay);
            // 非生产 mock 支付回调（与充值一致）
            if (! config('app.check.payOrder')) {
                $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
                AlphaOrderService::payCallback($order, $payOrder, null, $order->amount);
            }
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();

            return apiError($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            $lock->release();
            Log::error('power_create_error', [$e->getMessage(), $e->getTraceAsString()]);

            return apiError(__('服务器异常'));
        }
        DB::commit();
        $lock->release();

        return apiSuccess([
            'sn'         => $order->sn,
            'amount'     => bcmul($order->amount, pow(10, $pay['decimals']), 0),
            'token_addr' => $pay['token_addr'],
            'last_time'  => $last_time->timestamp,
        ]);
    }

    // 我的订单
    public function orders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page'  => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'type'  => 'nullable|in:0,1,2',
        ], [
            'page.integer'  => __('参数 page 不正确'),
            'page.min'      => __('参数 page 不正确'),
            'limit.integer' => __('参数 limit 不正确'),
            'limit.min'     => __('参数 limit 不正确'),
            'limit.max'     => __('参数 limit 不正确'),
            'type.in'       => __('参数 type 不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user    = $request->user();
        $user_id = $user->id;
        $limit   = $request->input('limit', 15);
        $type    = (int) $request->input('type', 0);

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            return apiError(__('用户信息不存在'));
        }

        $now   = Carbon::now();
        $query = AlphaOrder::query();
        switch ($type) {
            case 1: // 可退出
                $query->where('status', AlphaOrder::STATUS_PAID)->where('trial_end', '>=', $now);
                break;
            case 2: // 已完成
                $query->whereIn('status', [AlphaOrder::STATUS_DONE, AlphaOrder::STATUS_EXITED]);
                break;
            default: // 全部
                $query->whereIn('status', [AlphaOrder::STATUS_PAID, AlphaOrder::STATUS_DONE, AlphaOrder::STATUS_EXITED]);
                break;
        }

        $paginate = $query->with('product')
            ->where('user_id', $user_id)
            ->orderByDesc('id')
            ->paginate($limit);

        $collection = $paginate->map(function (AlphaOrder $item) use ($now) {
            $product = $item->product;
            $tier    = $product->tier;
            $type    = 0;
            if ($item->status == AlphaOrder::STATUS_PAID && $item->trial_end->gte($now)) {
                $type = 1;
            } elseif ($item->status == AlphaOrder::STATUS_DONE || $item->status == AlphaOrder::STATUS_EXITED) {
                $type = 2;
            }

            return [
                'id'          => $item->id,
                'tier'        => $tier,
                'amount'      => removeTrailingZeros($item->amount),      // 投入金额
                'power_am'    => removeTrailingZeros($item->power_am),    // 算力值
                'quota_power' => removeTrailingZeros($item->quota_reward), // 预计收益（响应键保持兼容，见字段改名 rem_power/quota_power → *_reward）
                'rel_reward'  => removeTrailingZeros($item->rel_reward),  // 已发放收益
                'last_reward' => removeTrailingZeros($item->last_reward), // 昨日收益
                'status'      => $item->status,                           // 订单状态
                'type'        => $type,
                'trial_end'   => $item->trial_end->format('Y-m-d H:i:s'),
                'created_at'  => $item->created_at->format('Y-m-d H:i:s'),
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }

    // 首月退出
    public function exit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ], ['id.required' => __('订单不能为空')]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user    = $request->user();
        $user_id = $user->id;

        // 判断是否是异常用户
        if ($user->status == 0) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        $order = AlphaOrder::query()->where('id', $request->input('id'))->where('user_id', $user_id)->first();
        if (! $order) {
            return apiError(__('订单不存在'));
        }
        try {
            AlphaOrderService::exit($order);
        } catch (LogicException $e) {
            return apiError($e->getMessage());
        }

        return apiSuccess(__('退出成功'));
    }

    // 支付确认（用户提交 hash 快速验证，与充值 done 一致）
    public function done(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sn'   => 'required|numeric|digits:19',
            'hash' => 'required|size:66',
        ], [
            'sn.required'   => __('单号不能为空'),
            'hash.required' => __('交易号不能为空'),
            'hash.size'     => __('交易号格式不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user = $request->user();
        $sn   = $request->input('sn');
        $hash = $request->input('hash');

        $payOrder = PayOrder::query()->where('sn', $sn)->where('type', PayOrder::TYPE_ALPHA_BUY)->first();
        if (! $payOrder || $payOrder->user_id != $user->id) {
            return apiError(__('支付单不存在'));
        }
        $order = AlphaOrder::getBySn($sn);
        if (! $order) {
            return apiError(__('订单不存在'));
        }
        if ($payOrder->status == PayOrder::STATUS_PAID) {
            return apiSuccess(__('订单已支付'));
        }
        if ($payOrder->status != PayOrder::STATUS_WAIT || $order->status != AlphaOrder::STATUS_WAIT) {
            return apiError(__('支付状态异常'));
        }

        try {
            $payInfo = $this->getPayInfoFromContract($payOrder);
            if (! $payInfo || empty($payInfo['isPaid'])) {
                return apiError(__('链上未找到支付信息或订单未支付'));
            }
            $this->validatePayment($payOrder, $payInfo);

            DB::beginTransaction();
            AlphaOrderService::payCallback($order, $payOrder, $hash, $payInfo['payTotal']);
            DB::commit();
        } catch (LogicException $e) {
            DB::rollBack();

            return apiError($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('power_done_error', [$e->getMessage()]);

            return apiError(__('服务器异常'));
        }
        $user_id = $user->id;
        UserUpgradeJob::dispatch($user_id);

        return apiSuccess(__('验证成功'));
    }

    // 取消支付
    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sn' => 'required|numeric|digits:19',
        ], ['sn.required' => __('单号不能为空')]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user     = $request->user();
        $sn       = $request->input('sn');
        $payOrder = PayOrder::query()->where('sn', $sn)->where('type', PayOrder::TYPE_ALPHA_BUY)->first();
        if (! $payOrder || $payOrder->user_id != $user->id) {
            return apiError(__('支付单不存在'));
        }
        $order = AlphaOrder::getBySn($sn);
        if (! $order) {
            return apiError(__('订单不存在'));
        }
        if ($payOrder->status == PayOrder::STATUS_CANCEL) {
            return apiSuccess(__('订单已取消'));
        }
        try {
            DB::beginTransaction();
            AlphaOrderService::payCancel($order, $payOrder, '用户取消支付');
            DB::commit();
        } catch (LogicException $e) {
            DB::rollBack();

            return apiError($e->getMessage());
        }

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

    private function getTokenPrice(string $asset_code): string
    {
        $chain_id = config('site.asset.chain_id');
        if (! $chain_id) {
            throw new LogicException(__('区块链id未配置'));
        }
        $asset = Asset::getPayInfo($asset_code, $chain_id);
        if (! $asset) {
            throw new LogicException(__('资产(XX)未配置', ['asset_code' => $asset_code]));
        }
        if (bccomp($asset->exchange_rate, '0', 8) <= 0) {
            throw new LogicException(__('资产(XX)汇率未配置', ['asset_code' => $asset_code]));
        }

        return $asset->exchange_rate;
    }

    private function getPayInfoFromContract(PayOrder $payOrder): ?array
    {
        if (! config('app.check.pay')) {
            return [
                'orderId'         => $payOrder->id,
                'contractAddress' => $payOrder->token_addr,
                'fromAddress'     => $payOrder->pay_addr,
                'toAddress'       => $payOrder->dest_addr,
                'payTotal'        => $payOrder->amount,
                'payTime'         => $payOrder->created_at->timestamp,
                'isPaid'          => true,
            ];
        }
        $chain_id = config('site.asset.chain_id');
        $chain    = Chain::getInfoByChainId($chain_id);
        $service  = new \App\Services\Web3\PaymentContractService([
            'rpc'     => $chain->rpc_url,
            'address' => $payOrder->pay_contract,
        ]);
        $result = $service->getPayInfo($payOrder->sn);
        if (! $result) {
            return null;
        }
        if (! empty($result['payTotal'])) {
            $result['payTotal'] = bcdiv($result['payTotal'], pow(10, $payOrder->decimals), $payOrder->decimals);
        }

        return $result;
    }

    private function validatePayment(PayOrder $payOrder, array $payInfo)
    {
        $payTotal   = $payInfo['payTotal'];
        $orderTotal = $payOrder->getPayAmount();
        if (bccomp($payTotal, $orderTotal, 6) < 0) {
            throw new LogicException(__('支付金额不匹配'));
        }
        if (strtolower($payInfo['contractAddress']) !== strtolower($payOrder->token_addr)) {
            throw new LogicException(__('合约地址验证失败'));
        }
        if (strtolower($payInfo['fromAddress']) !== strtolower($payOrder->pay_addr)) {
            throw new LogicException(__('付款地址验证失败'));
        }
    }
}
