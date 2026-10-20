# NOX Alpha 子项目 4（推广收益 / 团队收益 / 平级奖）实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 在 nox-backend 上落地 NOX Alpha 子项目 4：推广收益（下线购买资产包按推荐层抽成）、团队收益（下线静态收益按层抽成）、平级奖（同等级上级团队奖 10%），全部经 `creditEarnings` 入额度槽。

**Architecture:** 新建 `reward_rates` 率表（type×rank×has_node×layer→rate，种子填满既定制度，后台可配）、补 `invite_rewards`/`team_rewards` 迁移（后者加 layer/has_node/is_same_level 列）、`RewardService::distributeInvite`（挂 `AlphaOrderService::payCallback`）+ `distributeTeam`（挂 `AlphaRewardService::settleOne`）。复用 `UserRelationClosure::getAncestorsWithDepthLimit` 取推荐链、`AlphaQuotaService::creditEarnings` 入槽、`NodeOrder` 判持节点。

**Tech Stack:** Laravel 12 / PHP 8.2 / MySQL（测试 sqlite :memory:）/ Redis / dcat-admin / Pest / bcmath。

**Spec:** `docs/superpowers/specs/2026-09-01-nox-alpha-promotion-rewards-design.md`

## Global Constraints

- PHP >= 8.2；所有金额一律 **bcmath 字符串运算**（`bcadd`/`bcsub`/`bcmul`/`bccomp`，账本 scale 6，中间 scale 8，`bcround2`/`bcfloor2` 显式 scale 6），禁止 float/double。
- 所有可用余额变动必须走 `WalletLog::addLog()` / `AlphaQuotaService::creditEarnings`；禁止直接 `DB::raw` 改 `user_datas` **余额列**（`current_*`/`freeze_*`）。额度槽列由 `AlphaQuotaService` 专管。`invite_reward`/`team_reward` 是**累计计数器**（非余额列），可针对性 `update(['invite_reward' => DB::raw('... + '.$am)])`。
- 奖励类型常量必须同步 `TYPE_MAP` 与用户侧 `getTypeMapUser()`/`getTypeMapUserFilter()`（既定教训）。
- UI/提示文案走 `__('...')`（中文）。
- 遵循现有代码风格：Service 多为静态方法；Admin 控制器继承 `AdminController`。

---

## 文件结构

**新增**
- `database/migrations/2026_09_01_000001_create_invite_rewards_table.php`（从 sql/nox.sql 重建）
- `database/migrations/2026_09_01_000002_create_team_rewards_table.php`（重建 + layer/has_node/is_same_level 列）
- `database/migrations/2026_09_01_000003_create_reward_rates_table.php`（reward_rates + 种子）
- `app/Models/RewardRate.php`
- `app/Services/RewardService.php`
- `app/Admin/Controllers/RewardRatesController.php`
- `app/Admin/Controllers/InviteRewardsController.php`
- `app/Admin/Controllers/TeamRewardsController.php`
- 测试：`tests/Feature/RewardTest.php`

**修改**
- `app/Models/WalletLog.php`（TYPE_INVITE_REWARD=150/TYPE_TEAM_REWARD=151/TYPE_SAME_LEVEL_REWARD=152 + 三个 map）
- `app/Models/TeamReward.php`（casts：layer/has_node/is_same_level int）
- `app/Services/AlphaOrderService.php`（payCallback 末尾调 distributeInvite）
- `app/Services/AlphaRewardService.php`（settleOne 末尾调 distributeTeam）
- `app/Admin/routes.php`（rewardRates/inviteRewards/teamRewards resource）

---

## Task 1: reward 表迁移 + 模型 + 常量

**Files:**
- Create: `database/migrations/2026_09_01_000001_create_invite_rewards_table.php`
- Create: `database/migrations/2026_09_01_000002_create_team_rewards_table.php`
- Create: `database/migrations/2026_09_01_000003_create_reward_rates_table.php`
- Create: `app/Models/RewardRate.php`
- Modify: `app/Models/TeamReward.php`（casts）
- Modify: `app/Models/WalletLog.php`（常量）
- Test: `tests/Feature/RewardTest.php`（冒烟）

**Interfaces:**
- Produces: `RewardRate` 模型（字段 type/rank/has_node/layer/rate，唯一 `(type, rank, has_node, layer)`）；`invite_rewards`/`team_rewards` 表（team_rewards 含 layer/has_node/is_same_level）；`WalletLog::TYPE_INVITE_REWARD=150`/`TYPE_TEAM_REWARD=151`/`TYPE_SAME_LEVEL_REWARD=152`。

- [ ] **Step 1: 写迁移文件**

