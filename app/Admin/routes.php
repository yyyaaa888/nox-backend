<?php

use Dcat\Admin\Admin;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    // 主页
    $router->get('/', 'HomeController@index');
    $router->get('api/users', 'AjaxController@users');

    // 系统
    $router->resource('auth/users', 'Admin\AdminUsersController'); // 管理员
    $router->resource('auth/loginLogs', 'Admin\AdminLoginLogsController'); // 管理员登录日志
    $router->any('auth/ignoreChangePasswordPrompt', 'AuthController@ignoreChangePasswordPrompt'); // 忽略修改密码提示

    // 系统配置
    $router->get('/config', 'ConfigController@index');
    $router->any('/config/change', 'ConfigController@change');
    // 系统信息
    $router->get('/sysinfo', 'SysinfoController@index');

    // 区块链配置
    $router->resource('chains', 'ChainController');

    // 用户
    $router->get('users/setVirtualLevel', 'UserController@setVirtualLevel'); // 用户虚拟等级
    // $router->get('users/clearPayPassword', 'UserController@clearPayPassword'); // 清除支付密码
    $router->resource('users', 'UserController'); // 用户列表
    $router->resource('userDatas', 'UserDatasController'); // 用户数据

    // 团队
    $router->resource('levels', 'Team\LevelsController'); // 等级设置
    $router->resource('levelHistories', 'Team\LevelHistoriesController'); // 等级记录
    $router->resource('inviteRewards', 'InviteRewardsController'); // 分享奖励
    $router->resource('teamRewards', 'TeamRewardsController'); // 团队奖励
    $router->resource('rewardRates', 'RewardRatesController'); // 奖励比例
    $router->resource('userPerfs', 'Team\UserPerfsController'); // 用户业绩
    $router->resource('teams', 'Team\TeamsController'); // 推荐关系
    $router->get('team/trees', 'Team\UserTreesController@index'); // 推荐关系查询
    $router->resource('userPerfLogs', 'Team\UserPerfLogsController'); // 业绩日志

    // 社区
    $router->resource('fees', 'FeesController'); // 手续费
    $router->resource('feeLogs', 'FeeLogsController'); // 手续费明细
    // $router->resource('feeAllocs', 'FeeAllocsController'); // 手续费分配
    // $router->resource('feeSetts', 'FeeSettsController'); // 节点手续费分红
    $router->resource('nodeFeeAllocs', 'NodeFeeAllocsController'); // 节点池分红
    $router->resource('nodeFeeLogs', 'NodeFeeLogsController'); // 节点池分红明细

    // 内容
    $router->post('articles/sort', 'ArticlesController@sort')->name('articles-sort');
    $router->resource('articles', 'ArticlesController'); // 公告管理
    $router->resource('articleCates', 'ArticleCatesController'); // 公告类型
    $router->post('banners/sort', 'BannersController@sort')->name('banners-sort');
    $router->resource('banners', 'BannersController'); // banner管理
    // $router->resource('contents', 'ContentsController'); // 文字管理
    // $router->post('partners/sort', 'PartnersController@sort')->name('partners-sort');
    // $router->resource('partners', 'PartnersController'); // 合作机构
    // $router->resource('databases', 'DatabasesController'); // 数据库管理

    // 资产
    $router->resource('assets', 'Asset\AssetController'); // 可用资产管理
    $router->get('userAssets/recharge', 'Asset\UserAssetsController@recharge'); // 充值
    $router->resource('userAssets', 'Asset\UserAssetsController'); // 用户资产
    $router->resource('assetLogs', 'Asset\AssetLogsController'); // 资产日志
    $router->resource('withdraws', 'WithdrawsController'); // 提现记录
    // $router->resource('userRecharges', 'Asset\UserRechargesController'); // 用户充值记录
    $router->resource('recharges', 'Asset\RechargesController'); // 充值扣款记录
    $router->resource('payOrders', 'PayOrdersController'); // 支付记录

    // 算力资产包
    $router->resource('alphaProducts', 'AlphaProductsController'); // 资产包产品
    $router->resource('alphaOrders', 'AlphaOrdersController'); // 资产包订单
    $router->resource('alphaRewards', 'AlphaRewardsController'); // 资产包收益

    // 算力加速节点
    $router->resource('nodeProducts', 'NodeProductsController'); // 节点产品
    $router->resource('nodeOrders', 'NodeOrdersController'); // 节点订单
});
