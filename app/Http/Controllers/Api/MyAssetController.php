<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Models\InviteReward;
use App\Models\NodeOrder;
use App\Models\TeamReward;
use App\Models\UserData;
use App\Models\UserRelationClosure;
use App\Models\WalletLog;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MyAssetController extends Controller
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

        // 邀请人数
        $invite_num = UserRelationClosure::getDirectCount($user_id);
        // 团队人数
        $team_num = UserRelationClosure::getTeamCount($user_id, UserRelationClosure::MAX_DEPTH);

        // 个人算力
        $user_power = $userData->curr_power;
        // 全网算力
        $total_power = UserData::getTotalPower();

        // 读取修正数据
        $home_power  = (int)getGlobalConfig('site_home_power', 0);
        $total_power = bcadd($total_power, $home_power, 0);

        $node_count = NodeOrder::query()->where('user_id', $user_id)->where('status', NodeOrder::STATUS_PAID)->count();

        // 对所有数字进行去除尾随0处理
        $current_usdt = removeTrailingZeros($userData->current_usdt);
        $current_nox  = removeTrailingZeros($userData->current_nox);

        $usdt_min_amount = (int) getGlobalConfig('site_w_min_usdt', '0'); // USDT最小提现金额
        $usdt_fee_rate   = getGlobalConfig('site_w_fee_rate_usdt', '0'); // USDT提现手续费(%)
        // $recharge_usdt_min = (int)getGlobalConfig('site_recharge_min_usdt', '0'); // USDT最小充值金额

        $nox_min_amount = (int) getGlobalConfig('site_w_min_nox', '0'); // NOX最小提现金额
        $nox_fee_rate   = getGlobalConfig('site_w_fee_rate_nox', '0'); // NOX提现手续费(%)
        // $recharge_nox_min = (int)getGlobalConfig('site_recharge_min_nox', '0'); // NOX最小充值金额

        // 收益数据
        $team_reward   = $userData->team_reward;
        $invite_reward = $userData->invite_reward;
        $total_reward  = bcadd($team_reward, $invite_reward, 6);
        $total_reward  = bcadd($total_reward, $userData->stake_reward, 6);

        // 昨日收益
        $yesterday       = Carbon::yesterday()->format('Y-m-d');
        $y_reward        = WalletLog::getDayStakeReward($user_id, $yesterday);

        // 返回数据
        $data = [
            'address'     => UserService::hideStr($user->address),
            'level'       => $user->level_id,
            'node_count'  => $node_count,
            'invite_num'  => $invite_num,
            'team_num'    => $team_num,
            'user_power'  => removeTrailingZeros($user_power),
            'total_power' => removeTrailingZeros($total_power),
            'is_vip'      => $is_vip,
            'assets'      => [
                'USDT' => [
                    'current'    => $current_usdt,
                    'min_amount' => $usdt_min_amount,
                    // 'recharge_min_am' => $recharge_usdt_min,
                    'fee_rate' => removeTrailingZeros($usdt_fee_rate),
                ],
                'NOX' => [
                    'current'    => $current_nox,
                    'min_amount' => $nox_min_amount,
                    // 'recharge_min_am' => $recharge_nox_min,
                    'fee_rate' => removeTrailingZeros($nox_fee_rate),
                ],
            ],
            'reward' => [
                'total_reward'     => removeTrailingZeros($total_reward),
                'yesterday_reward' => removeTrailingZeros($y_reward),
                'team_reward'      => removeTrailingZeros($team_reward),
                'invite_reward'    => removeTrailingZeros($invite_reward),
            ],
        ];

        return apiSuccess($data);
    }
}