`2026_09_01_000001_create_invite_rewards_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invite_rewards', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('上级id');
            $table->unsignedInteger('child_id')->comment('子用户id');
            $table->unsignedInteger('order_id')->comment('订单id');
            $table->decimal('order_am', 20, 6)->unsigned()->default(0)->comment('订单金额');
            $table->decimal('reward_am', 20, 6)->unsigned()->default(0)->comment('奖励金额');
            $table->decimal('reward_rate', 10, 2)->unsigned()->default(0)->comment('比例(%)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('user_id');
            $table->index('child_id');
            $table->index('order_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('invite_rewards');
    }
};
```

`2026_09_01_000002_create_team_rewards_table.php`（含新增列）：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_rewards', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('上级id');
            $table->unsignedInteger('child_id')->comment('子用户id');
            $table->unsignedInteger('order_id')->comment('订单id');
            $table->unsignedTinyInteger('level')->comment('上级等级');
            $table->unsignedTinyInteger('prev_level')->default(0)->comment('前一级等级');
            $table->unsignedTinyInteger('child_level')->comment('子用户等级');
            $table->unsignedTinyInteger('layer')->default(1)->comment('推荐层');
            $table->unsignedTinyInteger('has_node')->default(0)->comment('上级持节点');
            $table->unsignedTinyInteger('is_same_level')->default(0)->comment('平级奖');
            $table->decimal('order_am', 20, 6)->unsigned()->comment('静态收益额');
            $table->decimal('reward_am', 20, 6)->unsigned()->comment('团队奖金额');
            $table->decimal('reward_rate', 10, 2)->unsigned()->comment('收益率(%)');
            $table->date('sett_date')->comment('结算日期');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('user_id');
            $table->index('child_id');
            $table->index('order_id');
            $table->index('sett_date');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('team_rewards');
    }
};
```

`2026_09_01_000003_create_reward_rates_table.php`（含种子）：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 10)->comment('invite/team');
            $table->unsignedTinyInteger('rank')->comment('V等级');
            $table->unsignedTinyInteger('has_node')->comment('0=基础 1=持节点');
            $table->unsignedTinyInteger('layer')->comment('推荐层');
            $table->decimal('rate', 10, 2)->unsigned()->comment('奖励率(%)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['type', 'rank', 'has_node', 'layer']);
        });

        // 既定制度种子
        $rates = [
            'invite' => [
                1 => [0 => ['2.00', '1.00', '1.00'], 1 => ['3.00', '2.00', '1.00']],
                2 => [0 => ['5.00', '2.00', '1.00'], 1 => ['7.00', '3.00', '1.00']],
                3 => [0 => ['7.00', '2.00', '1.00', '1.00'], 1 => ['10.00', '3.00', '1.00', '1.00']],
                4 => [0 => ['7.00', '4.00', '2.00', '1.00'], 1 => ['10.00', '5.00', '3.00', '1.00']],
                5 => [0 => ['8.00', '4.00', '2.00', '2.00', '2.00'], 1 => ['12.00', '5.00', '3.00', '3.00', '3.00']],
            ],
            'team' => [
                1 => [0 => ['2.00', '1.00', '1.00'], 1 => ['3.00', '1.00', '1.00']],
                2 => [0 => ['4.00', '2.00', '1.00'], 1 => ['5.00', '3.00', '1.00']],
                3 => [0 => ['5.00', '2.00', '2.00', '1.00'], 1 => ['7.00', '3.00', '3.00', '1.00']],
                4 => [0 => ['7.00', '4.00', '2.00', '2.00'], 1 => ['10.00', '5.00', '3.00', '3.00']],
                5 => [0 => ['7.00', '5.00', '4.00', '2.00', '2.00'], 1 => ['10.00', '7.00', '5.00', '3.00', '3.00']],
            ],
        ];
        $now = now();
        foreach ($rates as $type => $byRank) {
            foreach ($byRank as $rank => $byNode) {
                foreach ($byNode as $hasNode => $layers) {
                    foreach ($layers as $i => $rate) {
                        DB::table('reward_rates')->insert([
                            'type' => $type, 'rank' => $rank, 'has_node' => $hasNode,
                            'layer' => $i + 1, 'rate' => $rate,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('reward_rates');
    }
};
```

- [ ] **Step 2: 写模型**

`app/Models/RewardRate.php`：
```php
<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class RewardRate extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'reward_rates';
    protected $guarded = [];

    public const TYPE_INVITE = 'invite'; // 推广
    public const TYPE_TEAM   = 'team';   // 团队

    public const TYPE_MAP = [
        self::TYPE_INVITE => '推广收益',
        self::TYPE_TEAM   => '团队收益',
    ];
}
```

