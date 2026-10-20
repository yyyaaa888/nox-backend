# NOX Alpha 子项目 3（等级 V1–V5 + 周提现上限 + DTP→NOX 全面改名）实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 在 nox-backend 上落地 NOX Alpha 子项目 3：① 代币 DTP→NOX 全面改名（前提）；② 等级体系重构为 V1–V5（5 级，admin 可配，`total_perf` 阈值 + 每币种周提现上限）；③ 按等级 + 按币种（USDT/NOX）的自然周提现上限（无等级按 V1），与现有每日限额并存。

**Architecture:** 复用既有 `UserUpgradeService`（升级判定不变，仅阈值表改 V1–V5）、`WithdrawController` 每日限额模式（新增周限额检查 `checkWithdrawWeeklyLimit`）、`Level`/`LevelHistory`。改名是跨切面常量/列/键/数据迁移；周上限列放 `levels` 表。

**Tech Stack:** Laravel 12 / PHP 8.2 / MySQL（测试 sqlite :memory:）/ Redis / dcat-admin / Pest / bcmath。

**Spec:** `docs/superpowers/specs/2026-09-01-nox-alpha-levels-design.md`

## Global Constraints

- PHP >= 8.2；所有金额一律 **bcmath 字符串运算**（`bcadd`/`bcsub`/`bcmul`/`bccomp`，账本 scale 6，中间 scale 8，`bcround2`/`bcfloor2` 必须显式传 scale 6），禁止 float/double 处理金额。
- 所有可用余额变动必须走 `WalletLog::addLog()`（写 `asset_logs` + 更新 `user_datas`），禁止直接 `DB::raw` 改 `user_datas` 余额列。额度槽列由 `AlphaQuotaService` 专管。
- 订单 `sn` 与 `pay_orders.sn` 一致；UI/提示文案走 `__('...')`（中文）。
- **改名纪律**：`WalletLog::SYMBOL_DTP` → `SYMBOL_NOX`（值 'NOX'）、`Asset::CODE_DTP` → `CODE_NOX`（值 'NOX'）——所有代码引用、config 键、列名、展示文案、测试中的 `DTP` 一律改为 `NOX`（sql/nox.sql 是参考数据，不强制改）。
- 遵循现有代码风格：Service 多为静态方法；Admin 控制器继承 `AdminController`；API 控制器用 `Validator` + `apiError`/`apiSuccess`。

---

## 文件结构

**新增**
- `database/migrations/2026_09_01_000001_rename_dtp_to_nox_columns.php`（user_datas 列改名）
- `database/migrations/2026_09_01_000002_rename_dtp_to_nox_data.php`（assets/asset_logs/withdraws/configs 数据改名）
- `database/migrations/2026_09_01_000003_reconfigure_levels_to_v1_v5.php`（weekly_limit 列 + V1–V5 重构）
- 测试：`tests/Feature/RenameTokenTest.php`、`tests/Feature/LevelWeeklyLimitTest.php`（追加）

**修改**
- `app/Models/WalletLog.php`（SYMBOL_DTP → SYMBOL_NOX + SYMBOL_MAP + 内部引用）
- `app/Models/Asset.php`（CODE_DTP → CODE_NOX + CODE_MAP + 内部引用）
- `app/Models/UserData.php`（casts 键 + changeDtpBalance → changeNoxBalance）
- `app/Models/Withdraw.php`（getWeekUserTotalWithdrawAmount）
- `app/Models/Level.php`（weekly_limit 列 casts）
- `app/Http/Controllers/Api/WithdrawController.php`（符号分支 + config 键 + checkWithdrawWeeklyLimit）
- `app/Http/Controllers/Api/RechargeController.php`（config 键）
- `app/Http/Controllers/Api/MyAssetController.php`（config 键 + 余额列）
- `app/Admin/Controllers/ConfigController.php`（表单键/文案 DTP→NOX）
- `app/Admin/Controllers/Team/LevelsController.php`（weekly_limit 字段）
- 测试：`tests/Pest.php`、`tests/Feature/NodeFeeTest.php`、`tests/Feature/AlphaOrderTest.php`（DTP→NOX 同步）

