<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Jobs\UserUpgradeJob;
use App\Models\Asset;
use App\Models\Chain;
use App\Models\NodeOrder;
use App\Models\NodeProduct;
use App\Models\PayOrder;
use App\Models\UserData;
use App\Services\NodeBuyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NodeController extends Controller
{
    const CACHE_KEY = 'user_node_';

    // 节点首页：产品 + 我的节点概览
    public function home(Request $request)
    {
        $user    = $request->user();
        $user_id = $user->id;

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            throw new LogicException(__('用户信息不存在'));
        }

        // 计算节点购买额度
        $buy_limit = bcdiv($userData->curr_stake, 5000, 0);

        $product = NodeProduct::getDefaultNode();
        if (! $product) {
            return apiError(__('节点不存在'));
        }

        return apiSuccess([
            'node_price' => removeTrailingZeros($product->price),
            'buy_limit'  => $buy_limit,
            'my_count'   => $userData->node_num,
        ]);
    }

    // 申购：创建节点订单 + 支付单
    public function create(Request $request)
    {
        $allow = getGlobalConfig('site_allow_node_buy'); // 是否开启节点购买
        if (empty($allow)) {
            return apiError(__('未开启节点购买功能，请稍后再试'));
        }

        $validator = Validator::make($request->all(), [
            'num' => 'required|integer|min:1',
        ], [
            'num.required' => __('节点数量不能为空'),
            'num.min'      => __('节点数量不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $num = $request->input('num');

        $user    = $request->user();
        $user_id = $user->id;

        // 判断是否是异常用户
        if ($user->status == 0) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        $product = NodeProduct::getDefaultNode();
        if (! $product) {
            return apiError(__('节点不存在或已停用'));
        }

        $lock = Cache::lock(self::CACHE_KEY.$user_id, 5);
        if (! $lock->get()) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        // 检查是否存在未支付的订单
        $exists = NodeOrder::query()->where('user_id', $user_id)->where('status', NodeOrder::STATUS_WAIT)->exists();
        if ($exists) {
            $lock->release();

            return apiError(__('存在待支付的订单，请稍后重试'));
        }

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            $lock->release();

            return apiError(__('用户信息不存在'));
        }

        // 计算节点购买额度
        $buy_limit = bcdiv($userData->curr_stake, 5000, 0);
        if ($buy_limit == 0) {
            $lock->release();

            return apiError(__('用户当前节点购买额度为0'));
        }
        if ($userData->node_num >= $buy_limit) {
            $lock->release();

            return apiError(__('用户节点购买额度已用完'));
        }
        if (bcadd($userData->node_num, $num, 0) > $buy_limit) {
            $lock->release();

            return apiError(__('用户节点购买额度不足'));
        }

        try {
            [$asset, $chain, $pay_contract] = $this->getAsset(Asset::CODE_USDT);
        } catch (LogicException $e) {
            $lock->release();

            return apiError($e->getMessage());
        }

        $last_time = Carbon::now()->addMinutes(5);
        $pay       = [
            'pay_contract' => $pay_contract,
            'pay_addr'     => $user->address,
            'token_addr'   => $asset->contract_address,
            'dest_addr'    => $asset->wallet_address,
            'last_time'    => $last_time,
            'decimals'     => $asset->decimals,
        ];

        DB::beginTransaction();
        try {
            $order = NodeBuyService::createOrder($user, $product, $num, $pay);
            if (! config('app.check.payOrder')) {
                $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
                NodeBuyService::payCallback($order, $payOrder, null, $order->amount);
            }
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();

            return apiError($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            $lock->release();
            Log::error('node_create_error', [$e->getMessage(), $e->getTraceAsString()]);

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

    // 我的节点订单
    public function orders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page'  => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ], [
            'page.integer'  => __('参数 page 不正确'),
            'page.min'      => __('参数 page 不正确'),
            'limit.integer' => __('参数 limit 不正确'),
            'limit.min'     => __('参数 limit 不正确'),
            'limit.max'     => __('参数 limit 不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user    = $request->user();
        $user_id = $user->id;
        $limit   = $request->input('limit', 15);

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            return apiError(__('用户信息不存在'));
        }

        $paginate = NodeOrder::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($limit);

        $collection = $paginate->map(function (NodeOrder $item) {
            return [
                'id'         => $item->id,
                'sn'         => $item->sn,
                'num'        => $item->num,
                'price'      => removeTrailingZeros($item->price),
                'amount'     => removeTrailingZeros($item->amount),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
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

        $payOrder = PayOrder::query()->where('sn', $sn)->where('type', PayOrder::TYPE_NODE_BUY)->first();
        if (! $payOrder || $payOrder->user_id != $user->id) {
            return apiError(__('支付单不存在'));
        }
        $order = NodeOrder::getBySn($sn);
        if (! $order) {
            return apiError(__('订单不存在'));
        }
        if ($payOrder->status == PayOrder::STATUS_PAID) {
            // 已被 cron 等先行确认：补触发等级检查，确保持节点保底 V3 生效
            try {
                UserUpgradeJob::dispatch($user->id);
            } catch (\Throwable $e) {
                Log::error('node_done_upgrade_error', [$order->sn, $e->getMessage()]);
            }

            return apiSuccess(__('订单已支付'));
        }
        if ($payOrder->status != PayOrder::STATUS_WAIT || $order->status != NodeOrder::STATUS_WAIT) {
            return apiError(__('支付状态异常'));
        }

        try {
            $payInfo = $this->getPayInfoFromContract($payOrder);
            if (! $payInfo || empty($payInfo['isPaid'])) {
                return apiError(__('链上未找到支付信息或订单未支付'));
            }
            $this->validatePayment($payOrder, $payInfo);

            DB::beginTransaction();
            NodeBuyService::payCallback($order, $payOrder, $hash, $payInfo['payTotal']);
            DB::commit();
        } catch (LogicException $e) {
            DB::rollBack();

            return apiError($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('node_done_error', [$e->getMessage()]);

            return apiError(__('服务器异常'));
        }
        // 支付成功后触发等级检查：持有节点保底 V3（持节点等级下限在 UserUpgradeService 内判定）
        $user_id = $user->id;
        try {
            UserUpgradeJob::dispatch($user_id);
        } catch (\Throwable $e) {
            Log::error('node_done_upgrade_error', [$order->sn, $e->getMessage()]);
        }

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
        $payOrder = PayOrder::query()->where('sn', $sn)->where('type', PayOrder::TYPE_NODE_BUY)->first();
        if (! $payOrder || $payOrder->user_id != $user->id) {
            return apiError(__('支付单不存在'));
        }
        $order = NodeOrder::getBySn($sn);
        if (! $order) {
            return apiError(__('订单不存在'));
        }
        if ($payOrder->status == PayOrder::STATUS_CANCEL) {
            return apiSuccess(__('订单已取消'));
        }
        try {
            DB::beginTransaction();
            NodeBuyService::payCancel($order, $payOrder, '用户取消支付');
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