`app/Models/TeamReward.php` — 加 casts：
```php
    protected $casts = [
        'level'        => 'integer',
        'prev_level'   => 'integer',
        'child_level'  => 'integer',
        'layer'        => 'integer',
        'has_node'     => 'integer',
        'is_same_level'=> 'integer',
    ];
```

`app/Models/WalletLog.php` — 在 `TYPE_NODE_FEE_PROJECT = 141;` 后加：
```php
    // NOX Alpha 推广/团队/平级
    public const TYPE_INVITE_REWARD       = 150; // 推广收益
    public const TYPE_TEAM_REWARD         = 151; // 团队收益
    public const TYPE_SAME_LEVEL_REWARD   = 152; // 平级奖
```
在 `TYPE_MAP`、`getTypeMapUser()`、`getTypeMapUserFilter()`（USDT 分支）各加：
```php
        self::TYPE_INVITE_REWARD      => __('推广收益'),
        self::TYPE_TEAM_REWARD        => __('团队收益'),
        self::TYPE_SAME_LEVEL_REWARD  => __('平级奖'),
```

- [ ] **Step 3: 写冒烟测试**

`tests/Feature/RewardTest.php`：
```php
<?php

use App\Models\RewardRate;
use App\Models\TeamReward;
use App\Models\WalletLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('reward tables, models and constants are registered', function () {
    expect(WalletLog::TYPE_INVITE_REWARD)->toBe(150);
    expect(WalletLog::TYPE_TEAM_REWARD)->toBe(151);
    expect(WalletLog::TYPE_SAME_LEVEL_REWARD)->toBe(152);

    // 率表种子
    expect(RewardRate::count())->toBe(76);
    $v3NodeInviteL1 = RewardRate::query()
        ->where('type', 'invite')->where('rank', 3)->where('has_node', 1)->where('layer', 1)
        ->value('rate');
    expect($v3NodeInviteL1)->toBe('10.00');
    $v5TeamBaseL5 = RewardRate::query()
        ->where('type', 'team')->where('rank', 5)->where('has_node', 0)->where('layer', 5)
        ->value('rate');
    expect($v5TeamBaseL5)->toBe('2.00');
    // V1 只有 3 层，layer 4 无行
    expect(RewardRate::query()->where('type', 'invite')->where('rank', 1)->where('layer', 4)->exists())->toBeFalse();

    $t = TeamReward::create([
        'user_id' => 1, 'child_id' => 2, 'order_id' => 3,
        'level' => 3, 'prev_level' => 0, 'child_level' => 1,
        'layer' => 1, 'has_node' => 1, 'is_same_level' => 0,
        'order_am' => '100.000000', 'reward_am' => '10.000000', 'reward_rate' => '10.00',
        'sett_date' => now()->toDateString(),
    ]);
    expect(TeamReward::find($t->id)->layer)->toBe(1);
});
```

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/RewardTest.php`
Expected: PASS。

- [ ] **Step 5: 提交**

```bash
git add database/migrations/2026_09_01_000001_create_invite_rewards_table.php database/migrations/2026_09_01_000002_create_team_rewards_table.php database/migrations/2026_09_01_000003_create_reward_rates_table.php app/Models/RewardRate.php app/Models/TeamReward.php app/Models/WalletLog.php tests/Feature/RewardTest.php
git commit -m "feat: add reward rates table, invite/team reward tables and wallet types"
```

---

## Task 2: RewardService::distributeInvite（推广奖）+ 钩子

**Files:**
- Create: `app/Services/RewardService.php`
- Modify: `app/Services/AlphaOrderService.php`（payCallback 末尾）
- Test: `tests/Feature/RewardTest.php`（追加）

**Interfaces:**
- Consumes: `RewardRate`、`UserRelationClosure::getAncestorsWithDepthLimit`、`NodeOrder::STATUS_PAID`、`AlphaQuotaService::creditEarnings`、`WalletLog::TYPE_INVITE_REWARD`、`InviteReward`、`UserData`（invite_reward 累加器）。
- Produces: `RewardService::distributeInvite(AlphaOrder $order): void`。

- [ ] **Step 1: 写失败测试（追加到 RewardTest.php）**

```php
use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\NodeOrder;
use App\Models\User;
use App\Models\UserData;
use App\Services\RewardService;

function makeRewardUser(string $address, int $levelId): User
{
    $u = User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => $levelId, 'address' => $address]);
    UserData::addInfo($u->id);
    return $u;
}

