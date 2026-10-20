<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserData;
use App\Models\WalletLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class MyAssetLogController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page'       => 'nullable|integer|min:1',
            'limit'      => 'nullable|integer|min:1|max:100',
            'symbol'     => 'required|in:USDT,NOX',
            'type'       => 'nullable|integer',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d',
        ], [
            'page.integer'           => __('参数 page 不正确'),
            'page.min'               => __('参数 page 不正确'),
            'limit.integer'          => __('参数 limit 不正确'),
            'limit.min'              => __('参数 limit 不正确'),
            'limit.max'              => __('参数 limit 不正确'),
            'symbol.required'        => __('账户类型不能为空'),
            'symbol.in'              => __('账户类型不正确'),
            'type.integer'           => __('类型不正确'),
            'start_date.date_format' => __('开始日期格式不正确'),
            'end_date.date_format'   => __('结束日期格式不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user    = $request->user();
        $user_id = $user->id;

        $limit      = $request->input('limit', 15);
        $symbol     = $request->input('symbol');
        $type       = $request->input('type');
        $start_date = $request->input('start_date');
        $end_date   = $request->input('end_date');

        if ($start_date && $end_date) {
            try {
                $start_date = Carbon::parse($start_date)->startOfDay();
                $end_date   = Carbon::parse($end_date)->endOfDay();
            } catch (\Exception $e) {
                return apiError(__('日期格式不正确'));
            }
            if ($start_date->gt($end_date)) {
                return apiError(__('结束日期不能早于开始日期'));
            }
        } else {
            $start_date = null;
            $end_date   = null;
        }

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            return apiError(__('用户信息不存在'));
        }

        $query = WalletLog::query()->where('user_id', $user_id);
        $query->where('symbol', $symbol);

        if ($type) {
            if ($type == WalletLog::TYPE_RECHARGE_IN) { // 充值、扣款
                $query->whereIn('type', [WalletLog::TYPE_RECHARGE_IN, WalletLog::TYPE_RECHARGE_OUT]);
            } else {
                $query->where('type', $type);
            }
        }

        if ($start_date && $end_date) {
            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        // 排除掉冻结、冻结返还的变更类型
        $query->whereIn('change_type', [WalletLog::CHANGE_TYPE_IN, WalletLog::CHANGE_TYPE_OUT, WalletLog::CHANGE_TYPE_FAST_OUT]);

        $paginate   = $query->select('amount', 'type', 'change_type', 'created_at')->orderBy('id', 'desc')->paginate($limit);
        $types      = WalletLog::getTypeMapUser();
        $collection = $paginate->map(function (WalletLog $item) use ($types) {
            if ($item->change_type == WalletLog::CHANGE_TYPE_IN) {
                $change_type = 1;
            } else {
                $change_type = -1;
            }
            $type = $types[$item->type] ?? $item->type;

            return [
                'amount'      => removeTrailingZeros($item->amount),
                'type'        => $type,
                'change_type' => $change_type, // 1: 入账; -1: 出账
                'created_at'  => $item->created_at->format('Y-m-d H:i'),
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }

    // 总收益记录
    public function rewards(Request $request)
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

        $query = WalletLog::query()->where('user_id', $user_id);
        $query->whereIn('type', [WalletLog::TYPE_ALPHA_REWARD_IN, WalletLog::TYPE_INVITE_REWARD, WalletLog::TYPE_TEAM_REWARD, WalletLog::TYPE_SAME_LEVEL_REWARD]);

        $paginate   = $query->orderBy('id', 'desc')->paginate($limit);
        $types      = WalletLog::getTypeMapUser();
        $collection = $paginate->map(function (WalletLog $item) use ($types) {
            $type = $types[$item->type] ?? $item->type;

            return [
                'amount'      => removeTrailingZeros($item->amount),
                'type'        => $type,
                'change_type' => 1, // 1: 入账; -1: 出账
                'created_at'  => $item->created_at->format('Y-m-d H:i'),
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }

    // 昨日收益记录
    public function yesterdayRewards(Request $request)
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

        $query = WalletLog::query()->where('user_id', $user_id);
        $query->whereIn('type', [WalletLog::TYPE_ALPHA_REWARD_IN, WalletLog::TYPE_INVITE_REWARD, WalletLog::TYPE_TEAM_REWARD, WalletLog::TYPE_SAME_LEVEL_REWARD]);

        $yesterday     = Carbon::yesterday()->startOfDay();
        $yesterday_end = Carbon::yesterday()->endOfDay();
        $query->whereBetween('created_at', [$yesterday, $yesterday_end]);

        $paginate   = $query->orderBy('id', 'desc')->paginate($limit);
        $types      = WalletLog::getTypeMapUser();
        $collection = $paginate->map(function (WalletLog $item) use ($types) {
            $type = $types[$item->type] ?? $item->type;

            return [
                'amount'      => removeTrailingZeros($item->amount),
                'type'        => $type,
                'change_type' => 1, // 1: 入账; -1: 出账
                'created_at'  => $item->created_at->format('Y-m-d H:i'),
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }

    // 事件类型列表
    public function types(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|in:USDT,NOX',
        ], [
            'symbol.required' => __('账户类型不能为空'),
            'symbol.in'       => __('账户类型不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $symbol = $request->input('symbol');

        $list  = [];
        $types = WalletLog::getTypeMapUserFilter($symbol);
        foreach ($types as $key => $value) {
            $list[] = [
                'type' => $key,
                'name' => $value,
            ];
        }

        return apiSuccess($list);
    }
}
