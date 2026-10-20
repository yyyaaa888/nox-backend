<?php

use App\Models\LevelHistory;
use App\Models\User;
use App\Models\UserData;
use App\Models\UserRelationClosure;
use App\Services\UserRelationService;
use App\Services\UserUpgradeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * 创建用户并设置其个人业绩。
 */
function createUserWithPerf(string $perf, int $levelId = 1, int $pid = 0, int $virtualLevel = 1): User
{
    $user = User::factory()->create(['status' => 1, 'level_id' => $levelId, 'invited_uid' => $pid, 'virtual_level' => $virtualLevel]);
    UserData::addInfo($user->id);
    UserData::query()->where('user_id', $user->id)->update(['perf' => $perf]);

    $UserRelationService = new UserRelationService;
    $UserRelationService->setupUserRelations($user->id, $pid);

    return $user;
}

// 创建用户并设置其个人业绩和团队业绩
function createUserWithUserAndTeamPerf(string $user_perf, string $team_perf, int $levelId = 1): User
{
    $user = createUserWithPerf($user_perf, $levelId, 0);
    createUserWithPerf($team_perf, $levelId, $user->id);

    return $user;
}

// 创建指定数量的团队成员
function createTeamMembers(int $pid, int $team_num, int $eff_num = 0, int $level = 1)
{
    $cid = 0;
    for ($i = 0; $i < $eff_num; $i++) {
        $child = createUserWithPerf('1.000000', $level, $pid);
        $cid = $child->id;
    }
    $other_num = $team_num - $eff_num;
    for ($i = 0; $i < $other_num; $i++) {
        $child = createUserWithPerf('0.000000', $level, $cid > 0 ? $cid : $pid);
        if ($cid == 0) {
            $cid = $child->id;
        }
    }
}

// 创建指定数量的直推并指定成员业绩
function createDirectMembersWithPerf(int $pid, int $direct_num, string $perf, int $level = 1)
{
    for ($i = 0; $i < $direct_num; $i++) {
        createUserWithPerf($perf, $level, $pid);
    }
}

// 创建指定数量的团队成员并指定成员业绩
function createTeamMembersWithPerf(int $pid, int $team_num, string $perf, int $level = 1)
{
    $cid = 0;
    for ($i = 0; $i < $team_num; $i++) {
        $child = createUserWithPerf($perf, $level, $cid > 0 ? $cid : $pid);
        if ($cid == 0) {
            $cid = $child->id;
        }
    }
}

beforeEach(function () {
    // 迁移会在空表上自动插入 V1-V5，先清空再插入测试等级
    DB::table('levels')->delete();
    $config = [
        // id => [code, user_perf, team_perf, direct_num, team_num, weekly_limit_usdt, weekly_limit_nox]
        1 => ['V1', 0, 0, 0, 0, '300.000000', '300.000000'],
        2 => ['V2', 500, 5000, 3, 10, '500.000000', '500.000000'],
        3 => ['V3', 3000, 30000, 5, 10, '1000.000000', '1000.000000'],
        4 => ['V4', 30000, 500000, 10, 50, '3000.000000', '3000.000000'],
        5 => ['V5', 0, 1000000, 20, 100, '5000.000000', '5000.000000'],
    ];
    $rows = [];
    foreach ($config as $id => [$code, $user_perf, $team_perf, $direct_num, $team_num, $usdt, $nox]) {
        $rows[] = [
            'id' => $id,
            'code' => $code,
            'user_perf' => $user_perf,
            'team_perf' => $team_perf,
            'direct_num' => $direct_num,
            'team_num' => $team_num,
            'weekly_limit_usdt' => $usdt,
            'weekly_limit_nox' => $nox,
            'created_at' => '2000-01-01 00:00:00',
            'updated_at' => '2000-01-01 00:00:00',
        ];
    }
    DB::table('levels')->insert($rows);
});