test('distributeInvite pays referral rewards by layer and rate', function () {
    // V5 持节点上级(直推) + V1 下线购买
    $up = makeRewardUser('0x' . str_repeat('a', 40), 5);
    \App\Services\UserRelationService::setupUserRelations($up);
    $down = makeRewardUser('0x' . str_repeat('b', 40), 1);
    \App\Models\User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    \App\Services\UserRelationService::setupUserRelations($down);
    NodeOrder::create([
        'user_id' => $up->id, 'product_id' => 1, 'sn' => randOrderNo(),
        'amount' => '1000.000000', 'status' => NodeOrder::STATUS_PAID,
    ]);
    $product = AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id, 'product_id' => $product->id, 'sn' => randOrderNo(),
        'amount' => '1000.000000', 'reward_rate' => '17.00',
        'quota_power' => '2500.000000', 'rem_power' => '2500.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);

    RewardService::distributeInvite($order);

    // V5 持节点直推 = 1000 × 12% = 120
    expect(UserData::getInfo($up->id)->current_usdt)->toBe('120.000000');
    expect(UserData::getInfo($up->id)->invite_reward)->toBe('120.000000');
    expect(\App\Models\InviteReward::count())->toBe(1);
    $ir = \App\Models\InviteReward::first();
    expect($ir->user_id)->toBe($up->id);
    expect($ir->reward_am)->toBe('120.000000');
    expect($ir->reward_rate)->toBe('12.00');
});

test('distributeInvite is idempotent per order', function () {
    $up = makeRewardUser('0x' . str_repeat('a', 40), 5);
    $down = makeRewardUser('0x' . str_repeat('b', 40), 1);
    \App\Models\User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    \App\Services\UserRelationService::setupUserRelations($down);
    NodeOrder::create([
        'user_id' => $up->id, 'product_id' => 1, 'sn' => randOrderNo(),
        'amount' => '1000.000000', 'status' => NodeOrder::STATUS_PAID,
    ]);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id, 'product_id' => $product->id, 'sn' => randOrderNo(),
        'amount' => '1000.000000', 'reward_rate' => '17.00',
        'quota_power' => '2500.000000', 'rem_power' => '2500.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);

    RewardService::distributeInvite($order);
    RewardService::distributeInvite($order);

    expect(\App\Models\InviteReward::count())->toBe(1);
    expect(UserData::getInfo($up->id)->current_usdt)->toBe('120.000000');
});
```

> 注：`UserRelationService::setupUserRelations($user)` 在注册时建立 closure 行。测试手动建用户 + 设置 `invited_uid` + 调 setupUserRelations。若该方法签名不同，参考 `tests/Feature/NodeCommissionTest.php`（已删）或 `UserService` 的用法；实现时如遇困难，直接用 `DB::table('user_relation_closures')->insert(...)` 建 ancestor/descendant/depth 行（V1 下线 → depth1=V5 上级）。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/RewardTest.php`
Expected: FAIL（`RewardService` 不存在）。

- [ ] **Step 3: 写实现**

`app/Services/RewardService.php`：
```php
<?php

namespace App\Services;

use App\Models\AlphaOrder;
use App\Models\InviteReward;
use App\Models\NodeOrder;
use App\Models\RewardRate;
use App\Models\User;
use App\Models\UserData;
use App\Models\UserRelationClosure;
use App\Models\WalletLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardService
{
    const MAX_LAYER = 5;

    /**
     * 推广收益：下线购买资产包时，沿推荐链按层向各上级抽成
     */
    public static function distributeInvite(AlphaOrder $order)
    {
        if (InviteReward::query()->where('order_id', $order->id)->exists()) {
            return;
        }
        $downlineId = $order->user_id;
        $ancestors = UserRelationClosure::getAncestorsWithDepthLimit($downlineId, self::MAX_LAYER);
        foreach ($ancestors as $rel) {
            $layer = $rel->depth;
            $anc = User::query()->find($rel->ancestor_id);
            if (!$anc || $anc->level_id <= 0) {
                continue;
            }
            $hasNode = NodeOrder::query()->where('user_id', $anc->id)->where('status', NodeOrder::STATUS_PAID)->exists() ? 1 : 0;
            $rate = RewardRate::query()
                ->where('type', RewardRate::TYPE_INVITE)
                ->where('rank', $anc->level_id)
                ->where('has_node', $hasNode)
                ->where('layer', $layer)
                ->value('rate');
            if ($rate === null || bccomp($rate, '0', 2) <= 0) {
                continue;
            }
            $am = bcround2(bcdiv(bcmul($order->amount, $rate, 8), '100', 8), 6);
            if (bccomp($am, '0', 6) <= 0) {
                continue;
            }
            try {
                DB::transaction(function () use ($anc, $order, $downlineId, $am, $rate) {
                    AlphaQuotaService::creditEarnings($anc->id, $am, WalletLog::TYPE_INVITE_REWARD);
                    InviteReward::create([
                        'user_id'    => $anc->id,
                        'child_id'   => $downlineId,
                        'order_id'   => $order->id,
                        'order_am'   => $order->amount,
                        'reward_am'  => $am,
                        'reward_rate'=> $rate,
                    ]);
                    UserData::query()->where('user_id', $anc->id)->update([
                        'invite_reward' => DB::raw('invite_reward + ' . $am),
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('reward_invite_error', [$anc->id, $order->id, $e->getMessage()]);
            }
        }
    }
}
```