---

## Task 1: DTP→NOX 改名（常量 + user_datas 列 + UserData 模型）

**Files:**
- Modify: `app/Models/WalletLog.php`
- Modify: `app/Models/Asset.php`
- Modify: `app/Models/UserData.php`
- Create: `database/migrations/2026_09_01_000001_rename_dtp_to_nox_columns.php`
- Test: `tests/Feature/RenameTokenTest.php`

**Interfaces:**
- Produces: `WalletLog::SYMBOL_NOX = 'NOX'`（SYMBOL_MAP 同步）；`Asset::CODE_NOX = 'NOX'`（CODE_MAP 同步）；`UserData` 的 `current_nox`/`freeze_nox`/`total_nox` 列（decimal:6 cast）+ `changeNoxBalance()`；`user_datas` 表三列改名（数据保留）。

- [ ] **Step 1: 写失败测试**

`tests/Feature/RenameTokenTest.php`：
```php
<?php

use App\Models\Asset;
use App\Models\UserData;
use App\Models\WalletLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('token constants are renamed to NOX', function () {
    expect(WalletLog::SYMBOL_NOX)->toBe('NOX');
    expect(WalletLog::SYMBOL_MAP[WalletLog::SYMBOL_NOX])->toBe('NOX');
    expect(Asset::CODE_NOX)->toBe('NOX');
    expect(Asset::CODE_MAP[Asset::CODE_NOX])->toBe('NOX');
});

test('user_datas columns are renamed to nox', function () {
    expect(Schema::hasColumn('user_datas', 'current_nox'))->toBeTrue();
    expect(Schema::hasColumn('user_datas', 'freeze_nox'))->toBeTrue();
    expect(Schema::hasColumn('user_datas', 'total_nox'))->toBeTrue();
    expect(Schema::hasColumn('user_datas', 'current_dtp'))->toBeFalse();
    expect(Schema::hasColumn('user_datas', 'freeze_dtp'))->toBeFalse();
    expect(Schema::hasColumn('user_datas', 'total_dtp'))->toBeFalse();
});

test('changeNoxBalance updates the nox balance', function () {
    $user = \App\Models\User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    $data = UserData::getInfo($user->id);

    UserData::changeNoxBalance($user->id, '100.000000', WalletLog::CHANGE_TYPE_IN);

    expect(UserData::getInfo($user->id)->current_nox)->toBe('100.000000');
});
```

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/RenameTokenTest.php`
Expected: FAIL（`SYMBOL_NOX` 未定义、`current_nox` 列不存在、`changeNoxBalance` 不存在）。

- [ ] **Step 3: 写实现**

`app/Models/WalletLog.php`：
```php
    public const SYMBOL_USDT = 'USDT'; // USDT
    public const SYMBOL_NOX  = 'NOX';  // NOX（原 DTP 改名）
```
并把 `SYMBOL_MAP`、方法内所有 `self::SYMBOL_DTP` 引用（约 190/240/282 行）改为 `self::SYMBOL_NOX`。删除 `SYMBOL_DTP` 常量。

`app/Models/Asset.php`：
```php
    public const CODE_USDT = 'USDT';
    public const CODE_NOX  = 'NOX';  // 原 CODE_DTP
```
并把 `CODE_MAP`、方法内 `Asset::CODE_DTP` 引用（约 128 行）改为 `Asset::CODE_NOX`。删除 `CODE_DTP` 常量。

`app/Models/UserData.php`：
- casts（约 88-90 行）：
```php
        'total_nox' => 'decimal:6',
        'current_nox' => 'decimal:6',
        'freeze_nox' => 'decimal:6',