test('测试业绩统计数据准确性', function () {
    $user = createUserWithPerf('500.000000');
    createTeamMembers($user->id, 10, 3);

    $team_data = UserRelationClosure::getTeamPerformances($user->id);
    // 团队人数为 10
    expect($team_data['num'])->toBe(10);
    // 有效直推人数为 3
    expect($team_data['eff'])->toBe(3);
    // 直推人数为 3
    expect($team_data['direct'])->toBe(3);
    // 个人业绩为 500
    expect($team_data['user_perf'])->toBe('500.000000');
    // 团队业绩为 3
    expect($team_data['total'])->toBe('3.000000');
    // 总业绩为 503
    expect($team_data['all'])->toBe('503.000000');
    // 直推业绩为 3
    expect($team_data['direct_total'])->toBe('3.000000');
    // 大区业绩为 1
    expect($team_data['big_total'])->toBe('1.000000');
    // 小区业绩为 2
    expect($team_data['small_total'])->toBe('2.000000');

    expect(User::find($user->id)->level_id)->toBe(1);
});

test('不需要业绩升级到 V1', function () {
    $user = createUserWithPerf('0.000000');

    (new UserUpgradeService)->check($user->id);

    expect(User::find($user->id)->level_id)->toBe(1);

    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->toBeNull();
});

// 个人业绩达到最低阈值升级到 V2
test('个人业绩达到最低阈值时升级到 V2', function () {
    $user = createUserWithPerf('500.000000');
    createTeamMembers($user->id, 10, 3);

    (new UserUpgradeService)->check($user->id);

    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(1)
        ->and($history->new_level)->toBe(2);
});

// 用户会直接升级到达到阈值的最高等级
test('达到阈值时直接升级到满足条件的最高等级', function () {
    $user = createUserWithPerf('0.000000');
    // V5 需有效直推≥20、团队人数≥100：直接 100 个有业绩的直推，团队业绩=100w
    createDirectMembersWithPerf($user->id, 100, '10000.000000');

    (new UserUpgradeService)->check($user->id);

    // 团队业绩100w，升级到 V5
    expect(User::find($user->id)->level_id)->toBe(5);
});

// 个人业绩低于当前等级阈值降级到 V2
test('个人业绩低于当前等级阈值时降级', function () {
    $user = createUserWithPerf('500.000000', 3);

    // 三个有效直推
    createDirectMembersWithPerf($user->id, 3, '1.000000');
    // 7个无业绩团队成员
    createTeamMembersWithPerf($user->id, 7, '0.000000');

    (new UserUpgradeService)->check($user->id);

    // 500 仅达到 V2(500)，从 V3 降级到 V2
    expect(User::find($user->id)->level_id)->toBe(2);

    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(3)
        ->and($history->new_level)->toBe(2);
});

// 直推业绩会计入父用户业绩
test('直推业绩会计入父用户业绩用于升级', function () {
    $parent = createUserWithPerf('0.000000', 1, 0);

    // 三个直推有业绩
    createUserWithPerf('2000.000000', 1, $parent->id);
    createUserWithPerf('2000.000000', 1, $parent->id);
    createUserWithPerf('1000.000000', 1, $parent->id);

    // 7个无业绩团队成员
    createTeamMembersWithPerf($parent->id, 7, '0.000000');

    (new UserUpgradeService)->check($parent->id);

    // 个人业绩0，团队业绩5000，有效直推3，团队人数10 → V2
    expect(User::find($parent->id)->level_id)->toBe(2);
});

test('业绩达标，直推人数不达标，不升级', function () {
    $parent = createUserWithPerf('0.000000', 1, 0);

    // 团队业绩已达标5000，直推人数不达标3人
    createUserWithPerf('2500.000000', 1, $parent->id);
    createUserWithPerf('2500.000000', 1, $parent->id);

    (new UserUpgradeService)->check($parent->id);

    expect(User::find($parent->id)->level_id)->toBe(1)
        ->and(LevelHistory::where('user_id', $parent->id)->count())->toBe(0);
});

test('业绩达标，团队人数不达标，不升级', function () {
    $parent = createUserWithPerf('0.000000', 1, 0);

    // 团队业绩已达标5000，直推人数达标3人，团队人数不达标10人
    createUserWithPerf('2000.000000', 1, $parent->id);
    createUserWithPerf('2000.000000', 1, $parent->id);
    createUserWithPerf('1000.000000', 1, $parent->id);

    (new UserUpgradeService)->check($parent->id);

    expect(User::find($parent->id)->level_id)->toBe(1)
        ->and(LevelHistory::where('user_id', $parent->id)->count())->toBe(0);
});