`app/Services/AlphaOrderService.php` — `payCallback` 末尾（`AlphaQuotaService::sweep($order->user_id);` 之后）加：
```php
        // 推广收益：下线购买资产包，向推荐链分发
        \App\Services\RewardService::distributeInvite($order);
```

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/RewardTest.php`
Expected: PASS。再跑全量 `php artisan test`（注意 AlphaOrderTest 的 payCallback 用例——现在 payCallback 会触发 distributeInvite，若测试用户无推荐链则无影响）。

- [ ] **Step 5: 提交**

```bash
git add app/Services/RewardService.php app/Services/AlphaOrderService.php tests/Feature/RewardTest.php
git commit -m "feat: distribute invite rewards on alpha package purchase"
```

---

## Task 3: RewardService::distributeTeam + 平级奖 + 钩子

**Files:**
- Modify: `app/Services/RewardService.php`（加 distributeTeam）
- Modify: `app/Services/AlphaRewardService.php`（settleOne 末尾）
- Test: `tests/Feature/RewardTest.php`（追加）

**Interfaces:**
- Consumes: `RewardRate::TYPE_TEAM`、`UserRelationClosure::getAncestorsWithDepthLimit`、`AlphaQuotaService::creditEarnings`、`WalletLog::TYPE_TEAM_REWARD`/`TYPE_SAME_LEVEL_REWARD`、`TeamReward`。
- Produces: `RewardService::distributeTeam(AlphaOrder $order, string $settle_date, string $static_reward): void`。

- [ ] **Step 1: 写失败测试（追加到 RewardTest.php）**

```php
use App\Models\TeamReward;
use App\Services\RewardService;

test('distributeTeam pays team + same-level rewards', function () {
    // V3 持节点上级(直推)、下线 V3（同等级）
    $up = makeRewardUser('0x' . str_repeat('c', 40), 3);
    $down = makeRewardUser('0x' . str_repeat('d', 40), 3);
    \App\Models\User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    \App\Services\UserRelationService::setupUserRelations($down);
    NodeOrder::create([
        'user_id' => $up->id, 'product_id' => 1, 'sn' => randOrderNo(),
        'amount' => '1000.000000', 'status' => NodeOrder::STATUS_PAID,
    ]);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id, 'product_id' => $product->id, 'sn' => randOrderNo(),
        'amount' => '1000.000000', 'reward_rate' => '17.00',
        'quota_power' => '2500.000000', 'rem_power' => '2500.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);
    $static = '56.66666666'; // 静态收益额

    RewardService::distributeTeam($order, now()->toDateString(), $static);

    // V3 持节点直推团队率 layer1 = 7% → 56.66666666 × 7% = 3.9666666662 → 3.966667
    $teamAm = bcround2(bcdiv(bcmul($static, '7.00', 8), '100', 8), 6);
    // 平级 10% → teamAm × 10% = 0.396667
    $sameAm = bcround2(bcdiv(bcmul($teamAm, '10', 8), '100', 8), 6);
    expect(UserData::getInfo($up->id)->current_usdt)->toBe(bcadd($teamAm, $sameAm, 6));
    expect(UserData::getInfo($up->id)->team_reward)->toBe(bcadd($teamAm, $sameAm, 6));
    $tr = TeamReward::first();
    expect($tr->user_id)->toBe($up->id);
    expect($tr->layer)->toBe(1);
    expect($tr->has_node)->toBe(1);
    expect($tr->is_same_level)->toBe(1);
    expect($tr->reward_am)->toBe($teamAm);
});