```
- `changeDtpBalance` → `changeNoxBalance`（方法体里 `current_dtp`/`freeze_dtp`/`total_dtp` 列引用 → `_nox`）。确认 `WalletLog::addLog` 里调用的是 `changeNoxBalance`（同步改）。@property 注释同步。

`database/migrations/2026_09_01_000001_rename_dtp_to_nox_columns.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_datas', function (Blueprint $table) {
            $table->renameColumn('total_dtp', 'total_nox');
            $table->renameColumn('current_dtp', 'current_nox');
            $table->renameColumn('freeze_dtp', 'freeze_nox');
        });
    }
    public function down(): void
    {
        Schema::table('user_datas', function (Blueprint $table) {
            $table->renameColumn('total_nox', 'total_dtp');
            $table->renameColumn('current_nox', 'current_dtp');
            $table->renameColumn('freeze_nox', 'freeze_dtp');
        });
    }
};
```

> 注：`changeNoxBalance` 方法与 `UserData::addInfo` 默认值列——`addInfo` 若不显式设 nox 列则用迁移默认值，无需改（新增用户 nox 列默认 0）。运行测试后若 `changeNoxBalance` 影响 `WalletLog::addLog` 的 DTP 分支，一并改。

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/RenameTokenTest.php`
Expected: PASS。

- [ ] **Step 5: 提交**

```bash
git add app/Models/WalletLog.php app/Models/Asset.php app/Models/UserData.php database/migrations/2026_09_01_000001_rename_dtp_to_nox_columns.php tests/Feature/RenameTokenTest.php
git commit -m "refactor: rename DTP token to NOX (constants, user_datas columns, UserData)"
```

---

## Task 2: DTP→NOX 改名（业务代码 + config 键 + 数据迁移 + 展示 + 测试）

**Files:**
- Modify: `app/Http/Controllers/Api/WithdrawController.php`
- Modify: `app/Http/Controllers/Api/RechargeController.php`
- Modify: `app/Http/Controllers/Api/MyAssetController.php`
- Modify: `app/Admin/Controllers/ConfigController.php`
- Modify: `app/Models/Withdraw.php`（如引用 `SYMBOL_DTP`）
- Create: `database/migrations/2026_09_01_000002_rename_dtp_to_nox_data.php`
- Test: `tests/Feature/RenameTokenTest.php`（追加数据迁移用例）
- Modify: `tests/Pest.php`、`tests/Feature/NodeFeeTest.php`、`tests/Feature/AlphaOrderTest.php`（DTP→NOX 同步）

**Interfaces:**
- Consumes: Task 1 的 `SYMBOL_NOX`/`CODE_NOX`/nox 列。
- Produces: 业务代码/config 键全部用 NOX；`assets`/`asset_logs`/`withdraws`/`configs` 数据改名完成。

- [ ] **Step 1: 写失败测试（追加到 RenameTokenTest.php）**

```php
use Illuminate\Support\Facades\DB;

test('data migration renames assets, ledger symbols and config keys', function () {
    DB::table('assets')->insert([
        'id' => 7, 'chain_id' => 3, 'name' => 'DTP', 'title' => 'DTP正式币', 'code' => 'DTP',
        'decimals' => 18, 'type' => 2, 'status' => 1, 'is_exchange' => 1,
        'exchange_rate' => '0.10000000', 'contract_abi' => null,
        'contract_address' => null, 'withdraw_address' => null, 'withdraw_key' => null,
        'wallet_address' => null, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('asset_logs')->insert([
        'user_id' => 1, 'symbol' => 'DTP', 'amount' => '10.000000',
        'before_am' => '0.000000', 'after_am' => '10.000000',
        'change_type' => 1, 'type' => 130, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('withdraws')->insert([
        'user_id' => 1, 'sn' => randOrderNo(), 'symbol' => 'DTP', 'amount' => '10.000000',
        'status' => 1, 'withdraw_mode' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('configs')->insertOrIgnore([
        'group' => 'withdraw', 'key' => 'site_w_min_dtp', 'value' => '10',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    expect(DB::table('assets')->where('code', 'DTP')->count())->toBe(0);
    expect(DB::table('assets')->where('code', 'NOX')->count())->toBe(1);
    expect(DB::table('asset_logs')->where('symbol', 'DTP')->count())->toBe(0);
    expect(DB::table('asset_logs')->where('symbol', 'NOX')->count())->toBe(1);
    expect(DB::table('withdraws')->where('symbol', 'DTP')->count())->toBe(0);
    expect(DB::table('withdraws')->where('symbol', 'NOX')->count())->toBe(1);
    expect(DB::table('configs')->where('key', 'site_w_min_dtp')->count())->toBe(0);
    expect(DB::table('configs')->where('key', 'site_w_min_nox')->count())->toBe(1);
});
```

