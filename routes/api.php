<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\User\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// if (!app()->environment('production')) {
//     Route::post('newUser', [UserController::class, 'newUser'])->middleware(['language']);
// }

// 登录
Route::middleware(['language'])->group(function () {
    Route::get('api-login', function () {
        return apiError(__('Please log in first'), \App\ErrorCode::ACCOUNT_LOGOUT);
    })->name('login');
    // 用户注册或登录
    Route::post('user-login', [UserController::class, 'userLogin']);
    //是否新用户
    Route::get('is-new', [UserController::class, 'isNewUser']);
});

// 公共接口
Route::middleware(['language'])->group(function () {
    Route::get('index', [\App\Http\Controllers\Api\IndexController::class, 'index']); //首页
    Route::get('index/rewards', [\App\Http\Controllers\Api\IndexController::class, 'rewards']); // 昨日收益流
    // Route::get('index/lines', [\App\Http\Controllers\Api\IndexController::class, 'lines']); //路线图
    // Route::get('index/partners', [\App\Http\Controllers\Api\IndexController::class, 'partners']); //合作机构
    Route::get('announcements', [\App\Http\Controllers\Api\ArticleController::class, 'index']); // 系统公告
    Route::get('announcement', [\App\Http\Controllers\Api\ArticleController::class, 'show']); // 系统公告详情
    // Route::get('database/home', [\App\Http\Controllers\Api\DatabaseController::class, 'index']); // 数据库页面
    // Route::get('database/documents', [\App\Http\Controllers\Api\DatabaseController::class, 'documents']); // 相关文档
    // Route::get('database/posters', [\App\Http\Controllers\Api\DatabaseController::class, 'posters']); // 相关海报
    // Route::get('database/videos', [\App\Http\Controllers\Api\DatabaseController::class, 'videos']); // 相关视频
    // Route::get('database/videoMeetings', [\App\Http\Controllers\Api\DatabaseController::class, 'videoMeetings']); // 视频会议

});

Route::middleware(['language', 'auth:sanctum', 'checkSign'])->group(function () {

    // 我的
    Route::get('my/asset', [\App\Http\Controllers\Api\MyAssetController::class, 'index']); //我的资产
    Route::get('my/assetLogs', [\App\Http\Controllers\Api\MyAssetLogController::class, 'index']); //资金流水
    Route::get('my/assetLogs/rewards', [\App\Http\Controllers\Api\MyAssetLogController::class, 'rewards']); //总收益记录
    Route::get('my/assetLogs/yesterdayRewards', [\App\Http\Controllers\Api\MyAssetLogController::class, 'yesterdayRewards']); //昨日收益记录
    Route::get('my/assetLogs/types', [\App\Http\Controllers\Api\MyAssetLogController::class, 'types']); // 资金流水类型列表
    // 算力资产包
    Route::get('power/home', [\App\Http\Controllers\Api\PowerController::class, 'home']); // 资产包首页
    Route::post('power/create', [\App\Http\Controllers\Api\PowerController::class, 'create']); // 申购
    Route::get('power/orders', [\App\Http\Controllers\Api\PowerController::class, 'orders']); // 我的订单
    Route::post('power/exit', [\App\Http\Controllers\Api\PowerController::class, 'exit']); // 首月退出
    Route::post('power/done', [\App\Http\Controllers\Api\PowerController::class, 'done']); // 支付确认
    Route::post('power/cancel', [\App\Http\Controllers\Api\PowerController::class, 'cancel']); // 取消支付
    // 算力加速节点
    Route::get('node/home', [\App\Http\Controllers\Api\NodeController::class, 'home']); // 节点首页
    Route::post('node/create', [\App\Http\Controllers\Api\NodeController::class, 'create']); // 申购
    Route::post('node/done', [\App\Http\Controllers\Api\NodeController::class, 'done']); // 支付确认
    Route::post('node/cancel', [\App\Http\Controllers\Api\NodeController::class, 'cancel']); // 取消支付
    Route::get('node/orders', [\App\Http\Controllers\Api\NodeController::class, 'orders']); // 我的节点

    // 团队
    Route::get('my/team', [\App\Http\Controllers\Api\MyTeamController::class, 'index']); //我的团队
    Route::get('my/directs', [\App\Http\Controllers\Api\MyTeamController::class, 'directs']); //直推列表

    // 会员中心
    Route::get('memberCenter', [\App\Http\Controllers\Api\MemberCenterController::class, 'index']); //会员中心
    Route::get('memberCenter/inviteRewards', [\App\Http\Controllers\Api\MemberCenterController::class, 'inviteRewards']); //推广收益列表
    Route::get('memberCenter/teamRewards', [\App\Http\Controllers\Api\MemberCenterController::class, 'teamRewards']); //团队收益列表

    // 提现
    Route::get('withdraw', [\App\Http\Controllers\Api\WithdrawController::class, 'index']); //提现页面
    Route::post('withdraw', [\App\Http\Controllers\Api\WithdrawController::class, 'create']); //提现
    Route::post('withdraw/done', [\App\Http\Controllers\Api\WithdrawController::class, 'done']); //提现成功回调

});