test('distributeTeam skips non-same-level ancestors for same-level bonus', function () {
    // V5 上级(直推)、下线 V1 → 不同等级 → 无平级奖，只有团队奖
    $up = makeRewardUser('0x' . str_repeat('e', 40), 5);
    $down = makeRewardUser('0x' . str_repeat('f', 40), 1);
    \App\Models\User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    \App\Services\UserRelationService::setupUserRelations($down);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id, 'product_id' => $product->id, 'sn' => randOrderNo(),
        'amount' => '1000.000000', 'reward_rate' => '17.00',
        'quota_power' => '2500.000000', 'rem_power' => '2500.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);

    RewardService::distributeTeam($order, now()->toDateString(), '56.66666666');

    $tr = TeamReward::first();
    expect($tr->is_same_level)->toBe(0);
    // V5 基础团队率 layer1 = 7% → 3.966667，无平级
    expect(UserData::getInfo($up->id)->current_usdt)->toBe('3.966667');
});
```

> 注：`TeamReward` 表需要 `sett_date` 幂等（`distributeTeam` 先查 `team_rewards(order_id, sett_date)` 存在则返回）。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/RewardTest.php`
Expected: FAIL（`distributeTeam` 不存在）。

- [ ] **Step 3: 写实现**

`app/Services/RewardService.php` — 追加 `distributeTeam`：
```php
    /**
     * 团队收益 + 平级奖：下线产生静态收益时，沿推荐链按层向各上级抽成
     */
    public static function distributeTeam(AlphaOrder $order, string $settle_date, string $static_reward)
    {
        if (TeamReward::query()->where('order_id', $order->id)->where('sett_date', $settle_date)->exists()) {
            return;
        }
        $downlineId = $order->user_id;
        $downlineLevel = User::query()->where('id', $downlineId)->value('level_id') ?: 0;
        $ancestors = UserRelationClosure::getAncestorsWithDepthLimit($downlineId, self::MAX_LAYER);
        foreach ($ancestors as $rel) {
            $layer = $rel->depth;
            $anc = User::query()->find($rel->ancestor_id);
            if (!$anc || $anc->level_id <= 0) {
                continue;
            }
            $hasNode = NodeOrder::query()->where('user_id', $anc->id)->where('status', NodeOrder::STATUS_PAID)->exists() ? 1 : 0;
            $rate = RewardRate::query()
                ->where('type', RewardRate::TYPE_TEAM)
                ->where('rank', $anc->level_id)
                ->where('has_node', $hasNode)
                ->where('layer', $layer)
                ->value('rate');
            if ($rate === null || bccomp($rate, '0', 2) <= 0) {
                continue;
            }
            $teamAm = bcround2(bcdiv(bcmul($static_reward, $rate, 8), '100', 8), 6);
            if (bccomp($teamAm, '0', 6) <= 0) {
                continue;
            }
            $isSame = ($anc->level_id == $downlineLevel) ? 1 : 0;
            $sameAm = '0.000000';
            if ($isSame) {
                $sameAm = bcround2(bcdiv(bcmul($teamAm, '10', 8), '100', 8), 6);
            }
            try {
                DB::transaction(function () use ($anc, $order, $downlineId, $downlineLevel, $settle_date, $static_reward, $layer, $hasNode, $isSame, $teamAm, $sameAm, $rate) {
                    AlphaQuotaService::creditEarnings($anc->id, $teamAm, WalletLog::TYPE_TEAM_REWARD);
                    if ($isSame && bccomp($sameAm, '0', 6) > 0) {
                        AlphaQuotaService::creditEarnings($anc->id, $sameAm, WalletLog::TYPE_SAME_LEVEL_REWARD);
                    }
                    TeamReward::create([
                        'user_id'       => $anc->id,
                        'child_id'      => $downlineId,
                        'order_id'      => $order->id,
                        'level'         => $anc->level_id,
                        'prev_level'    => 0,
                        'child_level'   => $downlineLevel,
                        'layer'         => $layer,
                        'has_node'      => $hasNode,
                        'is_same_level' => $isSame,
                        'order_am'      => $static_reward,
                        'reward_am'     => $teamAm,
                        'reward_rate'   => $rate,
                        'sett_date'     => $settle_date,
                    ]);
                    UserData::query()->where('user_id', $anc->id)->update([
                        'team_reward' => DB::raw('team_reward + ' . bcadd($teamAm, $sameAm, 6)),
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('reward_team_error', [$anc->id, $order->id, $settle_date, $e->getMessage()]);
            }
        }
    }
```
并在文件顶部加 `use App\Models\TeamReward;`。

`app/Services/AlphaRewardService.php` — `settleOne` 末尾（`AlphaOrder::query()->where('id', $order->id)->update([...STATUS_DONE...])` 之后）加：
```php
            // 团队收益 + 平级奖：下线产生静态收益，向推荐链分发
            \App\Services\RewardService::distributeTeam($order, $settle_date, $reward);
```