> 注：`asset_logs`/`withdraws`/`assets`/`configs` 需先有表（子项目 1/2 迁移已建）。若 `asset_logs` 有必填列导致 insert 失败，参考 `sql/nox.sql` 的列补齐字段。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/RenameTokenTest.php`
Expected: FAIL（数据未改名）。

- [ ] **Step 3: 写实现**

`database/migrations/2026_09_01_000002_rename_dtp_to_nox_data.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // assets：DTP 正式币/tDTP 测试币 → NOX/tNOX
        DB::table('assets')->where('code', 'DTP')->update(['code' => 'NOX', 'name' => 'NOX', 'title' => 'NOX正式币']);
        DB::table('assets')->where('code', 'tDTP')->update(['code' => 'tNOX', 'name' => 'tNOX', 'title' => 'NOX测试币']);
        // asset_logs / withdraws：symbol
        DB::table('asset_logs')->where('symbol', 'DTP')->update(['symbol' => 'NOX']);
        DB::table('withdraws')->where('symbol', 'DTP')->update(['symbol' => 'NOX']);
        // configs：site_*_dtp → site_*_nox
        foreach ([
            'site_allow_recharge_dtp' => 'site_allow_recharge_nox',
            'site_allow_w_dtp'         => 'site_allow_w_nox',
            'site_w_min_dtp'           => 'site_w_min_nox',
            'site_w_fee_rate_dtp'      => 'site_w_fee_rate_nox',
            'site_w_dtp_global_limit'  => 'site_w_nox_global_limit',
            'site_w_dtp_user_limit'    => 'site_w_nox_user_limit',
        ] as $old => $new) {
            DB::table('configs')->where('key', $old)->update(['key' => $new]);
        }
    }
    public function down(): void
    {
        // 反向改名（不强制精确，用于回滚）
        DB::table('assets')->where('code', 'NOX')->update(['code' => 'DTP', 'name' => 'DTP', 'title' => 'DTP正式币']);
        DB::table('assets')->where('code', 'tNOX')->update(['code' => 'tDTP', 'name' => 'tDTP', 'title' => 'DTP测试币']);
        DB::table('asset_logs')->where('symbol', 'NOX')->update(['symbol' => 'DTP']);
        DB::table('withdraws')->where('symbol', 'NOX')->update(['symbol' => 'DTP']);
        foreach ([
            'site_allow_recharge_nox' => 'site_allow_recharge_dtp',
            'site_allow_w_nox'         => 'site_allow_w_dtp',
            'site_w_min_nox'           => 'site_w_min_dtp',
            'site_w_fee_rate_nox'      => 'site_w_fee_rate_dtp',
            'site_w_nox_global_limit'  => 'site_w_dtp_global_limit',
            'site_w_nox_user_limit'    => 'site_w_dtp_user_limit',
        ] as $new => $old) {
            DB::table('configs')->where('key', $new)->update(['key' => $old]);
        }
    }
};
```

`app/Http/Controllers/Api/WithdrawController.php` — 所有 `WalletLog::SYMBOL_DTP` → `WalletLog::SYMBOL_NOX`；`site_w_*_dtp` → `site_w_*_nox`；`site_w_dtp_*` → `site_w_nox_*`；注释/文案 DTP→NOX。`$userData->current_dtp` → `$userData->current_nox`（若读取 NOX 余额）。

`app/Http/Controllers/Api/RechargeController.php` — `site_allow_recharge_dtp` → `site_allow_recharge_nox`；`WalletLog::SYMBOL_DTP` → `SYMBOL_NOX`。

`app/Http/Controllers/Api/MyAssetController.php` — `site_w_*_dtp` → `site_w_*_nox`；`$userData->current_dtp` → `current_nox`。

`app/Admin/Controllers/ConfigController.php` — 表单键 `site_*_dtp` → `site_*_nox`，文案 `DTP` → `NOX`。

`tests/Pest.php`、`tests/Feature/NodeFeeTest.php`、`tests/Feature/AlphaOrderTest.php` — 所有 `'DTP'`/`SYMBOL_DTP` → `'NOX'`/`SYMBOL_NOX`。

> 用 grep 兜底：`grep -rn "DTP\|dtp" app/ tests/ routes/ config/ --include="*.php"` 应无输出（除注释里可保留历史说明）。

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/RenameTokenTest.php`
Expected: PASS（含数据迁移用例）。再跑 `php artisan test` 全量（改名可能影响子项目 1/2 测试——NodeFeeTest 的 DTP 用例改为 NOX 后应仍通过）。

