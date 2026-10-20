<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Partner;
use App\Models\User;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        // 获取首页广告图
        $banners = Banner::getListByPos(Banner::POS_INDEX);
        $banners = $banners->map(function (Banner $item) {
            return [
                'img'  => asset('/storage/'.$item->img),
                'href' => $item->href,
            ];
        });

        $nox_pool    = UserData::query()->sum('current_nox');
        $usdt_pool   = UserData::query()->sum('current_usdt');
        $total_power = UserData::query()->sum('curr_power');
        $total_user  = (string) User::query()->count();

        // 读取修正数据
        $home_nox_pool  = (int)getGlobalConfig('site_home_nox_pool', 0);
        $home_usdt_pool = (int)getGlobalConfig('site_home_usdt_pool', 0);
        $home_power     = (int)getGlobalConfig('site_home_power', 0);
        $home_user_cc   = (int)getGlobalConfig('site_home_user_cc', 0);

        $nox_pool    = bcadd($nox_pool, $home_nox_pool, 0);
        $usdt_pool   = bcadd($usdt_pool, $home_usdt_pool, 0);
        $total_power = bcadd($total_power, $home_power, 0);
        $total_user  = bcadd($total_user, $home_user_cc, 0);

        // 仪表板
        $dashboard = [
            'nox_pool'    => $nox_pool, // 全网NOX池
            'usdt_pool'   => $usdt_pool, // 全网USDT池
            'total_power' => $total_power, // 全网算力
            'total_user'  => $total_user, // 全网人数
        ];

        // // 昨日收益流
        // $rewards = [
        //     [
        //         'address' => UserService::hideStr('0x0000000000000000000000000000000000000000'),
        //         'time' => date('Y-m-d H:i'),
        //         'amount' => '100', // NOX收益
        //     ],
        // ];

        // // 获取联系方式
        // $show_contact = getGlobalConfig('site_show_contact');
        // $contact_tg = getGlobalConfig('site_contact_tg');
        // $contact_x = getGlobalConfig('site_contact_x');

        return apiSuccess([
            'banners'   => $banners,
            'dashboard' => $dashboard,
            // 'rewards'   => $rewards,
            // 'show_contact' => $show_contact ? true : false,
            // 'contact' => $show_contact ? [
            //     'tg' => $contact_tg,
            //     'x' => $contact_x,
            // ] : null,
        ]);
    }

    // 昨日收益流
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

        $limit = $request->input('limit', 15);

        $query = WalletLog::query()->with('user')->where('symbol', WalletLog::SYMBOL_NOX);
        $query->whereIn('type', [WalletLog::TYPE_ALPHA_REWARD_IN, WalletLog::TYPE_INVITE_REWARD, WalletLog::TYPE_TEAM_REWARD, WalletLog::TYPE_SAME_LEVEL_REWARD]);

        $yesterday     = Carbon::yesterday()->startOfDay();
        $yesterday_end = Carbon::yesterday()->endOfDay();
        $query->whereBetween('created_at', [$yesterday, $yesterday_end]);

        $paginate   = $query->orderBy('id', 'desc')->paginate($limit);
        $collection = $paginate->map(function (WalletLog $item) {
            return [
                'address' => UserService::hideStr($item?->user?->address),
                'time'    => $item->created_at->format('Y-m-d H:i'),
                'amount'  => removeTrailingZeros($item->amount),
            ];
        });
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }

    // public function about(Request $request)
    // {
    //     // 获取关于我们广告图
    //     $banners = Banner::getListByPos(Banner::POS_ABOUT);
    //     $banners = $banners->map(function (Banner $item) {
    //         return [
    //             'img' => asset('/storage/' . $item->img),
    //             'href' => $item->href,
    //         ];
    //     });

    //     $lang = Language::getLang();
    //     $about1 = Content::getContentByCode(Content::CODE_ABOUT1, $lang);
    //     $about2 = Content::getContentByCode(Content::CODE_ABOUT2, $lang);

    //     return apiSuccess([
    //         'banners' => $banners,
    //         'about1' => $about1,
    //         'about2' => $about2,
    //     ]);
    // }

    // 合作机构
    public function partners(Request $request)
    {
        $list     = Partner::query()->where('is_show', Partner::IS_SHOW_SHOW)->get();
        $partners = [];
        /** @var Partner $item */
        foreach ($list as $item) {
            $partners[] = [
                'name'     => $item->name,
                'logo_url' => asset('/storage/'.$item->logo_url),
            ];
        }

        return apiSuccess($partners);
    }
}
