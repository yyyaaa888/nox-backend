<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Models\InviteReward;
use App\Models\Level;
use App\Models\RewardRate;
use App\Models\TeamReward;
use App\Models\UserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MemberCenterController extends Controller
{
    public function index(Request $request)
    {
        $user    = $request->user();
        $user_id = $user->id;

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            throw new LogicException(__('用户信息不存在'));
        }

        $is_vip = $userData->node_id > 0;

        $levels = Level::LEVEL_MAP;

        $invite_rates = RewardRate::query()->where('type', RewardRate::TYPE_INVITE)->get();
        $team_rates   = RewardRate::query()->where('type', RewardRate::TYPE_TEAM)->get();

        $invite_rules = [];
        $team_rules   = [];
        foreach ($levels as $level => $level_name) {
            $invite_rate1   = $invite_rates->where('rank', $level)->where('has_node', 0);
            $invite_rate2   = $invite_rates->where('rank', $level)->where('has_node', 1);
            $invite_rules[] = [
                'level'      => $level_name,
                'base_rates' => $invite_rate1->pluck('rate')->map(function ($item) {
                    return removeTrailingZeros($item).'%';
                })->implode('/'),
                'node_rates' => $invite_rate2->pluck('rate')->map(function ($item) {
                    return removeTrailingZeros($item).'%';
                })->implode('/'),
            ];

            $team_rate1   = $team_rates->where('rank', $level)->where('has_node', 0);
            $team_rate2   = $team_rates->where('rank', $level)->where('has_node', 1);
            $team_rules[] = [
                'level'      => $level_name,
                'base_rates' => $team_rate1->pluck('rate')->map(function ($item) {
                    return removeTrailingZeros($item).'%';
                })->implode('/'),
                'node_rates' => $team_rate2->pluck('rate')->map(function ($item) {
                    return removeTrailingZeros($item).'%';
                })->implode('/'),
            ];
        }

        // 返回数据
        $data = [
            'is_vip'       => $is_vip,
            'invite_rules' => $invite_rules,
            'team_rules'   => $team_rules,
        ];

        return apiSuccess($data);
    }

    // 推广收益列表
    public function inviteRewards(Request $request)
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

        $paginate   = InviteReward::query()->where('user_id', $user_id)->paginate($limit);
        $collection = $paginate->map(function (InviteReward $item) {
            return [
                'time'   => $item->created_at->format('Y-m-d H:i'), // 时间
                'reward' => removeTrailingZeros($item->reward_am), // 金额
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }

    // 团队收益列表
    public function teamRewards(Request $request)
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

        $paginate   = TeamReward::query()->where('user_id', $user_id)->paginate($limit);
        $collection = $paginate->map(function (TeamReward $item) {
            return [
                'time'   => $item->created_at->format('Y-m-d H:i'), // 时间
                'reward' => removeTrailingZeros($item->reward_am), // 金额
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }
}