- [ ] **Step 5: 提交**

```bash
git add database/migrations/2026_09_01_000002_rename_dtp_to_nox_data.php app/Http/Controllers/Api/WithdrawController.php app/Http/Controllers/Api/RechargeController.php app/Http/Controllers/Api/MyAssetController.php app/Admin/Controllers/ConfigController.php app/Models/Withdraw.php tests/Feature/RenameTokenTest.php tests/Pest.php tests/Feature/NodeFeeTest.php tests/Feature/AlphaOrderTest.php
git commit -m "refactor: rename DTP to NOX across business code, config keys, data and tests"
```

---

## Task 3: 等级 V1–V5 重构 + weekly_limit 列

**Files:**
- Create: `database/migrations/2026_09_01_000003_reconfigure_levels_to_v1_v5.php`
- Modify: `app/Models/Level.php`
- Test: `tests/Feature/LevelWeeklyLimitTest.php`

**Interfaces:**
- Consumes: Task 2 的 NOX 命名。
- Produces: `levels` 表 V1–V5（id 1-5，`total_perf` 3000/5000/10000/50000/100000，`reward_rate` 3/5/7/9/11，`weekly_limit_usdt`/`weekly_limit_nox` 5000/5000…300000/300000）；T6–T10 删除；`users.level_id > 5` → 5。`Level` 模型 `weekly_limit_*` decimal:6 cast。

- [ ] **Step 1: 写失败测试**

`tests/Feature/LevelWeeklyLimitTest.php`：
```php
<?php

use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('levels are reconfigured to V1-V5 with weekly limits', function () {
    expect(Level::count())->toBe(5);
    $v1 = Level::query()->where('code', 'V1')->first();
    expect($v1->total_perf)->toBe(3000);
    expect($v1->weekly_limit_usdt)->toBe('5000.000000');
    expect($v1->weekly_limit_nox)->toBe('5000.000000');
    $v5 = Level::query()->where('code', 'V5')->first();
    expect($v5->total_perf)->toBe(100000);
    expect($v5->weekly_limit_usdt)->toBe('300000.000000');
    expect(Level::query()->where('code', 'T10')->exists())->toBeFalse();
});

test('users with legacy level above V5 are remapped to V5', function () {
    User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => 10]);

    expect(User::query()->where('level_id', 10)->count())->toBe(0);
    expect(User::query()->where('level_id', 5)->count())->toBe(1);
});
```

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/LevelWeeklyLimitTest.php`
Expected: FAIL（levels 仍是 T1–T10、无 weekly_limit 列）。

- [ ] **Step 3: 写实现**

`database/migrations/2026_09_01_000003_reconfigure_levels_to_v1_v5.php`：
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
        Schema::table('levels', function (Blueprint $table) {
            $table->decimal('weekly_limit_usdt', 20, 6)->unsigned()->default(0)->comment('周USDT提现上限');
            $table->decimal('weekly_limit_nox', 20, 6)->unsigned()->default(0)->comment('周NOX提现上限');
        });

        $config = [
            1 => [3000, '5000.000000', '5000.000000'],
            2 => [5000, '10000.000000', '10000.000000'],
            3 => [10000, '30000.000000', '30000.000000'],
            4 => [50000, '100000.000000', '100000.000000'],
            5 => [100000, '300000.000000', '300000.000000'],
        ];
        foreach ($config as $id => [$perf, $usdt, $nox]) {
            DB::table('levels')->where('id', $id)->update([
                'name' => 'V' . $id,
                'code' => 'V' . $id,
                'total_perf' => $perf,
                'weekly_limit_usdt' => $usdt,
                'weekly_limit_nox' => $nox,
            ]);
        }
        // 删除 T6-T10
        DB::table('levels')->where('id', '>', 5)->delete();
        // 存量用户 level_id > 5 → 5
        DB::table('users')->where('level_id', '>', 5)->update(['level_id' => 5]);
    }
    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropColumn(['weekly_limit_usdt', 'weekly_limit_nox']);
        });
    }
};
```