> 注：`$reward` 在 settleOne 里是本次结算的静态收益额（bcmath 字符串）。`distributeTeam` 的 team_rewards(order_id, sett_date) 幂等守卫 + settleOne 自身的 alpha_rewards 幂等，双重保障。

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/RewardTest.php`
Expected: PASS。再跑全量 `php artisan test`（AlphaRewardTest 的 settleOne 用例现在会触发 distributeTeam——测试用户无推荐链则无影响，有则按新逻辑；确认不回归）。

- [ ] **Step 5: 提交**

```bash
git add app/Services/RewardService.php app/Services/AlphaRewardService.php tests/Feature/RewardTest.php
git commit -m "feat: distribute team and same-level rewards on static reward settlement"
```

---

## Task 4: Admin 控制器（奖励率 + 推广/团队记录）

**Files:**
- Create: `app/Admin/Controllers/RewardRatesController.php`
- Create: `app/Admin/Controllers/InviteRewardsController.php`
- Create: `app/Admin/Controllers/TeamRewardsController.php`
- Modify: `app/Admin/routes.php`
- Test: `php artisan test` 全量 + `route:list`

**Interfaces:**
- Consumes: `RewardRate`、`InviteReward`、`TeamReward`、`User`。

- [ ] **Step 1: 写 RewardRatesController**

`app/Admin/Controllers/RewardRatesController.php`：
```php
<?php

namespace App\Admin\Controllers;

use App\Models\RewardRate;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class RewardRatesController extends AdminController
{
    protected $title = '推广奖励率';

    protected function grid()
    {
        $grid = new Grid(new RewardRate());

        $grid->column('id', 'ID')->sortable();
        $grid->column('type', '类型')->using(RewardRate::TYPE_MAP);
        $grid->column('rank', 'V等级');
        $grid->column('has_node', '节点态')->using([0 => '基础', 1 => '持节点']);
        $grid->column('layer', '推荐层');
        $grid->column('rate', '奖励率(%)');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('type', '类型')->select(RewardRate::TYPE_MAP);
            $filter->equal('rank', 'V等级');
        });

        return $grid;
    }

    protected function form()
    {
        return Form::make(new RewardRate(), function (Form $form) {
            $form->select('type', '类型')->options(RewardRate::TYPE_MAP)->required()->rules('in:invite,team');
            $form->number('rank', 'V等级')->required()->rules('integer|between:1,5');
            $form->select('has_node', '节点态')->options([0 => '基础', 1 => '持节点'])->required();
            $form->number('layer', '推荐层')->required()->rules('integer|between:1,5');
            $form->decimal('rate', '奖励率(%)')->required()->rules('required|numeric|min:0|max:100');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new RewardRate(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('type', '类型')->using(RewardRate::TYPE_MAP);
            $show->field('rank', 'V等级');
            $show->field('has_node', '节点态')->using([0 => '基础', 1 => '持节点']);
            $show->field('layer', '推荐层');
            $show->field('rate', '奖励率(%)');
        });
    }
}
```

- [ ] **Step 2: 写 InviteRewardsController**

`app/Admin/Controllers/InviteRewardsController.php`：
```php
<?php

namespace App\Admin\Controllers;

use App\Models\InviteReward;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class InviteRewardsController extends AdminController
{
    protected $title = '推广记录';

    protected function grid()
    {
        $grid = new Grid(new InviteReward());
        $grid->model()->orderByDesc('id');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '上级地址')->display(fn ($v) => $v ? substr($v, 0, 10) . '...' : '-');
        $grid->column('child_id', '下线ID');
        $grid->column('order_id', '订单ID');
        $grid->column('order_am', '订单金额')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('reward_am', '奖励金额')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('reward_rate', '比例(%)');
        $grid->column('created_at', '创建时间')->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('user_id', '上级ID');
            $filter->equal('child_id', '下线ID');
            $filter->equal('order_id', '订单ID');
        });

        $grid->disableCreateButton();
        $grid->disableEditButton();
        $grid->disableViewButton();
        $grid->disableDeleteButton();

        return $grid;
    }
}
```

- [ ] **Step 3: 写 TeamRewardsController**

`app/Admin/Controllers/TeamRewardsController.php`：
```php
<?php

namespace App\Admin\Controllers;