test('V1级别，不升级', function () {
    $user = createUserWithPerf('100.000000', 1);

    (new UserUpgradeService)->check($user->id);

    // 100 未达 V2(个人500)，保持级别1，且不产生等级历史
    expect(User::find($user->id)->level_id)->toBe(1)
        ->and(LevelHistory::count())->toBe(0);
});

test('V2级别，不升级', function () {
    $user = createUserWithPerf('2000.000000', 2);

    // 团队成员20 有效直推5
    createTeamMembers($user->id, 20, 5);

    (new UserUpgradeService)->check($user->id);

    // 未达 V3(个人3000)，保持级别2，且不产生等级历史
    expect(User::find($user->id)->level_id)->toBe(2)
        ->and(LevelHistory::count())->toBe(0);
});

test('V3级别，不升级', function () {
    $user = createUserWithPerf('20000.000000', 3);

    // 团队成员50 有效直推10
    createTeamMembers($user->id, 50, 10);

    (new UserUpgradeService)->check($user->id);

    // 未达 V4(个人30000)，保持级别3，且不产生等级历史
    expect(User::find($user->id)->level_id)->toBe(3)
        ->and(LevelHistory::count())->toBe(0);
});

test('V4级别，不升级', function () {
    $user = createUserWithPerf('0.000000', 4);

    // 有效直推20
    createDirectMembersWithPerf($user->id, 20, '30000.000000');
    // 其他无业绩的团队成员80
    createTeamMembersWithPerf($user->id, 80, '0.000000');

    (new UserUpgradeService)->check($user->id);

    // 未达 V4(团队100w)，保持级别4，且不产生等级历史
    expect(User::find($user->id)->level_id)->toBe(4)
        ->and(LevelHistory::count())->toBe(0);
});

test('V5级别，不变', function () {
    $user = createUserWithPerf('1000000.000000', 5);

    // 有效直推200
    createDirectMembersWithPerf($user->id, 200, '10000.000000');

    (new UserUpgradeService)->check($user->id);

    // 保持级别5，且不产生等级历史
    expect(User::find($user->id)->level_id)->toBe(5)
        ->and(LevelHistory::count())->toBe(0);
});

// ---- 持有节点保底 V3 ----
test('持节点用户业绩不足从 V1 升到 V3 且 remark 标注持有节点', function () {
    $user = createUserWithPerf('0.000000', 1, 0);
    UserData::query()->where('user_id', $user->id)->update(['node_id' => 1]);

    (new UserUpgradeService)->check($user->id);

    expect(User::find($user->id)->level_id)->toBe(3);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(1)
        ->and($history->new_level)->toBe(3)
        ->and($history->remark)->toContain('持有节点');
});

test('持节点用户 V3 且业绩不足时保持 V3 不降级', function () {
    $user = createUserWithPerf('0.000000', 3, 0);
    UserData::query()->where('user_id', $user->id)->update(['node_id' => 1]);

    (new UserUpgradeService)->check($user->id);

    expect(User::find($user->id)->level_id)->toBe(3);
    expect(LevelHistory::where('user_id', $user->id)->count())->toBe(0);
});

test('持节点用户业绩尽失从 V5 降至 V3（保底）且 remark 标注持有节点', function () {
    $user = createUserWithPerf('0.000000', 5, 0);
    UserData::query()->where('user_id', $user->id)->update(['node_id' => 1]);

    (new UserUpgradeService)->check($user->id);

    expect(User::find($user->id)->level_id)->toBe(3);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(5)
        ->and($history->new_level)->toBe(3)
        ->and($history->remark)->toContain('持有节点');
});

test('持节点用户按业绩达到 V4 时不被保底压回且 remark 不标注持有节点', function () {
    $user = createUserWithPerf('30000.000000', 1, 0);
    UserData::query()->where('user_id', $user->id)->update(['node_id' => 1]);
    createDirectMembersWithPerf($user->id, 10, '1.000000'); // 有效直推 10
    createTeamMembersWithPerf($user->id, 40, '0.000000');   // 团队人数补到 50

    (new UserUpgradeService)->check($user->id);

    // 个人业绩 30000 达 V4（用户路径），保底只是下限，不封顶
    expect(User::find($user->id)->level_id)->toBe(4);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history->remark)->not->toContain('持有节点');
});