`app/Models/Level.php` — 加 casts：
```php
    protected $casts = [
        'weekly_limit_usdt' => 'decimal:6',
        'weekly_limit_nox'  => 'decimal:6',
    ];
```
（`total_perf`/`dept_num`/`dept_perf`/`reward_rate` 是 int 列，无需 cast。）

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/LevelWeeklyLimitTest.php`
Expected: PASS。

- [ ] **Step 5: 提交**

```bash
git add database/migrations/2026_09_01_000003_reconfigure_levels_to_v1_v5.php app/Models/Level.php tests/Feature/LevelWeeklyLimitTest.php
git commit -m "feat: reconfigure levels to V1-V5 with weekly withdrawal limits"
```

---

## Task 4: 周提现上限（Withdraw helper + checkWithdrawWeeklyLimit）

**Files:**
- Modify: `app/Models/Withdraw.php`（getWeekUserTotalWithdrawAmount）
- Modify: `app/Http/Controllers/Api/WithdrawController.php`（checkWithdrawWeeklyLimit + 调用）
- Test: `tests/Feature/LevelWeeklyLimitTest.php`（追加）

**Interfaces:**
- Consumes: `Level::weekly_limit_usdt/weekly_limit_nox`、`User::level_id`、`WalletLog::SYMBOL_USDT/NOX`。
- Produces: `Withdraw::getWeekUserTotalWithdrawAmount(int $userId, string $symbol, string $weekStart): string`；`WithdrawController::checkWithdrawWeeklyLimit(int $user_id, string $amount, string $symbol): void`。

- [ ] **Step 1: 写失败测试（追加到 LevelWeeklyLimitTest.php）**

```php
use App\Models\Level;
use App\Models\Withdraw;
use App\Services\WithdrawOrderService;
use App\Models\UserData;
use App\Models\WalletLog;

test('weekly limit rejects over-limit withdrawal', function () {
    $user = \App\Models\User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => 1]); // V1
    UserData::addInfo($user->id);
    WalletLog::addLog($user->id, '10000.000000', WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);
    // 本周已提 4000（V1 周上限 5000）
    $o = WithdrawOrderService::createOrder($user, [
        'symbol' => WalletLog::SYMBOL_USDT, 'amount' => '4000.000000',
        'real_am' => '4000.000000', 'fee' => '0.000000', 'fee_rate' => '0.00',
        'withdraw_mode' => Withdraw::WITHDRAW_MODE_CONTRACT,
    ], ['sign' => '0x' . str_repeat('f', 64), 'message' => 'm', 'project_signer' => '0x' . str_repeat('e', 40), 'withdraw_contract' => '0x' . str_repeat('d', 40), 'deadline' => now()->addMinutes(5)]);
    $o->status = Withdraw::STATUS_DONE;
    $o->save();

    $controller = new \App\Http\Controllers\Api\WithdrawController();
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('checkWithdrawWeeklyLimit');
    $m->setAccessible(true);

    // 再提 2000 → 累计 6000 > 5000，拒绝
    $this->expectException(\App\Exceptions\LogicException::class);
    $m->invoke($controller, $user->id, '2000.000000', WalletLog::SYMBOL_USDT);
});

