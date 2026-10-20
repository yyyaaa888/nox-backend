<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Models\InviteReward;
use App\Models\User;
use App\Models\UserData;
use App\Models\UserRelationClosure;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MyTeamController extends Controller
{
    public function index(Request $request)
    {
        $user    = $request->user();
        $user_id = $user->id;

        $userData = UserData::getInfo($user_id);
        if (! $userData) {
            throw new LogicException(__('用户信息不存在'));
        }

        $team_reward   = $userData->team_reward;
        $invite_reward = $userData->invite_reward;

        // 直推人数
        $direct_num = UserRelationClosure::getDirectCount($user_id);
        // 团队人数
        $team_num = UserRelationClosure::getTeamCount($user_id, UserRelationClosure::MAX_DEPTH);

        // 个人业绩
        $user_perf = User::getPersonalPerformance($user_id);
        // 团队业绩
        $team_perf = User::getTeamPerformance($user_id, UserRelationClosure::MAX_DEPTH);

        // 返回数据
        $data = [
            'address'       => UserService::hideStr($user->address),
            'invite_code'   => $user->invite_code,
            'level'         => $user->level_id,
            'direct_num'    => $direct_num,
            'team_num'      => $team_num,
            'user_perf'     => removeTrailingZeros($user_perf),      // 个人业绩(USDT)
            'team_perf'     => removeTrailingZeros($team_perf),      // 团队业绩(USDT)
            'invite_reward' => removeTrailingZeros($invite_reward),  // 推广收益
            'team_reward'   => removeTrailingZeros($team_reward),    // 团队收益
        ];

        return apiSuccess($data);
    }

    // 直推列表
    public function directs(Request $request)
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

        $paginate   = User::query()->with('userData')->where('invited_uid', $user_id)->paginate($limit);
        $collection = $paginate->map(function (User $child) {
            $userData = $child?->userData;
            $stake    = $userData?->curr_stake ?? '0';

            $reward = InviteReward::getUserRewardForChild($child->invited_uid, $child->id);

            return [
                'address' => UserService::hideStr($child->address),
                'level'   => $child->level_id, // 等级
                'stake'   => removeTrailingZeros($stake), // 个人质押
                'reward'  => removeTrailingZeros($reward), // 推广收益
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }
}