use App\Models\TeamReward;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class TeamRewardsController extends AdminController
{
    protected $title = '团队记录';

    protected function grid()
    {
        $grid = new Grid(new TeamReward());
        $grid->model()->orderByDesc('id');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '上级地址')->display(fn ($v) => $v ? substr($v, 0, 10) . '...' : '-');
        $grid->column('child_id', '下线ID');
        $grid->column('order_id', '订单ID');
        $grid->column('layer', '推荐层');
        $grid->column('level', '上级等级');
        $grid->column('child_level', '下线等级');
        $grid->column('has_node', '节点态')->using([0 => '基础', 1 => '持节点']);
        $grid->column('is_same_level', '平级')->using([0 => '否', 1 => '是'])->dot();
        $grid->column('order_am', '静态收益')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('reward_am', '团队奖')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('reward_rate', '率(%)');
        $grid->column('sett_date', '结算日')->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('user_id', '上级ID');
            $filter->equal('sett_date', '结算日');
            $filter->equal('is_same_level', '平级')->select([0 => '否', 1 => '是']);
        });

        $grid->disableCreateButton();
        $grid->disableEditButton();
        $grid->disableViewButton();
        $grid->disableDeleteButton();

        return $grid;
    }
}
```

> 注：`InviteReward`/`TeamReward` 需有 `user()` 关联（belongsTo User）——检查现有模型；若没有则补：
> ```php
>     public function user()
>     {
>         return $this->belongsTo(User::class, 'user_id');
>     }
> ```

- [ ] **Step 4: 注册后台路由**

`app/Admin/routes.php` — 在 `nodeFeeAllocs` resource 之后加：
```php
    // 推广/团队奖励
    $router->resource('rewardRates', 'RewardRatesController'); // 奖励率
    $router->resource('inviteRewards', 'InviteRewardsController'); // 推广记录
    $router->resource('teamRewards', 'TeamRewardsController'); // 团队记录
```

- [ ] **Step 5: 全量验证**

Run: `php artisan test`
Expected: 全部 PASS。
Run: `php artisan route:list`
Expected: `admin/rewardRates*`、`admin/inviteRewards*`、`admin/teamRewards*` 存在。

- [ ] **Step 6: 提交**

```bash
git add app/Admin/Controllers/RewardRatesController.php app/Admin/Controllers/InviteRewardsController.php app/Admin/Controllers/TeamRewardsController.php app/Admin/routes.php app/Models/InviteReward.php app/Models/TeamReward.php
git commit -m "feat: add reward rate and invite/team reward admin controllers"
```

---

## Self-Review

**1. Spec 覆盖核对**
- 推广收益（购买触发、按层抽成、率表）：Task 1/2 ✅
- 团队收益 + 平级奖（静态收益触发、同等级 10%、is_same_level）：Task 1/3 ✅
- reward_rates 表 + 种子 + 后台可配：Task 1/4 ✅
- WalletLog 150/151/152 + 用户侧 map：Task 1 ✅
- 累加列 invite_reward/team_reward：Task 2/3 ✅
- Admin 记录列表：Task 4 ✅

**2. 占位符扫描**
- 无 TBD/TODO；每个 Task 含可运行测试与实现代码。`UserRelationService::setupUserRelations` 的签名实现时核对（或直接用 closure 表 insert）。

**3. 类型/签名一致性**
- `RewardService::distributeInvite(AlphaOrder)` / `distributeTeam(AlphaOrder, string, string)` 在 Task 2/3 定义，Task 2/3 钩子调用一致 ✅
- `RewardRate::TYPE_INVITE/TYPE_TEAM` 常量在 Task 1 定义，Task 2/3/4 引用一致 ✅
- `TeamReward::layer/has_node/is_same_level` 列在 Task 1 建表，Task 3 写入、Task 4 展示一致 ✅

**4. 已知风险/注意**
- `UserRelationService::setupUserRelations` 签名未核对——Task 2 测试实现时若签名不符，改用 `DB::table('user_relation_closures')->insert(['ancestor_id'=>..., 'descendant_id'=>..., 'depth'=>1])` 直接建链。
- 既有 AlphaOrderTest/AlphaRewardTest 的 payCallback/settleOne 用例现在会触发 reward 分发——测试用户大多无推荐链（无 ancestors），分发为空不影响断言；有推荐链的按新逻辑（已断言的值不含奖励则不受影响）。
- `AlphaRewardService::settleOne` 里 `$reward` 是本次静态收益额（可能为 0 时已提前 return）——distributeTeam 用该值。
- 余额列约束：累加列（invite_reward/team_reward）是计数器非余额列，`DB::raw` 更新允许。

---

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-09-01-nox-alpha-promotion-rewards.md`. Two execution options:**

**1. Subagent-Driven（推荐）** - 每个 Task 派一个全新 subagent 执行，任务间我做两阶段评审，迭代快、上下文干净。

**2. Inline Execution** - 在当前会话内用 executing-plans 批量执行，带检查点评审。

Which approach?