test('weekly limit resets on monday and counts per symbol', function () {
    $user = \App\Models\User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => 1]);
    $controller = new \App\Http\Controllers\Api\WithdrawController();
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('checkWithdrawWeeklyLimit');
    $m->setAccessible(true);

    // 上周的提现不计入
    $lastWeek = now()->subDays(8);
    DB::table('withdraws')->insert([
        'user_id' => $user->id, 'sn' => randOrderNo(), 'symbol' => WalletLog::SYMBOL_USDT,
        'amount' => '5000.000000', 'status' => Withdraw::STATUS_DONE, 'withdraw_mode' => 1,
        'created_at' => $lastWeek, 'updated_at' => $lastWeek,
    ]);
    // 本周 NOX 已提 6000（USDT 周上限不受影响）
    DB::table('withdraws')->insert([
        'user_id' => $user->id, 'sn' => randOrderNo(), 'symbol' => WalletLog::SYMBOL_NOX,
        'amount' => '6000.000000', 'status' => Withdraw::STATUS_DONE, 'withdraw_mode' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // USDT 再提 4000 不超（上周 5000 不算 + NOX 不算）
    $m->invoke($controller, $user->id, '4000.000000', WalletLog::SYMBOL_USDT);
    $this->assertTrue(true);
});
```

> 注：`WithdrawOrderService::createOrder` 已存在（子项目 2），冻结余额需要用户有足够 USDT（测试先 `WalletLog::addLog` 充值）。`checkWithdrawWeeklyLimit` 是私有方法，用反射调用。`withdraws` 表（子项目 2 已建）必填列参考 `sql/nox.sql`。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/LevelWeeklyLimitTest.php`
Expected: FAIL（`checkWithdrawWeeklyLimit` 不存在）。

- [ ] **Step 3: 写实现**

`app/Models/Withdraw.php` — 加方法：
```php
    /**
     * 用户某周累计提现（自然周，周一 00:00 起；status 排除驳回/取消）
     */
    public static function getWeekUserTotalWithdrawAmount(int $userId, string $symbol, string $weekStart)
    {
        return self::query()
            ->where('user_id', $userId)
            ->where('symbol', $symbol)
            ->where('created_at', '>=', $weekStart . ' 00:00:00')
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DONE, self::STATUS_FAIL])
            ->sum('amount');
    }
```

`app/Http/Controllers/Api/WithdrawController.php` — 加私有方法 + 在 `create` 里 `checkWithdrawUserLimit` 之后调用：
```php
    private function checkWithdrawWeeklyLimit(int $user_id, string $amount, string $symbol)
    {
        if ($symbol != WalletLog::SYMBOL_USDT && $symbol != WalletLog::SYMBOL_NOX) {
            throw new LogicException(__('资产类型不正确'));
        }
        // 无等级按 V1
        $level_id = User::query()->where('id', $user_id)->value('level_id') ?: 1;
        $level = Level::query()->find($level_id);
        $weekly_limit = $symbol == WalletLog::SYMBOL_USDT ? $level->weekly_limit_usdt : $level->weekly_limit_nox;
        if ($weekly_limit === null) {
            $weekly_limit = '0.000000';
        }

        $weekStart = Carbon::now()->startOfWeek()->toDateString(); // 周一
        $total_withdraw = Withdraw::getWeekUserTotalWithdrawAmount($user_id, $symbol, $weekStart);

        $remaining = bcsub($weekly_limit, $total_withdraw, 2);
        if (bccomp($remaining, '0', 6) <= 0) {
            throw new LogicException(__('本周提现金额超限，剩余可提现金额为 :amount', ['amount' => 0]));
        }
        if (bccomp($remaining, $amount, 6) < 0) {
            throw new LogicException(__('本周提现金额超限，剩余可提现金额为 :amount', ['amount' => $remaining]));
        }
    }
```
在 `create` 的 `$this->checkWithdrawGlobalLimit(...)`/`$this->checkWithdrawUserLimit(...)` 之后（约 167-168 行）加：
```php
            $this->checkWithdrawWeeklyLimit($user_id, $amount, $symbol);
```
确保文件顶部 `use App\Models\Level;` 已导入（没有则加）。

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/LevelWeeklyLimitTest.php`
Expected: PASS。再跑 `php artisan test` 全量。

- [ ] **Step 5: 提交**

```bash
git add app/Models/Withdraw.php app/Http/Controllers/Api/WithdrawController.php tests/Feature/LevelWeeklyLimitTest.php
git commit -m "feat: add level-based weekly withdrawal limit"
```

---

## Task 5: Admin LevelsController 周上限字段 + 全量回归

**Files:**
- Modify: `app/Admin/Controllers/Team/LevelsController.php`
- Test: `php artisan test` 全量回归

**Interfaces:**
- Consumes: `Level::weekly_limit_usdt/weekly_limit_nox`。

- [ ] **Step 1: 写实现**

`app/Admin/Controllers/Team/LevelsController.php` — grid 加两列、form 加两个输入：

grid（在 `reward_rate` 列后加）：
```php
            $grid->column('weekly_limit_usdt', '周USDT上限')->display(fn ($v) => removeTrailingZeros($v));
            $grid->column('weekly_limit_nox', '周NOX上限')->display(fn ($v) => removeTrailingZeros($v));