// ---- 虚拟等级 ----
test('业绩级别低于虚拟等级时按虚拟等级', function () {
    $user = createUserWithPerf('0.000000', 1, 0, 3);

    (new UserUpgradeService)->check($user->id);

    expect(User::find($user->id)->level_id)->toBe(3);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(1)
        ->and($history->new_level)->toBe(3)
        ->and($history->remark)->toContain('虚拟等级V3');
});

test('业绩级别高于虚拟等级时按实际级别', function () {
    $user = createUserWithPerf('500.000000', 1, 0, 1);
    createTeamMembers($user->id, 10, 3);

    (new UserUpgradeService)->check($user->id);

    // 业绩达标 V2 > 虚拟等级 V1，按实际级别 V2
    expect(User::find($user->id)->level_id)->toBe(2);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->remark)->not->toContain('虚拟等级');
});

test('业绩级别等于虚拟等级时保持不变', function () {
    $user = createUserWithPerf('2000.000000', 2, 0, 2);
    createTeamMembers($user->id, 20, 5);

    (new UserUpgradeService)->check($user->id);

    // 业绩 V2 = 虚拟等级 V2，保持不变，不产生记录
    expect(User::find($user->id)->level_id)->toBe(2)
        ->and(LevelHistory::where('user_id', $user->id)->count())->toBe(0);
});

test('虚拟等级保护用户不被降级到虚拟等级以下', function () {
    $user = createUserWithPerf('0.000000', 5, 0, 4);

    (new UserUpgradeService)->check($user->id);

    // 业绩尽失按业绩应为 V1，虚拟等级 V4 托底 → V4
    expect(User::find($user->id)->level_id)->toBe(4);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(5)
        ->and($history->new_level)->toBe(4)
        ->and($history->remark)->toContain('虚拟等级V4');
});

test('已处于虚拟等级时业绩尽失不产生变更记录', function () {
    $user = createUserWithPerf('0.000000', 5, 0, 5);

    (new UserUpgradeService)->check($user->id);

    expect(User::find($user->id)->level_id)->toBe(5)
        ->and(LevelHistory::where('user_id', $user->id)->count())->toBe(0);
});

test('调低虚拟等级后按业绩重新降级', function () {
    $user = createUserWithPerf('0.000000', 5, 0, 5);

    (new UserUpgradeService)->check($user->id);
    expect(User::find($user->id)->level_id)->toBe(5)
        ->and(LevelHistory::where('user_id', $user->id)->count())->toBe(0);

    // 虚拟等级调低到 V2 → 按业绩(1)与虚拟(2)取高 → V2
    $user->virtual_level = 2;
    $user->save();
    (new UserUpgradeService)->check($user->id);

    expect(User::find($user->id)->level_id)->toBe(2);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(5)
        ->and($history->new_level)->toBe(2)
        ->and($history->remark)->toContain('虚拟等级V2');
});

test('虚拟等级低于节点保底时按节点保底 V3', function () {
    $user = createUserWithPerf('0.000000', 1, 0, 2);
    UserData::query()->where('user_id', $user->id)->update(['node_id' => 1]);

    (new UserUpgradeService)->check($user->id);

    // 虚拟 V2，节点保底 V3 取更高 → V3，remark 同时标注两者
    expect(User::find($user->id)->level_id)->toBe(3);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->remark)->toContain('虚拟等级V2')
        ->and($history->remark)->toContain('持有节点');
});

test('虚拟等级高于节点保底时按虚拟等级', function () {
    $user = createUserWithPerf('0.000000', 1, 0, 5);
    UserData::query()->where('user_id', $user->id)->update(['node_id' => 1]);

    (new UserUpgradeService)->check($user->id);

    // 虚拟 V5 高于节点保底 V3 → V5，无节点保底标注
    expect(User::find($user->id)->level_id)->toBe(5);
    $history = LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->remark)->toContain('虚拟等级V5')
        ->and($history->remark)->not->toContain('持有节点');
});