```
form（在 `reward_rate` 后加）：
```php
            $form->decimal('weekly_limit_usdt', '周USDT提现上限')->rules('required|numeric|min:0');
            $form->decimal('weekly_limit_nox', '周NOX提现上限')->rules('required|numeric|min:0');
```

- [ ] **Step 2: 全量验证**

Run: `php artisan test`
Expected: 全部 PASS（子项目 1/2/3 用例，45 + 新增）。
Run: `php artisan route:list`
Expected: 无异常（`admin/levels*` 仍存在）。

- [ ] **Step 3: 提交**

```bash
git add app/Admin/Controllers/Team/LevelsController.php
git commit -m "feat: add weekly withdrawal limit fields to levels admin"
```

---

## Self-Review

**1. Spec 覆盖核对**
- DTP→NOX 改名（常量/列/键/数据/展示/测试）：Task 1/2 ✅
- 等级 V1–V5 重构 + weekly_limit 列：Task 3 ✅
- 周提现上限（自然周、按币种、无等级按 V1、与每日限额并存）：Task 4 ✅
- Admin LevelsController 周上限字段：Task 5 ✅

**2. 占位符扫描**
- 无 TBD/TODO；每个 Task 含可运行测试与实现代码。

**3. 类型/签名一致性**
- `WalletLog::SYMBOL_NOX`/`Asset::CODE_NOX` 在 Task 1 定义，Task 2/4 引用一致 ✅
- `Withdraw::getWeekUserTotalWithdrawAmount(int, string, string): string` 在 Task 4 定义/使用一致 ✅
- `Level::weekly_limit_usdt/weekly_limit_nox` 在 Task 3 定义（cast decimal:6），Task 4/5 使用一致 ✅

**4. 已知风险/注意**
- `user_datas` 列改名后，子项目 1 的 `UserData` 18 列 `decimal:6` cast 需同步（Task 1 已含 casts 键改名）。
- `changeDtpBalance` → `changeNoxBalance` 方法改名需同步 `WalletLog::addLog` 的调用点。
- 改名后 `grep DTP app/ tests/ routes/ config/` 应无输出（除历史注释）；sql/nox.sql 参考数据不强制改。
- `WithdrawController` 提现 NOX 分支：现有 `site_w_fee_rate_dtp` 等 config 键改名后，若 configs 表无对应新键，`getGlobalConfig('site_w_fee_rate_nox', '0')` 用默认 0——运营需在后台配置 NOX 费率。
- `LevelsController` 当前 grid 用 `display(fn) => removeTrailingZeros(...)`；新列沿用。
- NodeFeeTest 的 F2 用例（DTP 手续费不进池）改名后为 NOX——断言逻辑不变。

---

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-09-01-nox-alpha-levels.md`. Two execution options:**

**1. Subagent-Driven（推荐）** - 每个 Task 派一个全新 subagent 执行，任务间我做两阶段评审，迭代快、上下文干净。

**2. Inline Execution** - 在当前会话内用 executing-plans 批量执行，带检查点评审。

Which approach?
