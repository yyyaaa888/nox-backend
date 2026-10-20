# NOX Alpha 子项目 1（算力资产包 + 额度槽）实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 在 nox-backend 上落地 NOX Alpha 的算力资产包：产品/订单/每日静态产出（订单级 2.5× 封顶自动完成）、用户级 2.5× 额度槽（超槽冻结、复投解冻）、首月试用退 80%，并删除旧质押/DAO 节点业务。

**Architecture:** 新增 `alpha_products` / `alpha_orders` / `alpha_rewards` 三张表与对应模型/服务，复用现有已验证的每日结算模式（幂等 + chunkById + Cache::lock）。额度槽作为统一记账入口 `AlphaQuotaService::creditEarnings`，静态产出与后续子项目的动态收益都走它。申购走现有 `pay_orders` + `app:order-pay` 链上对账直付，内部余额不扣减。旧的质押/节点代码、表、路由、后台、seeders、测试全部删除，并修复保留代码里对旧模型的引用。

**Tech Stack:** Laravel 12 / PHP 8.2 / MySQL（测试 sqlite :memory:）/ Redis / dcat-admin / Pest / bcmath。

**Spec:** `docs/superpowers/specs/2026-08-31-nox-alpha-power-package-design.md`

## Global Constraints

- PHP >= 8.2；所有金额一律 **bcmath 字符串运算**（`bcadd`/`bcsub`/`bcmul`/`bccomp`，账本 scale 6，中间计算 scale 8），禁止 float/double 处理金额。
- 所有可用余额变动必须走 `WalletLog::addLog()`（写 `asset_logs` 账本 + 更新 `user_datas` 余额），禁止直接 `DB::raw` 改 `user_datas` 余额列。额度槽冻结列（`alpha_frozen`）是余额外的桶，由 `AlphaQuotaService` 专管。
- 静态日产出额 = `amount × reward_rate ÷ 3000`（= 本金 × 月产率% ÷ 30），受订单 `rem_power` 封顶。
- 用户级额度槽 `S = alpha_quota_multiple × Σ(进行中订单 amount)`，`alpha_quota_multiple` 读 `getGlobalConfig('alpha_quota_multiple', '2.5')`。
- 订单 `sn` 与对应 `pay_orders.sn` 必须一致（`randOrderNo()` 生成，19 位数字）。
- UI/提示文案走 `__('...')`（中文），语言由 `language` 中间件处理。
- 遵循现有代码风格：Service 多为静态方法；Admin 控制器继承 `AdminController`（grid()/form() 模式）；API 控制器用 `Validator` + `apiError`/`apiSuccess`。

---

## 文件结构

**新增**
- `database/migrations/2026_08_31_000001_create_alpha_products_table.php`
- `database/migrations/2026_08_31_000002_create_alpha_orders_table.php`
- `database/migrations/2026_08_31_000003_create_alpha_rewards_table.php`
- `database/migrations/2026_08_31_000004_add_alpha_quota_columns_to_user_datas_table.php`
- `database/migrations/2026_08_31_000005_insert_alpha_quota_config.php`（configs 种子：`alpha_quota_multiple = 2.5`）
- `database/migrations/2026_08_31_000006_drop_legacy_stake_node_tables.php`
- `app/Models/AlphaProduct.php`
- `app/Models/AlphaOrder.php`
- `app/Models/AlphaReward.php`
- `app/Services/AlphaQuotaService.php`
- `app/Services/AlphaOrderService.php`
- `app/Services/AlphaRewardService.php`
- `app/Console/Commands/AlphaRewardCmd.php`
- `app/Http/Controllers/Api/PowerController.php`
- `app/Admin/Controllers/AlphaProductsController.php`
- `app/Admin/Controllers/AlphaOrdersController.php`
- 测试：`tests/Feature/AlphaOrderTest.php`、`tests/Feature/AlphaRewardTest.php`、`tests/Feature/AlphaExitTest.php`

**修改**
- `app/Models/WalletLog.php`（新增 3 个 TYPE 常量 + TYPE_MAP）
- `app/Models/PayOrder.php`（新增 `TYPE_ALPHA_BUY` + TYPE_MAP）
- `app/Console/Commands/OrderPay.php`（completeOrder/failOrder/timeoutOrder 增加资产包分支）
- `routes/api.php`（删 node/stake 路由，加 power 路由）
- `routes/console.php`（`app:stake-reward` → `app:alpha-reward`；删 `app:fee-sett`）
- `app/Admin/routes.php`（删旧 resource，加 alphaProducts/alphaOrders）
- `app/Models/InviteReward.php`（删 `order()` 关联）
- `app/Models/FeeSett.php`（删 `node()` 关联）
- `app/Http/Controllers/Api/MyTeamController.php`（删 Node/NodeCommission 展示）
- `app/Admin/Controllers/Team/UserTreesController.php`（删节点名展示）
- `app/Admin/Controllers/UserDatasController.php`（删节点列/过滤器）

**删除**
- 模型：`StakeOrder` `StakeReward` `StakeSpeeds` `Machine` `MachineAttr` `MachineProduct` `MachineProductAttr` `MachineRate` `Node` `NodeOrder` `NodeCommission` `NodeReward` `NodeRewardLog` `UserPowerLog` `MintSpeed`
- 服务：`StakeBuyService` `StakeRewardService` `NodeBuyService` `NodeCommissionService` `FeeSettService`
- 命令：`StakeRewardCmd` `FeeSettCmd`
- API 控制器：`StakeBuyController` `StakeOrderController` `NodeBuyController` `NodeHomeController` `StakeHomeV2Controller`
- Admin 控制器：`MachineProductsController` `MachinesController` `MachineRatesController` `StakeOrdersController` `StakeRewardsController` `StakeSpeedsController` `UserPowerLogsController` `NodesController` `NodeOrdersController` `NodeRewardsController` `NodeCommissionsController`
- Admin Repositories：`MachineProductRepository` `MachineRepository`
- 迁移：`2026_08_09_000001_create_nodes_table.php` `2026_08_09_000002_create_node_orders_table.php` `2026_08_09_000003_create_node_commissions_table.php` `2026_08_09_000004_update_nodes_data.php` `2026_08_09_000005_add_node_marketing_wallet_config.php`
- Seeders：`database/seeders/Staking/` 整个目录
- 测试：`tests/Feature/NodeCommissionTest.php`

---

## Task 1: alpha 表迁移 + 模型 + 常量

**Files:**
- Create: `database/migrations/2026_08_31_000001_create_alpha_products_table.php`
- Create: `database/migrations/2026_08_31_000002_create_alpha_orders_table.php`
- Create: `database/migrations/2026_08_31_000003_create_alpha_rewards_table.php`
- Create: `database/migrations/2026_08_31_000004_add_alpha_quota_columns_to_user_datas_table.php`
- Create: `database/migrations/2026_08_31_000005_insert_alpha_quota_config.php`
- Create: `app/Models/AlphaProduct.php`
- Create: `app/Models/AlphaOrder.php`
- Create: `app/Models/AlphaReward.php`
- Modify: `app/Models/WalletLog.php`（常量）
- Modify: `app/Models/PayOrder.php`（常量）
- Test: `tests/Feature/AlphaOrderTest.php`（本任务只测常量/模型存在性）

**Interfaces:**
- Produces: 模型 `AlphaProduct`（`TIER_BASIC='basic'`、`TIER_HIGH='high'`、`STATUS_ON=1`、字段 name/tier/min_amount/max_amount/base_rate/node_rate/status/sort）；`AlphaOrder`（`STATUS_WAIT=0`、`STATUS_PAID=1`、`STATUS_DONE=2`、`STATUS_EXITED=3`、`STATUS_CANCEL=4`、`getBySn()`、`getByPk()`、`user()`、`product()`）；`AlphaReward`（字段 user_id/order_id/order_am/rate/reward_am/quota_used_am/frozen_am/settle_date，唯一 `(order_id, settle_date)`）；`WalletLog::TYPE_ALPHA_REWARD_IN=130` / `TYPE_ALPHA_EXIT=131` / `TYPE_ALPHA_UNFREEZE=132`；`PayOrder::TYPE_ALPHA_BUY=2`。迁移 000005 写入 `configs` 的 `alpha_quota_multiple = '2.5'`。

- [ ] **Step 1: 写迁移文件**

`2026_08_31_000001_create_alpha_products_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alpha_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('产品名称');
            $table->string('tier', 20)->comment('档位 basic/high');
            $table->decimal('min_amount', 20, 6)->unsigned()->default(0)->comment('最小申购额');
            $table->decimal('max_amount', 20, 6)->unsigned()->default(0)->comment('最大申购额');
            $table->decimal('base_rate', 10, 2)->unsigned()->comment('基础月产率(%)');
            $table->decimal('node_rate', 10, 2)->unsigned()->default(0)->comment('持节点加速月产率(%)');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=启用 0=停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alpha_products');
    }
};
```

`2026_08_31_000002_create_alpha_orders_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alpha_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('product_id')->comment('产品id');
            $table->string('sn', 20)->comment('单号');
            $table->decimal('amount', 20, 6)->unsigned()->comment('本金');
            $table->decimal('reward_rate', 10, 2)->unsigned()->comment('月产率快照(%)');
            $table->decimal('quota_power', 20, 6)->unsigned()->comment('静态封顶=2.5*本金');
            $table->decimal('rem_power', 20, 6)->unsigned()->default(0)->comment('剩余可产出');
            $table->decimal('rel_reward', 20, 6)->unsigned()->default(0)->comment('累计已产出');
            $table->decimal('last_reward', 20, 6)->unsigned()->default(0)->comment('最近产出');
            $table->date('last_day')->nullable()->comment('最近产出日期');
            $table->dateTime('trial_end')->nullable()->comment('首月试用截止');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0=待支付 1=进行中 2=已完成 3=已退出 4=取消');
            $table->dateTime('pay_at')->nullable()->comment('支付时间');
            $table->dateTime('done_at')->nullable()->comment('完成时间');
            $table->dateTime('exit_at')->nullable()->comment('退出时间');
            $table->dateTime('fail_at')->nullable()->comment('取消/失败时间');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('sn');
            $table->index('user_id');
            $table->index('status');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alpha_orders');
    }
};
```

`2026_08_31_000003_create_alpha_rewards_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alpha_rewards', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('order_id')->comment('订单id');
            $table->decimal('order_am', 20, 6)->unsigned()->comment('订单金额');
            $table->decimal('rate', 10, 2)->unsigned()->comment('月产率(%)');
            $table->decimal('reward_am', 20, 6)->unsigned()->comment('本次产出额');
            $table->decimal('quota_used_am', 20, 6)->unsigned()->default(0)->comment('计入额度槽金额');
            $table->decimal('frozen_am', 20, 6)->unsigned()->default(0)->comment('本次冻结金额');
            $table->date('settle_date')->comment('结算日');
            $table->dateTime('created_at')->nullable();
            $table->unique(['order_id', 'settle_date']);
            $table->index('user_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alpha_rewards');
    }
};
```

`2026_08_31_000004_add_alpha_quota_columns_to_user_datas_table.php`：
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
            $table->decimal('alpha_quota_used', 20, 6)->unsigned()->default(0)->comment('额度槽已用(C)');
            $table->decimal('alpha_frozen', 20, 6)->unsigned()->default(0)->comment('冻结收益');
        });
    }
    public function down(): void
    {
        Schema::table('user_datas', function (Blueprint $table) {
            $table->dropColumn(['alpha_quota_used', 'alpha_frozen']);
        });
    }
};
```

`2026_08_31_000005_insert_alpha_quota_config.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('configs')->insertOrIgnore([
            'group' => 'power',
            'key'   => 'alpha_quota_multiple',
            'value' => '2.5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function down(): void
    {
        DB::table('configs')->where('key', 'alpha_quota_multiple')->delete();
    }
};
```

- [ ] **Step 2: 写模型文件**

`app/Models/AlphaProduct.php`：
```php
<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class AlphaProduct extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'alpha_products';
    protected $guarded = [];

    public const TIER_BASIC = 'basic';
    public const TIER_HIGH  = 'high';
    public const TIER_MAP = [
        self::TIER_BASIC => '基础算力方案',
        self::TIER_HIGH  => '高额算力方案',
    ];

    public const STATUS_ON  = 1;
    public const STATUS_OFF = 0;
    public const STATUS_MAP = [
        self::STATUS_ON  => '启用',
        self::STATUS_OFF => '停用',
    ];

    public function orders()
    {
        return $this->hasMany(AlphaOrder::class, 'product_id');
    }
}
```

`app/Models/AlphaOrder.php`：
```php
<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class AlphaOrder extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'alpha_orders';
    protected $guarded = [];
    protected $casts = [
        'trial_end' => 'datetime',
        'pay_at'    => 'datetime',
        'done_at'   => 'datetime',
        'exit_at'   => 'datetime',
        'fail_at'   => 'datetime',
    ];

    public const STATUS_WAIT   = 0; // 待支付
    public const STATUS_PAID   = 1; // 进行中
    public const STATUS_DONE   = 2; // 已完成(2.5x到顶)
    public const STATUS_EXITED = 3; // 已退出(首月退80%)
    public const STATUS_CANCEL = 4; // 取消

    public const STATUS_MAP = [
        self::STATUS_WAIT   => '待支付',
        self::STATUS_PAID   => '进行中',
        self::STATUS_DONE   => '已完成',
        self::STATUS_EXITED => '已退出',
        self::STATUS_CANCEL => '取消',
    ];

    public const STATUS_COLOR = [
        self::STATUS_WAIT   => 'default',
        self::STATUS_PAID   => 'info',
        self::STATUS_DONE   => 'success',
        self::STATUS_EXITED => 'warning',
        self::STATUS_CANCEL => 'danger',
    ];

    public static function getByPk(int $id): ?self
    {
        return self::find($id);
    }

    public static function getBySn(string $sn): ?self
    {
        return self::query()->where('sn', $sn)->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(AlphaProduct::class, 'product_id');
    }
}
```

`app/Models/AlphaReward.php`：
```php
<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class AlphaReward extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'alpha_rewards';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(AlphaOrder::class, 'order_id');
    }
}
```

- [ ] **Step 3: 加 WalletLog / PayOrder 常量**

`app/Models/WalletLog.php` — 在 `TYPE_FEE_FH = 120;` 之后加：
```php
    // NOX Alpha 资产包
    public const TYPE_ALPHA_REWARD_IN = 130; // 资产包每日产出
    public const TYPE_ALPHA_EXIT      = 131; // 资产包首月退出退款
    public const TYPE_ALPHA_UNFREEZE  = 132; // 额度槽冻结收益解冻
```
并在 `TYPE_MAP` 末尾加：
```php
        // NOX Alpha 资产包
        self::TYPE_ALPHA_REWARD_IN => '资产包每日产出',
        self::TYPE_ALPHA_EXIT      => '资产包首月退出退款',
        self::TYPE_ALPHA_UNFREEZE  => '冻结收益解冻',
```

`app/Models/PayOrder.php` — 在 `const TYPE_RECHARGE = 1;` 后加：
```php
    const TYPE_ALPHA_BUY = 2; // 资产包申购
```
并在 `TYPE_MAP` 加：
```php
        self::TYPE_ALPHA_BUY => '资产包申购',
```

- [ ] **Step 4: 写测试验证常量与模型**

`tests/Feature/AlphaOrderTest.php`（本任务先放一个冒烟测试）：
```php
<?php

use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\AlphaReward;
use App\Models\PayOrder;
use App\Models\WalletLog;

test('alpha constants and models are registered', function () {
    expect(PayOrder::TYPE_ALPHA_BUY)->toBe(2);
    expect(WalletLog::TYPE_ALPHA_REWARD_IN)->toBe(130);
    expect(WalletLog::TYPE_ALPHA_EXIT)->toBe(131);
    expect(WalletLog::TYPE_ALPHA_UNFREEZE)->toBe(132);

    $product = AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'base_rate' => '17.00',
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    expect(AlphaProduct::find($product->id)->tier)->toBe('basic');

    $order = AlphaOrder::create([
        'user_id' => 1,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => '1000.000000',
        'reward_rate' => '17.00',
        'quota_power' => '2500.000000',
        'rem_power' => '2500.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);
    expect(AlphaOrder::getBySn($order->sn)->id)->toBe($order->id);
    expect(AlphaOrder::STATUS_MAP[AlphaOrder::STATUS_PAID])->toBe('进行中');

    AlphaReward::create([
        'user_id' => 1,
        'order_id' => $order->id,
        'order_am' => '1000.000000',
        'rate' => '17.00',
        'reward_am' => '5.66666666',
        'settle_date' => now()->toDateString(),
    ]);
    expect(AlphaReward::count())->toBe(1);
});
```

- [ ] **Step 5: 跑测试确认通过**

Run: `php artisan test tests/Feature/AlphaOrderTest.php`
Expected: PASS（若无 sqlite 迁移问题则先 `php artisan test` 观察输出）。

- [ ] **Step 6: 提交**

```bash
git add database/migrations/2026_08_31_00000* app/Models/Alpha*.php app/Models/WalletLog.php app/Models/PayOrder.php tests/Feature/AlphaOrderTest.php
git commit -m "feat: add alpha package tables, models and wallet/pay type constants"
```

---

## Task 2: AlphaQuotaService（额度槽记账 + 解冻）

**Files:**
- Create: `app/Services/AlphaQuotaService.php`
- Test: `tests/Feature/AlphaRewardTest.php`（本任务只测额度槽部分）

**Interfaces:**
- Consumes: `AlphaOrder::STATUS_PAID`、`WalletLog::TYPE_ALPHA_REWARD_IN`、`WalletLog::TYPE_ALPHA_UNFREEZE`、`getGlobalConfig('alpha_quota_multiple','2.5')`、`UserData`（`alpha_quota_used`/`alpha_frozen`/`current_usdt`）。
- Produces: `AlphaQuotaService::currentSlot(int $uid): string`；`AlphaQuotaService::creditEarnings(int $uid, string $amount, int $type): array{available:string, frozen:string}`；`AlphaQuotaService::sweep(int $uid): string`。

- [ ] **Step 1: 写失败测试**

`tests/Feature/AlphaRewardTest.php`：
```php
<?php

use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\User;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Services\AlphaQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeAlphaUser(): User
{
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    return $user;
}

function makeAlphaOrder(User $user, string $amount, string $rate = '17.00'): AlphaOrder
{
    $product = AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'base_rate' => $rate,
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    return AlphaOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => $amount,
        'reward_rate' => $rate,
        'quota_power' => bcmul($amount, '2.5', 6),
        'rem_power' => bcmul($amount, '2.5', 6),
        'status' => AlphaOrder::STATUS_PAID,
    ]);
}

test('creditEarnings within slot credits all to available', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S = 25000

    $res = AlphaQuotaService::creditEarnings($user->id, '100.000000', WalletLog::TYPE_ALPHA_REWARD_IN);

    expect($res['available'])->toBe('100.000000');
    expect($res['frozen'])->toBe('0.000000');
    $data = UserData::getInfo($user->id);
    expect($data->current_usdt)->toBe('100.000000');
    expect($data->alpha_quota_used)->toBe('100.000000');
    expect($data->alpha_frozen)->toBe('0.000000');
});

test('creditEarnings freezes the excess beyond slot', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S = 25000
    AlphaQuotaService::creditEarnings($user->id, '24900.000000', WalletLog::TYPE_ALPHA_REWARD_IN); // C = 24900

    $res = AlphaQuotaService::creditEarnings($user->id, '200.000000', WalletLog::TYPE_ALPHA_REWARD_IN);

    expect($res['available'])->toBe('100.000000'); // 25000-24900
    expect($res['frozen'])->toBe('100.000000');    // 200-100
    $data = UserData::getInfo($user->id);
    expect($data->alpha_quota_used)->toBe('25000.000000'); // 封顶到 S
    expect($data->alpha_frozen)->toBe('100.000000');
});

test('sweep unfreezes frozen earnings after new order', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S = 25000
    AlphaQuotaService::creditEarnings($user->id, '24900.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    AlphaQuotaService::creditEarnings($user->id, '200.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    expect(UserData::getInfo($user->id)->alpha_frozen)->toBe('100.000000');

    makeAlphaOrder($user, '10000.000000'); // 新订单 → S = 50000

    $swept = AlphaQuotaService::sweep($user->id);

    expect($swept)->toBe('100.000000');
    $data = UserData::getInfo($user->id);
    expect($data->alpha_frozen)->toBe('0.000000');
    expect($data->current_usdt)->toBe('25100.000000'); // 24900 + 100(入账) + 100(解冻)
});
```

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/AlphaRewardTest.php`
Expected: FAIL（`AlphaQuotaService` 不存在）。

- [ ] **Step 3: 写实现**

`app/Services/AlphaQuotaService.php`：
```php
<?php

namespace App\Services;

use App\Exceptions\LogicException;
use App\Models\AlphaOrder;
use App\Models\UserData;
use App\Models\WalletLog;
use Illuminate\Support\Facades\DB;

class AlphaQuotaService
{
    /**
     * 当前额度槽 S = 2.5 * 进行中订单本金之和
     */
    public static function currentSlot(int $uid): string
    {
        $sum = AlphaOrder::query()
            ->where('user_id', $uid)
            ->where('status', AlphaOrder::STATUS_PAID)
            ->sum('amount');
        $multiple = getGlobalConfig('alpha_quota_multiple', '2.5');
        return bcadd(bcmul($sum, $multiple, 8), '0', 6);
    }

    /**
     * 产出入账：C+E <= S 全入可用；超出部分进冻结。返回 [available, frozen]。
     */
    public static function creditEarnings(int $uid, string $amount, int $type): array
    {
        if (bccomp($amount, '0', 6) <= 0) {
            return ['available' => '0.000000', 'frozen' => '0.000000'];
        }
        return DB::transaction(function () use ($uid, $amount, $type) {
            $slot = self::currentSlot($uid);
            $data = UserData::getInfo($uid);
            if (!$data) {
                $data = UserData::addInfo($uid);
            }
            $used = $data->alpha_quota_used;
            $remain = bcsub($slot, $used, 6);

            $available = '0.000000';
            $frozen = '0.000000';
            if (bccomp($remain, '0', 6) > 0) {
                $available = bccomp($amount, $remain, 6) > 0 ? $remain : $amount;
                $frozen = bcsub($amount, $available, 6);
                WalletLog::addLog($uid, $available, WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, $type);
                $newUsed = bcadd($used, $available, 6);
                UserData::query()->where('user_id', $uid)->update([
                    'alpha_quota_used' => $newUsed,
                    'alpha_frozen' => DB::raw('alpha_frozen + ' . $frozen),
                ]);
            } else {
                // 槽已满：全额冻结
                $frozen = $amount;
                UserData::query()->where('user_id', $uid)->update([
                    'alpha_frozen' => DB::raw('alpha_frozen + ' . $amount),
                ]);
            }
            return ['available' => $available, 'frozen' => $frozen];
        });
    }

    /**
     * 新订单后解冻：把冻结收益按新槽位余量转可用。返回解冻金额。
     */
    public static function sweep(int $uid): string
    {
        return DB::transaction(function () use ($uid) {
            $data = UserData::getInfo($uid);
            if (!$data) {
                return '0.000000';
            }
            $frozen = $data->alpha_frozen;
            if (bccomp($frozen, '0', 6) <= 0) {
                return '0.000000';
            }
            $slot = self::currentSlot($uid);
            $headroom = bcsub($slot, $data->alpha_quota_used, 6);
            if (bccomp($headroom, '0', 6) <= 0) {
                return '0.000000';
            }
            $sweep = bccomp($frozen, $headroom, 6) > 0 ? $headroom : $frozen;
            WalletLog::addLog($uid, $sweep, WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_ALPHA_UNFREEZE);
            UserData::query()->where('user_id', $uid)->update([
                'alpha_frozen' => DB::raw('alpha_frozen - ' . $sweep),
            ]);
            return $sweep;
        });
    }
}
```

> 注：`creditEarnings` 里 `WalletLog::addLog` 已包含余额校验（余额不足抛 `LogicException`），不必重复。

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/AlphaRewardTest.php`
Expected: PASS（3 个用例）。

- [ ] **Step 5: 提交**

```bash
git add app/Services/AlphaQuotaService.php tests/Feature/AlphaRewardTest.php
git commit -m "feat: add AlphaQuotaService credit/sweep for 2.5x quota slot"
```

---

## Task 3: AlphaOrderService（申购/对账/首月退出）

**Files:**
- Create: `app/Services/AlphaOrderService.php`
- Test: `tests/Feature/AlphaOrderTest.php`（追加申购/退出用例）

**Interfaces:**
- Consumes: `AlphaProduct`、`AlphaOrder`、`PayOrder`（`TYPE_ALPHA_BUY`）、`Asset::getPayInfo`、`config('site.asset.chain_id')`/`config('site.pay_contract')`、`AlphaQuotaService::sweep`、`WalletLog::TYPE_ALPHA_EXIT`。
- Produces: `AlphaOrderService::createOrder(User $user, AlphaProduct $product, string $amount, array $pay): AlphaOrder`；`payCallback(AlphaOrder $o, PayOrder $p, ?string $hash, string $pay_total): void`；`payCancel(...)`；`payTimeout(...)`；`payFail(...)`；`exit(AlphaOrder $o): void`。

- [ ] **Step 1: 写失败测试（追加到 AlphaOrderTest.php）**

```php
use App\Models\Chain;
use App\Models\Asset;
use App\Models\PayOrder;
use App\Services\AlphaOrderService;
use Illuminate\Support\Facades\DB;

test('createOrder builds alpha order and pay order with snapshot', function () {
    config(['site.asset.chain_id' => '97']);
    config(['site.pay_contract' => '0x' . str_repeat('a', 40)]);
    DB::table('chains')->insert([
        'id' => 1, 'name' => 'bsc', 'alias' => 'bsc', 'chain_id' => '97',
        'symbol' => 'BNB', 'decimals' => 18, 'rpc_url' => 'https://example.com',
        'scan_url' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('assets')->insert([
        'id' => 1, 'chain_id' => 1, 'name' => 'USDT', 'title' => 'USDT', 'code' => 'USDT',
        'decimals' => 18, 'type' => 2, 'status' => 1, 'is_exchange' => 1,
        'exchange_rate' => '1.00000000', 'contract_abi' => '[]',
        'contract_address' => '0x' . str_repeat('b', 40),
        'withdraw_address' => '0x' . str_repeat('c', 40), 'withdraw_key' => '',
        'wallet_address' => '0x' . str_repeat('d', 40),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $user = makeAlphaUser();
    $product = AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);
    $pay = [
        'pay_contract' => config('site.pay_contract'),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];

    $order = AlphaOrderService::createOrder($user, $product, '1000.000000', $pay);

    expect($order->amount)->toBe('1000.000000');
    expect($order->reward_rate)->toBe('17.00');
    expect($order->quota_power)->toBe('2500.000000');
    expect($order->rem_power)->toBe('2500.000000');
    expect($order->status)->toBe(AlphaOrder::STATUS_WAIT);
    expect($order->trial_end->gt(now()))->toBeTrue();
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
    expect($payOrder->type)->toBe(PayOrder::TYPE_ALPHA_BUY);
    expect($payOrder->order_id)->toBe($order->id);
});

test('payCallback activates order and sweeps frozen', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S=25000
    \App\Services\AlphaQuotaService::creditEarnings($user->id, '24900.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    \App\Services\AlphaQuotaService::creditEarnings($user->id, '200.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    expect(UserData::getInfo($user->id)->alpha_frozen)->toBe('100.000000');

    // 用户再申购一笔，直接走支付回调
    $product = AlphaProduct::first();
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = AlphaOrderService::createOrder($user, $product, '10000.000000', $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    AlphaOrderService::payCallback($order, $payOrder, '0x' . str_repeat('f', 64), '10000.000000');

    expect(AlphaOrder::find($order->id)->status)->toBe(AlphaOrder::STATUS_PAID);
    $data = UserData::getInfo($user->id);
    expect($data->alpha_frozen)->toBe('0.000000');
});

test('exit within trial refunds 80% and keeps earnings', function () {
    $user = makeAlphaUser();
    $order = makeAlphaOrder($user, '1000.000000');

    AlphaOrderService::exit($order);

    $fresh = AlphaOrder::find($order->id);
    expect($fresh->status)->toBe(AlphaOrder::STATUS_EXITED);
    expect($fresh->exit_at->notNull())->toBeTrue();
    $data = UserData::getInfo($user->id);
    expect($data->current_usdt)->toBe('800.000000'); // 80% 退款
    expect(WalletLog::query()->where('user_id', $user->id)->where('type', WalletLog::TYPE_ALPHA_EXIT)->count())->toBe(1);
});
```

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/AlphaOrderTest.php`
Expected: FAIL（`AlphaOrderService` 不存在）。

- [ ] **Step 3: 写实现**

`app/Services/AlphaOrderService.php`：
```php
<?php

namespace App\Services;

use App\Exceptions\LogicException;
use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\PayOrder;
use App\Models\User;
use App\Models\WalletLog;
use Illuminate\Support\Carbon;

class AlphaOrderService
{
    /**
     * 创建资产包申购订单 + 支付单（链上直付，不扣内部余额）
     */
    public static function createOrder(User $user, AlphaProduct $product, string $amount, array $pay): AlphaOrder
    {
        $sn = randOrderNo();
        $now = Carbon::now();

        $order = new AlphaOrder();
        $order->user_id     = $user->id;
        $order->product_id  = $product->id;
        $order->sn          = $sn;
        $order->amount      = $amount;
        $order->reward_rate = $product->base_rate;
        $order->quota_power = bcmul($amount, getGlobalConfig('alpha_quota_multiple', '2.5'), 6);
        $order->rem_power   = $order->quota_power;
        $order->trial_end   = $now->copy()->addDays(30);
        $order->status      = AlphaOrder::STATUS_WAIT;
        $order->save();

        $payOrder = new PayOrder();
        $payOrder->user_id      = $user->id;
        $payOrder->order_id     = $order->id;
        $payOrder->sn           = $sn;
        $payOrder->type         = PayOrder::TYPE_ALPHA_BUY;
        $payOrder->amount       = $amount;
        $payOrder->decimals     = $pay['decimals'];
        $payOrder->symbol       = WalletLog::SYMBOL_USDT;
        $payOrder->pay_contract = $pay['pay_contract'];
        $payOrder->pay_addr     = $pay['pay_addr'];
        $payOrder->token_addr   = $pay['token_addr'];
        $payOrder->dest_addr    = $pay['dest_addr'];
        $payOrder->last_time    = $pay['last_time'];
        $payOrder->status       = PayOrder::STATUS_WAIT;
        $payOrder->save();

        return $order;
    }

    /**
     * 支付确认：激活订单（待支付→进行中）并触发解冻
     */
    public static function payCallback(AlphaOrder $order, PayOrder $payOrder, ?string $hash, string $pay_total)
    {
        $now = Carbon::now();
        $re = PayOrder::query()->where('id', $payOrder->id)->where('status', PayOrder::STATUS_WAIT)->update([
            'status'    => PayOrder::STATUS_PAID,
            'hash'      => $hash,
            'pay_total' => $pay_total,
            'pay_at'    => $now,
        ]);
        if (!$re) {
            throw new LogicException(__('支付状态异常'));
        }
        $re2 = AlphaOrder::query()->where('id', $order->id)->where('status', AlphaOrder::STATUS_WAIT)->update([
            'status' => AlphaOrder::STATUS_PAID,
            'pay_at' => $now,
        ]);
        if (!$re2) {
            throw new LogicException(__('订单状态异常'));
        }
        // 本金入槽 → 解冻
        AlphaQuotaService::sweep($order->user_id);
    }

    public static function payCancel(AlphaOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_CANCEL, AlphaOrder::STATUS_CANCEL, $fail_reason);
    }

    public static function payTimeout(AlphaOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_TIMEOUT, AlphaOrder::STATUS_CANCEL, $fail_reason);
    }

    public static function payFail(AlphaOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_FAIL, AlphaOrder::STATUS_CANCEL, $fail_reason);
    }

    private static function markTerminal(AlphaOrder $order, PayOrder $payOrder, int $payStatus, int $orderStatus, ?string $fail_reason)
    {
        $now = Carbon::now();
        $re = PayOrder::query()->where('id', $payOrder->id)->where('status', PayOrder::STATUS_WAIT)->update([
            'status'      => $payStatus,
            'fail_at'     => $now,
            'fail_reason' => $fail_reason,
        ]);
        if (!$re) {
            throw new LogicException(__('支付状态异常'));
        }
        $re2 = AlphaOrder::query()->where('id', $order->id)->where('status', AlphaOrder::STATUS_WAIT)->update([
            'status'  => $orderStatus,
            'fail_at' => $now,
        ]);
        if (!$re2) {
            throw new LogicException(__('订单状态异常'));
        }
    }

    /**
     * 首月试用退出：退 80% 本金入可用余额，订单终止
     */
    public static function exit(AlphaOrder $order)
    {
        $now = Carbon::now();
        $res = AlphaOrder::query()
            ->where('id', $order->id)
            ->where('status', AlphaOrder::STATUS_PAID)
            ->where('trial_end', '>=', $now)
            ->update([
                'status'  => AlphaOrder::STATUS_EXITED,
                'exit_at' => $now,
            ]);
        if (!$res) {
            throw new LogicException(__('当前订单不可退出'));
        }
        $refund = bcmul($order->amount, '0.8', 6);
        WalletLog::addLog($order->user_id, $refund, WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_ALPHA_EXIT);
    }
}
```

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/AlphaOrderTest.php`
Expected: PASS（4 个用例：常量冒烟 + 创建 + 支付回调 + 退出）。

- [ ] **Step 5: 提交**

```bash
git add app/Services/AlphaOrderService.php tests/Feature/AlphaOrderTest.php
git commit -m "feat: add AlphaOrderService purchase/payback/trial-exit"
```

---

## Task 4: AlphaRewardService 每日结算 + 命令 + 调度

**Files:**
- Create: `app/Services/AlphaRewardService.php`
- Create: `app/Console/Commands/AlphaRewardCmd.php`
- Modify: `routes/console.php`（替换 `app:stake-reward` 调度）
- Test: `tests/Feature/AlphaRewardTest.php`（追加结算用例）

**Interfaces:**
- Consumes: `AlphaOrder::STATUS_PAID`、`AlphaQuotaService::creditEarnings`、`WalletLog::TYPE_ALPHA_REWARD_IN`、`AlphaReward`。
- Produces: `AlphaRewardService::run(string $settle_date, bool $skip=false): bool`；命令 `app:alpha-reward {date?} {--skip}`。

- [ ] **Step 1: 写失败测试（追加到 AlphaRewardTest.php）**

```php
use App\Models\AlphaReward;
use App\Services\AlphaRewardService;

test('daily settlement credits reward and decrements rem_power', function () {
    $user = makeAlphaUser();
    $order = makeAlphaOrder($user, '10000.000000', '17.00'); // 日产出 = 10000*17/3000 = 56.66666666

    $ok = (new AlphaRewardService())->run(now()->toDateString());

    expect($ok)->toBeTrue();
    $fresh = AlphaOrder::find($order->id);
    expect($fresh->rel_reward)->toBe('56.66666666');
    expect($fresh->rem_power)->toBe('2443.33333334'); // 2500 - 56.66666666
    expect(UserData::getInfo($user->id)->current_usdt)->toBe('56.66666666');
    expect(AlphaReward::query()->where('order_id', $order->id)->where('settle_date', now()->toDateString())->count())->toBe(1);
});

test('daily settlement is idempotent for same order and date', function () {
    $user = makeAlphaUser();
    $order = makeAlphaOrder($user, '10000.000000');

    (new AlphaRewardService())->run(now()->toDateString());
    (new AlphaRewardService())->run(now()->toDateString());

    expect(AlphaReward::query()->where('order_id', $order->id)->count())->toBe(1);
    expect(AlphaOrder::find($order->id)->rel_reward)->toBe('56.66666666');
});

test('order auto-completes when rel_reward reaches quota_power', function () {
    $user = makeAlphaUser();
    $product = AlphaProduct::first();
    $order = AlphaOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => '1000.000000',
        'reward_rate' => '17.00',
        'quota_power' => '2500.000000',
        'rem_power' => '1.000000',   // 只差 1
        'rel_reward' => '2499.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);

    (new AlphaRewardService())->run(now()->toDateString());

    $fresh = AlphaOrder::find($order->id);
    expect($fresh->status)->toBe(AlphaOrder::STATUS_DONE);
    expect($fresh->done_at->notNull())->toBeTrue();
    expect($fresh->rel_reward)->toBe('2500.000000'); // 以 rem_power 封顶
    expect($fresh->rem_power)->toBe('0.000000');
});
```

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/AlphaRewardTest.php`
Expected: FAIL（`AlphaRewardService` 不存在）。

- [ ] **Step 3: 写实现**

`app/Services/AlphaRewardService.php`（复用 StakeRewardService 的模式）：
```php
<?php

namespace App\Services;

use App\Models\AlphaOrder;
use App\Models\AlphaReward;
use App\Models\UserData;
use App\Models\WalletLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlphaRewardService
{
    public function run(string $settle_date, bool $skip = false): bool
    {
        $lock = Cache::lock('alpha_reward_service', 1800);
        if (!$lock->get()) {
            throw new \Exception('任务运行中，请稍后再试');
        }

        if (!$skip && AlphaReward::query()->where('settle_date', $settle_date)->exists()) {
            $lock->release();
            return true;
        }

        try {
            $endOfDay = Carbon::parse($settle_date)->endOfDay();
            AlphaOrder::query()
                ->where('status', AlphaOrder::STATUS_PAID)
                ->where('created_at', '<=', $endOfDay)
                ->chunkById(100, function ($orders) use ($settle_date) {
                    foreach ($orders as $order) {
                        if (AlphaReward::query()->where('order_id', $order->id)->where('settle_date', $settle_date)->exists()) {
                            continue;
                        }
                        $this->settleOne($order, $settle_date);
                    }
                });
        } catch (\Throwable $e) {
            Log::error('alpha_reward_settle_error', [$settle_date, $e->getMessage(), $e->getTraceAsString()]);
            $lock->release();
            return false;
        }

        $lock->release();
        return true;
    }

    private function settleOne(AlphaOrder $order, string $settle_date): void
    {
        DB::transaction(function () use ($order, $settle_date) {
            // 产出 = 本金 * 月产率 / 3000，受 rem_power 封顶
            $reward = bcdiv(bcmul($order->amount, $order->reward_rate, 8), '3000', 8);
            if (bccomp($reward, $order->rem_power, 8) > 0) {
                $reward = $order->rem_power;
            }
            if (bccomp($reward, '0', 8) <= 0) {
                return;
            }

            $res = AlphaOrder::query()
                ->where('id', $order->id)
                ->where('status', AlphaOrder::STATUS_PAID)
                ->where('rem_power', '>=', $reward)
                ->update([
                    'rem_power'   => DB::raw('rem_power - ' . $reward),
                    'rel_reward'  => DB::raw('rel_reward + ' . $reward),
                    'last_reward' => $reward,
                    'last_day'    => $settle_date,
                ]);
            if (!$res) {
                throw new \Exception("订单 {$order->id} 结算冲突");
            }

            [$available, $frozen] = array_values(AlphaQuotaService::creditEarnings($order->user_id, $reward, WalletLog::TYPE_ALPHA_REWARD_IN));

            AlphaReward::create([
                'user_id'       => $order->user_id,
                'order_id'      => $order->id,
                'order_am'      => $order->amount,
                'rate'          => $order->reward_rate,
                'reward_am'     => $reward,
                'quota_used_am' => $available,
                'frozen_am'     => $frozen,
                'settle_date'   => $settle_date,
            ]);

            $fresh = AlphaOrder::find($order->id);
            if (bccomp($fresh->rel_reward, $fresh->quota_power, 6) >= 0) {
                AlphaOrder::query()->where('id', $order->id)->update([
                    'status'  => AlphaOrder::STATUS_DONE,
                    'done_at' => Carbon::now(),
                ]);
            }
        });
    }
}
```

`app/Console/Commands/AlphaRewardCmd.php`：
```php
<?php

namespace App\Console\Commands;

use App\Services\AlphaRewardService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AlphaRewardCmd extends Command
{
    protected $signature = 'app:alpha-reward {date?} {--skip}';

    protected $description = '资产包每日产出结算';

    public function handle()
    {
        $inputDate = $this->argument('date');
        $skip = $this->option('skip');
        if (!$inputDate) {
            $inputDate = Carbon::yesterday()->toDateString();
        }
        $date = Carbon::createFromFormat('Y-m-d', $inputDate);
        if (!$date || $date->format('Y-m-d') !== $inputDate) {
            $this->error('date format must be Y-m-d');
            return 1;
        }
        try {
            $service = new AlphaRewardService();
            return $service->run($inputDate, $skip ? true : false) ? 0 : 1;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
```

`routes/console.php` — 把：
```php
// 质押结算: app:stake-reward 每天执行一次
Schedule::command('app:stake-reward')
    ->withoutOverlapping()
    ->runInBackground()
    ->dailyAt('00:20')
    ->name('stake-reward')
    ->appendOutputTo(storage_path('logs/stake-reward.log'));
```
替换为：
```php
// 资产包结算: app:alpha-reward 每天执行一次
Schedule::command('app:alpha-reward')
    ->withoutOverlapping()
    ->runInBackground()
    ->dailyAt('00:20')
    ->name('alpha-reward')
    ->appendOutputTo(storage_path('logs/alpha-reward.log'));
```
（同文件里删除 `app:fee-sett` 的 Schedule 段，见 Task 7 删除；本任务仅替换质押段。）

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/AlphaRewardTest.php`
Expected: PASS（6 个用例）。

- [ ] **Step 5: 提交**

```bash
git add app/Services/AlphaRewardService.php app/Console/Commands/AlphaRewardCmd.php routes/console.php tests/Feature/AlphaRewardTest.php
git commit -m "feat: add daily alpha reward settlement command and schedule"
```

---

## Task 5: PowerController（前台 API）+ 路由

**Files:**
- Create: `app/Http/Controllers/Api/PowerController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/AlphaOrderTest.php`（追加 HTTP 用例）

**Interfaces:**
- Consumes: `AlphaProduct`、`AlphaOrderService::createOrder`、`AlphaQuotaService`、`Asset::getPayInfo`、`Chain::getInfoByChainId`。
- Produces: 路由 `GET power/home`、`POST power/create`、`GET power/orders`、`POST power/exit`、`POST power/done`、`POST power/cancel`（均在 auth:sanctum + checkSign + language 组内）。

- [ ] **Step 1: 写失败测试（追加到 AlphaOrderTest.php）**

```php
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['site.asset.chain_id' => '97']);
    config(['site.pay_contract' => '0x' . str_repeat('a', 40)]);
    DB::table('chains')->insertOrIgnore([
        'id' => 1, 'name' => 'bsc', 'alias' => 'bsc', 'chain_id' => '97',
        'symbol' => 'BNB', 'decimals' => 18, 'rpc_url' => 'https://example.com',
        'scan_url' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('assets')->insertOrIgnore([
        'id' => 1, 'chain_id' => 1, 'name' => 'USDT', 'title' => 'USDT', 'code' => 'USDT',
        'decimals' => 18, 'type' => 2, 'status' => 1, 'is_exchange' => 1,
        'exchange_rate' => '1.00000000', 'contract_abi' => '[]',
        'contract_address' => '0x' . str_repeat('b', 40),
        'withdraw_address' => '0x' . str_repeat('c', 40), 'withdraw_key' => '',
        'wallet_address' => '0x' . str_repeat('d', 40),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    Cache::flush();
});

test('power/create returns pay info without debiting internal balance', function () {
    $user = makeAlphaUser();
    Sanctum::actingAs($user);
    AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);

    $resp = $this->postJson('/api/power/create', ['product_id' => 1, 'amount' => '1000']);

    $resp->assertJson(['code' => 200]);
    $order = AlphaOrder::first();
    expect($order->status)->toBe(AlphaOrder::STATUS_WAIT);
    expect(UserData::getInfo($user->id)->current_usdt)->toBe('0.000000'); // 不扣余额
    expect($resp->json('data.sn'))->toBe($order->sn);
});

test('power/create rejects amount outside product range', function () {
    $user = makeAlphaUser();
    Sanctum::actingAs($user);
    AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);

    $resp = $this->postJson('/api/power/create', ['product_id' => 1, 'amount' => '50']);

    $resp->assertJson(['code' => 400]);
    expect(AlphaOrder::count())->toBe(0);
});
```

> 注：`beforeEach` 里给全局辅助函数 `makeAlphaUser`/`makeAlphaOrder` 用的是文件级 `function`，与 `beforeEach` 共存于同一文件无冲突。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/AlphaOrderTest.php`
Expected: FAIL（路由 `power/create` 不存在 → 404）。

- [ ] **Step 3: 写实现**

`routes/api.php` — 在受保护组（`auth:sanctum` + `checkSign`）内、`my/assetLogs/types` 之后加入：
```php
    // 算力资产包
    Route::get('power/home', [\App\Http\Controllers\Api\PowerController::class, 'home']); // 资产包首页
    Route::post('power/create', [\App\Http\Controllers\Api\PowerController::class, 'create']); // 申购
    Route::get('power/orders', [\App\Http\Controllers\Api\PowerController::class, 'orders']); // 我的订单
    Route::post('power/exit', [\App\Http\Controllers\Api\PowerController::class, 'exit']); // 首月退出
    Route::post('power/done', [\App\Http\Controllers\Api\PowerController::class, 'done']); // 支付确认
    Route::post('power/cancel', [\App\Http\Controllers\Api\PowerController::class, 'cancel']); // 取消支付
```

`app/Http/Controllers/Api/PowerController.php`：
```php
<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\Asset;
use App\Models\Chain;
use App\Models\PayOrder;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Services\AlphaOrderService;
use App\Services\AlphaQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PowerController extends Controller
{
    const CACHE_KEY = 'user_power_';

    // 资产包首页：产品 + 我的额度槽概览
    public function home(Request $request)
    {
        $user = $request->user();
        $data = UserData::getInfo($user->id);
        $slot = AlphaQuotaService::currentSlot($user->id);
        $orders = AlphaOrder::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function (AlphaOrder $o) {
                return [
                    'id' => $o->id,
                    'sn' => $o->sn,
                    'amount' => $o->amount,
                    'reward_rate' => $o->reward_rate,
                    'rel_reward' => $o->rel_reward,
                    'quota_power' => $o->quota_power,
                    'status' => $o->status,
                    'status_name' => AlphaOrder::STATUS_MAP[$o->status],
                    'trial_end' => $o->trial_end?->toDateTimeString(),
                    'created_at' => $o->created_at?->toDateTimeString(),
                ];
            });

        return apiSuccess([
            'products' => AlphaProduct::query()->where('status', AlphaProduct::STATUS_ON)->orderBy('sort')->get(),
            'slot' => [
                'total' => $slot,
                'used' => $data?->alpha_quota_used ?? '0.000000',
                'frozen' => $data?->alpha_frozen ?? '0.000000',
            ],
            'orders' => $orders,
        ]);
    }

    // 申购：创建订单 + 支付单，返回支付信息
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'amount' => 'required|decimal:0,6|gt:0',
        ], [
            'product_id.required' => __('产品不能为空'),
            'amount.required' => __('金额不能为空'),
            'amount.decimal' => __('金额不正确'),
            'amount.gt' => __('金额必须大于0'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $product = AlphaProduct::query()->where('id', $request->input('product_id'))->where('status', AlphaProduct::STATUS_ON)->first();
        if (!$product) {
            return apiError(__('产品不存在或已停用'));
        }
        $amount = $request->input('amount');
        if (bccomp($amount, $product->min_amount, 6) < 0 || bccomp($amount, $product->max_amount, 6) > 0) {
            return apiError(__('金额不在申购区间内'));
        }

        $user = $request->user();
        $lock = Cache::lock(self::CACHE_KEY . $user->id, 5);
        if (!$lock->get()) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        try {
            list($asset, $chain, $pay_contract) = $this->getAsset(Asset::CODE_USDT);
        } catch (LogicException $e) {
            $lock->release();
            throw $e;
        }

        $last_time = Carbon::now()->addMinutes(5);
        $pay = [
            'pay_contract' => $pay_contract,
            'pay_addr'     => $user->address,
            'token_addr'   => $asset->contract_address,
            'dest_addr'    => $asset->wallet_address,
            'last_time'    => $last_time,
            'decimals'     => $asset->decimals,
        ];

        DB::beginTransaction();
        try {
            $order = AlphaOrderService::createOrder($user, $product, $amount, $pay);
            // 非生产 mock 支付回调（与充值一致）
            if (!config('app.check.pay')) {
                $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
                AlphaOrderService::payCallback($order, $payOrder, null, $order->amount);
            }
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();
            return apiError($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            $lock->release();
            Log::error('power_create_error', [$e->getMessage(), $e->getTraceAsString()]);
            return apiError(__('服务器异常'));
        }
        DB::commit();
        $lock->release();

        return apiSuccess([
            'sn' => $order->sn,
            'amount' => bcmul($order->amount, pow(10, $pay['decimals']), 0),
            'token_addr' => $pay['token_addr'],
            'last_time' => $last_time->timestamp,
        ]);
    }

    // 我的订单
    public function orders(Request $request)
    {
        $user = $request->user();
        $page = AlphaOrder::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(20);

        return apiPage($page);
    }

    // 首月退出
    public function exit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ], ['id.required' => __('订单不能为空')]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user = $request->user();
        $order = AlphaOrder::query()->where('id', $request->input('id'))->where('user_id', $user->id)->first();
        if (!$order) {
            return apiError(__('订单不存在'));
        }
        try {
            AlphaOrderService::exit($order);
        } catch (LogicException $e) {
            return apiError($e->getMessage());
        }
        return apiSuccess(__('退出成功'));
    }

    // 支付确认（用户提交 hash 快速验证，与充值 done 一致）
    public function done(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sn' => 'required|numeric|digits:19',
            'hash' => 'required|size:66',
        ], [
            'sn.required' => __('单号不能为空'),
            'hash.required' => __('交易号不能为空'),
            'hash.size' => __('交易号格式不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user = $request->user();
        $sn = $request->input('sn');
        $hash = $request->input('hash');

        $payOrder = PayOrder::query()->where('sn', $sn)->where('type', PayOrder::TYPE_ALPHA_BUY)->first();
        if (!$payOrder || $payOrder->user_id != $user->id) {
            return apiError(__('支付单不存在'));
        }
        $order = AlphaOrder::getBySn($sn);
        if (!$order) {
            return apiError(__('订单不存在'));
        }
        if ($payOrder->status == PayOrder::STATUS_PAID) {
            return apiSuccess(__('订单已支付'));
        }
        if ($payOrder->status != PayOrder::STATUS_WAIT || $order->status != AlphaOrder::STATUS_WAIT) {
            return apiError(__('支付状态异常'));
        }

        try {
            $payInfo = $this->getPayInfoFromContract($payOrder);
            if (!$payInfo || empty($payInfo['isPaid'])) {
                return apiError(__('链上未找到支付信息或订单未支付'));
            }
            $this->validatePayment($payOrder, $payInfo);

            DB::beginTransaction();
            AlphaOrderService::payCallback($order, $payOrder, $hash, $payInfo['payTotal']);
            DB::commit();
        } catch (LogicException $e) {
            DB::rollBack();
            return apiError($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('power_done_error', [$e->getMessage()]);
            return apiError(__('服务器异常'));
        }
        return apiSuccess(__('验证成功'));
    }

    // 取消支付
    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sn' => 'required|numeric|digits:19',
        ], ['sn.required' => __('单号不能为空')]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $user = $request->user();
        $sn = $request->input('sn');
        $payOrder = PayOrder::query()->where('sn', $sn)->where('type', PayOrder::TYPE_ALPHA_BUY)->first();
        if (!$payOrder || $payOrder->user_id != $user->id) {
            return apiError(__('支付单不存在'));
        }
        $order = AlphaOrder::getBySn($sn);
        if (!$order) {
            return apiError(__('订单不存在'));
        }
        if ($payOrder->status == PayOrder::STATUS_CANCEL) {
            return apiSuccess(__('订单已取消'));
        }
        try {
            DB::beginTransaction();
            AlphaOrderService::payCancel($order, $payOrder, '用户取消支付');
            DB::commit();
        } catch (LogicException $e) {
            DB::rollBack();
            return apiError($e->getMessage());
        }
        return apiSuccess(__('取消成功'));
    }

    private function getAsset(string $asset_code)
    {
        $chain_id = config('site.asset.chain_id');
        if (!$chain_id) {
            throw new LogicException(__('区块链id未配置'));
        }
        $chain = Chain::getInfoByChainId($chain_id);
        if (!$chain) {
            throw new LogicException(__('区块链信息未配置'));
        }
        $asset = Asset::getPayInfo($asset_code, $chain_id);
        if (!$asset) {
            throw new LogicException(__('资产(XX)未配置', ['asset_code' => $asset_code]));
        }
        $pay_contract = config('site.pay_contract');
        if (!$pay_contract) {
            throw new LogicException(__('支付合约地址未配置'));
        }
        return [$asset, $chain, $pay_contract];
    }

    private function getPayInfoFromContract(PayOrder $payOrder): ?array
    {
        if (!config('app.check.pay')) {
            return [
                'orderId' => $payOrder->id,
                'contractAddress' => $payOrder->token_addr,
                'fromAddress' => $payOrder->pay_addr,
                'toAddress' => $payOrder->dest_addr,
                'payTotal' => $payOrder->amount,
                'payTime' => $payOrder->created_at->timestamp,
                'isPaid' => true,
            ];
        }
        $chain_id = config('site.asset.chain_id');
        $chain = Chain::getInfoByChainId($chain_id);
        $service = new \App\Services\Web3\PaymentContractService([
            'rpc' => $chain->rpc_url,
            'address' => $payOrder->pay_contract,
        ]);
        $result = $service->getPayInfo($payOrder->sn);
        if (!$result) {
            return null;
        }
        if (!empty($result['payTotal'])) {
            $result['payTotal'] = bcdiv($result['payTotal'], pow(10, $payOrder->decimals), $payOrder->decimals);
        }
        return $result;
    }

    private function validatePayment(PayOrder $payOrder, array $payInfo)
    {
        $payTotal = $payInfo['payTotal'];
        $orderTotal = $payOrder->getPayAmount();
        if (bccomp($payTotal, $orderTotal, 6) < 0) {
            throw new LogicException(__('支付金额不匹配'));
        }
        if (strtolower($payInfo['contractAddress']) !== strtolower($payOrder->token_addr)) {
            throw new LogicException(__('合约地址验证失败'));
        }
        if (strtolower($payInfo['fromAddress']) !== strtolower($payOrder->pay_addr)) {
            throw new LogicException(__('付款地址验证失败'));
        }
    }
}
```

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/AlphaOrderTest.php`
Expected: PASS（6 个用例）。

- [ ] **Step 5: 提交**

```bash
git add app/Http/Controllers/Api/PowerController.php routes/api.php tests/Feature/AlphaOrderTest.php
git commit -m "feat: add power API (home/create/orders/exit/done/cancel)"
```

---

## Task 6: OrderPay 扩展（资产包对账分支）

**Files:**
- Modify: `app/Console/Commands/OrderPay.php`（completeOrder/failOrder/timeoutOrder 增加 TYPE_ALPHA_BUY）
- Test: `tests/Feature/AlphaOrderTest.php`（追加对账调用用例）

**Interfaces:**
- Consumes: `AlphaOrder::getBySn`、`AlphaOrderService::payCallback/payFail/payTimeout`、`PayOrder::TYPE_ALPHA_BUY`。

- [ ] **Step 1: 写失败测试（追加到 AlphaOrderTest.php）**

```php
test('order-pay completes an alpha pay order', function () {
    $user = makeAlphaUser();
    config(['app.check.pay' => false]);
    $product = AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = AlphaOrderService::createOrder($user, $product, '1000.000000', $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    $cmd = new \App\Console\Commands\OrderPay();
    $ref = new ReflectionClass($cmd);
    $m = $ref->getMethod('processOrder');
    $m->setAccessible(true);
    $result = $m->invoke($cmd, $payOrder);

    expect($result)->toBe('success');
    expect(AlphaOrder::find($order->id)->status)->toBe(AlphaOrder::STATUS_PAID);
});
```

> 注：该用例依赖 `processOrder` 内 `getPayInfoFromContract` 走 mock 分支（`app.check.pay=false`）。`getAsset` 需要 chain/asset/pay_contract 配置，沿用 Task 5 `beforeEach` 的 DB 准备。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/AlphaOrderTest.php`
Expected: FAIL（`completeOrder` 对 TYPE_ALPHA_BUY 抛"订单类型异常"）。

- [ ] **Step 3: 写实现**

`app/Console/Commands/OrderPay.php` — 将 `completeOrder`、`failOrder`、`timeoutOrder` 三个方法的分支改为同时支持充值 + 资产包：

`completeOrder`（替换方法体）：
```php
    private function completeOrder(PayOrder $order, array $payInfo)
    {
        $hash = $payInfo['hash'] ?? null;
        $payTotal = $payInfo['payTotal'] ?? null;
        if ($order->type == PayOrder::TYPE_RECHARGE) {
            $bus_order = UserRecharge::getBySn($order->sn);
            if ($bus_order) {
                UserRechargeService::payCallback($bus_order, $order, $hash, $payTotal);
            }
        } elseif ($order->type == PayOrder::TYPE_ALPHA_BUY) {
            $bus_order = \App\Models\AlphaOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\AlphaOrderService::payCallback($bus_order, $order, $hash, $payTotal);
            }
        } else {
            throw new \Exception('订单类型异常');
        }
    }
```

`failOrder`（替换方法体）：
```php
    private function failOrder(PayOrder $order, PayFailException $error)
    {
        $fail_reason = '支付校验失败: ' . $error->getMessage();
        if ($order->type == PayOrder::TYPE_RECHARGE) {
            $bus_order = UserRecharge::getBySn($order->sn);
            if ($bus_order) {
                UserRechargeService::payFail($bus_order, $order, $fail_reason);
            }
        } elseif ($order->type == PayOrder::TYPE_ALPHA_BUY) {
            $bus_order = \App\Models\AlphaOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\AlphaOrderService::payFail($bus_order, $order, $fail_reason);
            }
        } else {
            throw new \Exception('订单类型异常');
        }
    }
```

`timeoutOrder`（替换方法体）：
```php
    private function timeoutOrder(PayOrder $order)
    {
        $fail_reason = '超时取消';
        if ($order->type == PayOrder::TYPE_RECHARGE) {
            $bus_order = UserRecharge::getBySn($order->sn);
            if ($bus_order) {
                UserRechargeService::payTimeout($bus_order, $order, $fail_reason);
            }
        } elseif ($order->type == PayOrder::TYPE_ALPHA_BUY) {
            $bus_order = \App\Models\AlphaOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\AlphaOrderService::payTimeout($bus_order, $order, $fail_reason);
            }
        } else {
            throw new \Exception('订单类型异常');
        }
    }
```

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/AlphaOrderTest.php`
Expected: PASS（7 个用例）。

- [ ] **Step 5: 提交**

```bash
git add app/Console/Commands/OrderPay.php tests/Feature/AlphaOrderTest.php
git commit -m "feat: extend OrderPay reconciliation for alpha package purchases"
```

---

## Task 7: 删除旧质押/DAO 节点业务 + 修复保留引用

**Files:**
- Create: `database/migrations/2026_08_31_000006_drop_legacy_stake_node_tables.php`
- Delete: 模型（15 个）、服务（5 个）、命令（2 个）、API 控制器（5 个）、Admin 控制器（11 个）、Admin Repositories（2 个）、迁移（5 个）、`database/seeders/Staking/`、`tests/Feature/NodeCommissionTest.php`
- Modify: `app/Models/InviteReward.php`、`app/Models/FeeSett.php`、`app/Http/Controllers/Api/MyTeamController.php`、`app/Admin/Controllers/Team/UserTreesController.php`、`app/Admin/Controllers/UserDatasController.php`、`app/Admin/routes.php`、`routes/api.php`、`routes/console.php`

**Interfaces:**
- Consumes: 无需前置（清理任务，最终回归由 `php artisan test` 全量验证）。

- [ ] **Step 1: 写 DROP 迁移**

`database/migrations/2026_08_31_000006_drop_legacy_stake_node_tables.php`：
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'stake_orders', 'stake_rewards', 'stake_speeds',
            'machines', 'machine_attrs', 'machine_products', 'machine_product_attrs', 'machine_rates',
            'nodes', 'node_orders', 'node_commissions', 'node_rewards', 'node_reward_logs', 'node_perf_logs',
            'user_power_logs',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // 旧表无需恢复（业务已下线）
    }
};
```

- [ ] **Step 2: 删除文件（用 git rm）**

```bash
git rm app/Models/StakeOrder.php app/Models/StakeReward.php app/Models/StakeSpeeds.php \
       app/Models/Machine.php app/Models/MachineAttr.php app/Models/MachineProduct.php \
       app/Models/MachineProductAttr.php app/Models/MachineRate.php app/Models/Node.php \
       app/Models/NodeOrder.php app/Models/NodeCommission.php app/Models/NodeReward.php \
       app/Models/NodeRewardLog.php app/Models/UserPowerLog.php app/Models/MintSpeed.php \
       app/Services/StakeBuyService.php app/Services/StakeRewardService.php \
       app/Services/NodeBuyService.php app/Services/NodeCommissionService.php app/Services/FeeSettService.php \
       app/Console/Commands/StakeRewardCmd.php app/Console/Commands/FeeSettCmd.php \
       app/Http/Controllers/Api/StakeBuyController.php app/Http/Controllers/Api/StakeOrderController.php \
       app/Http/Controllers/Api/NodeBuyController.php app/Http/Controllers/Api/NodeHomeController.php \
       app/Http/Controllers/Api/StakeHomeV2Controller.php \
       app/Admin/Controllers/MachineProductsController.php app/Admin/Controllers/MachinesController.php \
       app/Admin/Controllers/MachineRatesController.php app/Admin/Controllers/StakeOrdersController.php \
       app/Admin/Controllers/StakeRewardsController.php app/Admin/Controllers/StakeSpeedsController.php \
       app/Admin/Controllers/UserPowerLogsController.php app/Admin/Controllers/NodesController.php \
       app/Admin/Controllers/NodeOrdersController.php app/Admin/Controllers/NodeRewardsController.php \
       app/Admin/Controllers/NodeCommissionsController.php \
       app/Admin/Repositories/MachineProductRepository.php app/Admin/Repositories/MachineRepository.php \
       database/migrations/2026_08_09_000001_create_nodes_table.php \
       database/migrations/2026_08_09_000002_create_node_orders_table.php \
       database/migrations/2026_08_09_000003_create_node_commissions_table.php \
       database/migrations/2026_08_09_000004_update_nodes_data.php \
       database/migrations/2026_08_09_000005_add_node_marketing_wallet_config.php \
       database/seeders/Staking/ClearAllUserDataSeeder.php database/seeders/Staking/UserInitSeeder.php \
       tests/Feature/NodeCommissionTest.php
```
（若 `database/seeders/Staking` 目录因此为空，`rmdir database/seeders/Staking`。）

- [ ] **Step 3: 修复保留文件的引用**

`app/Models/InviteReward.php` — 删除方法：
```php
    public function order()
    {
        return $this->belongsTo(StakeOrder::class, 'order_id');
    }
```

`app/Models/FeeSett.php` — 删除方法：
```php
        return $this->belongsTo(Node::class, 'node_id');
```
（连同所在 `node()` 方法一起删除。）

`app/Http/Controllers/Api/MyTeamController.php` — 删除 `use App\Models\Node;`、`use App\Models\NodeCommission;` 两行 import；删除/替换引用点（第 93 行 `NodeCommission::query()...sum('commission')` 的佣金列，改为不展示或展示"无"；第 95/136/153 行 `Node::NODE_MAP[$userData?->node_id] ?? '无'` 替换为 `'无'`）。具体：
- 删除 `$reward_am = (string)NodeCommission::query()->where('user_id', $user_id)->where('from_user_id', $child->id)->sum('commission');` 这一行及下游对 `$reward_am` 的展示。
- 三处 `$node = Node::NODE_MAP[$userData?->node_id] ?? '无';` → `$node = '无';`

`app/Admin/Controllers/Team/UserTreesController.php` — 删除 `use App\Models\Node;`；第 58 行：
```php
                $node = Node::NODE_MAP[$branch['node_id']] ?? '无';
```
改为：
```php
                $node = '无';
```

`app/Admin/Controllers/UserDatasController.php` — 删除 `use App\Models\Node;`；删除第 27 行的节点显示分支（`return Node::NODE_MAP[$this->node_id] ?? null;` 整段 return 改为 `return null;`），删除第 59 行过滤器：
```php
                $filter->equal('node_id', '节点')->select(Node::NODE_MAP)->width(2);
```

`app/Admin/routes.php` — 删除以下 resource 行：
```php
    $router->resource('levels', 'Team\LevelsController'); // 等级设置  ← 注意：levels 属于子项目3，本计划不删！
```
> 修正：本计划**只删**与质押/节点相关的行：
```php
    $router->resource('machineProducts', 'MachineProductsController');
    $router->resource('machines', 'MachinesController');
    $router->resource('machineRates', 'MachineRatesController');
    $router->resource('stakeOrders', 'StakeOrdersController');
    $router->resource('stakeRewards', 'StakeRewardsController');
    $router->resource('userPowerLogs', 'UserPowerLogsController');
    $router->resource('stakeSpeeds', 'StakeSpeedsController');
    $router->resource('nodes', 'NodesController');
    $router->resource('nodeOrders', 'NodeOrdersController');
    $router->resource('nodeRewards', 'NodeRewardsController');
    $router->resource('nodeCommissions', 'NodeCommissionsController');
```

`routes/api.php` — 删除：
```php
    Route::get('node/home', [...NodeHomeController...]);
    Route::get('node/detail', [...NodeHomeController...]);
    Route::get('stakeV2/home', [...StakeHomeV2Controller...]);
    Route::get('stakeV2/detail', [...StakeHomeV2Controller...]);
    Route::post('node/create', [...NodeBuyController...]);
    Route::get('node/commissions', [...NodeBuyController...]);
    Route::post('stake/create', [...StakeBuyController...]);
    Route::get('stake/orders', [...StakeOrderController...]);
```

`routes/console.php` — 删除整个 `app:fee-sett` Schedule 段（与 Task 4 的质押段替换合并完成）：
```php
// 手续费结算: app:fee-sett 每天执行一次
Schedule::command('app:fee-sett')
    ->withoutOverlapping()
    ->runInBackground()
    ->daily()
    ->name('fee-sett')
    ->appendOutputTo(storage_path('logs/fee-sett.log'));
```

- [ ] **Step 4: 全量跑测试 + 语法检查**

Run: `php artisan test`
Expected: 全部 PASS（旧的 NodeCommissionTest 已删除，新增 alpha 测试通过）。
Run: `php -l` 逐个抽查改动/删除后遗留引用：`grep -rn "StakeOrder\|NodeCommission\|MachineProduct\|NodeRewardLog\|Node::" app/ routes/ database/ --include="*.php" | grep -v vendor`
Expected: 无输出（无残留引用）。

- [ ] **Step 5: 提交**

```bash
git add -A
git commit -m "chore: remove legacy staking/DAO-node business and fix references"
```

---

## Task 8: Admin 后台（产品 + 订单）

**Files:**
- Create: `app/Admin/Controllers/AlphaProductsController.php`
- Create: `app/Admin/Controllers/AlphaOrdersController.php`
- Modify: `app/Admin/routes.php`
- Test: `php artisan test`（全量回归，后台控制器无独立单测，以加载不报错为准）

**Interfaces:**
- Consumes: `AlphaProduct`、`AlphaOrder`、`User`、`AlphaProduct::STATUS_MAP`。
- Produces: 后台路由 `admin/alphaProducts`、`admin/alphaOrders`（resource）。

- [ ] **Step 1: 写 AlphaProductsController**

`app/Admin/Controllers/AlphaProductsController.php`：
```php
<?php

namespace App\Admin\Controllers;

use App\Models\AlphaProduct;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class AlphaProductsController extends AdminController
{
    protected $title = '资产包产品';

    protected function grid()
    {
        $grid = new Grid(new AlphaProduct());
        $grid->model()->orderBy('sort', 'asc');

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '名称');
        $grid->column('tier', '档位')->using(AlphaProduct::TIER_MAP);
        $grid->column('min_amount', '最小申购额')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('max_amount', '最大申购额')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('base_rate', '基础月产率(%)');
        $grid->column('node_rate', '节点加速月产率(%)');
        $grid->column('status', '状态')->using(AlphaProduct::STATUS_MAP)->dot();
        $grid->column('sort', '排序')->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('tier', '档位')->select(AlphaProduct::TIER_MAP);
        });

        return $grid;
    }

    protected function form()
    {
        return Form::make(new AlphaProduct(), function (Form $form) {
            $form->text('name', '名称')->required()->maxLength(50);
            $form->select('tier', '档位')->options(AlphaProduct::TIER_MAP)->required()->rules('in:' . implode(',', array_keys(AlphaProduct::TIER_MAP)));
            $form->decimal('min_amount', '最小申购额')->required()->rules('required|numeric|min:0');
            $form->decimal('max_amount', '最大申购额')->required()->rules('required|numeric|min:0|gte:min_amount');
            $form->decimal('base_rate', '基础月产率(%)')->required()->rules('required|numeric|min:0');
            $form->decimal('node_rate', '节点加速月产率(%)')->default(0)->rules('numeric|min:0');
            $form->switch('status', '状态')->default(AlphaProduct::STATUS_ON);
            $form->number('sort', '排序')->default(0);
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new AlphaProduct(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '名称');
            $show->field('tier', '档位')->using(AlphaProduct::TIER_MAP);
            $show->field('min_amount', '最小申购额');
            $show->field('max_amount', '最大申购额');
            $show->field('base_rate', '基础月产率(%)');
            $show->field('node_rate', '节点加速月产率(%)');
            $show->field('status', '状态')->using(AlphaProduct::STATUS_MAP);
            $show->field('sort', '排序');
        });
    }
}
```

- [ ] **Step 2: 写 AlphaOrdersController**

`app/Admin/Controllers/AlphaOrdersController.php`：
```php
<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\OrderRepository;
use App\Models\AlphaOrder;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class AlphaOrdersController extends AdminController
{
    protected $title = '资产包订单';

    protected function grid()
    {
        $grid = new Grid(new AlphaOrder());
        $grid->model()->orderByDesc('id');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '用户地址')->display(fn ($v) => $v ? substr($v, 0, 10) . '...' : '-');
        $grid->column('sn', '单号')->copyable();
        $grid->column('product.name', '产品');
        $grid->column('amount', '本金')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('reward_rate', '月产率(%)');
        $grid->column('progress', '进度')->display(function () {
            return removeTrailingZeros($this->rel_reward) . ' / ' . removeTrailingZeros($this->quota_power);
        });
        $grid->column('status', '状态')->using(AlphaOrder::STATUS_MAP)->dot();
        $grid->column('trial_end', '试用截止');
        $grid->column('created_at', '创建时间')->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('user_id', '用户ID');
            $filter->equal('sn', '单号');
            $filter->equal('status', '状态')->select(AlphaOrder::STATUS_MAP);
        });

        // 订单只读
        $grid->disableCreateButton();
        $grid->disableEditButton();
        $grid->disableViewButton();
        $grid->disableDeleteButton();

        return $grid;
    }
}
```

> 注：`product.name` / `user.address` 关系列依赖 `AlphaOrder::product()` 与 `user()` 关联（Task 1 已定义）。若 `removeTrailingZeros` 在 Admin 上下文不可用，改用 `\App\helpers` 已全局自动加载（`app/helpers.php` 在 composer autoload files，全局可用）。

- [ ] **Step 3: 注册后台路由**

`app/Admin/routes.php` — 在"质押"区删除旧行后，追加：
```php
    // 算力资产包
    $router->resource('alphaProducts', 'AlphaProductsController'); // 资产包产品
    $router->resource('alphaOrders', 'AlphaOrdersController'); // 资产包订单
```

- [ ] **Step 4: 全量验证**

Run: `php artisan test`
Expected: 全部 PASS。
Run: `php artisan route:list`（确认 `admin/alphaProducts*`、`admin/alphaOrders*` 存在且旧 `node`/`stake`/`machines` 路由消失）。

- [ ] **Step 5: 提交**

```bash
git add app/Admin/Controllers/AlphaProductsController.php app/Admin/Controllers/AlphaOrdersController.php app/Admin/routes.php
git commit -m "feat: add alpha products/orders admin controllers"
```

---

## Self-Review

**1. Spec 覆盖核对**
- 产品/订单/额度槽/每日结算/冻结解冻/首月退出/直付对账/旧业务清理/后台/测试：均有对应 Task（1–8）✅
- 每日产出公式 `amount×rate÷3000`、2.5× 封顶、`alpha_quota_multiple` 配置、`TYPE_ALPHA_*` 常量：Task 1/4 ✅
- "订单完成不回溯冻结"：`creditEarnings` 只作用于入账时点，`sweep` 只在新订单后正向释放，无回扫逻辑 ✅

**2. 占位符扫描**
- 无 TBD/TODO；所有 Task 含可直接运行的测试代码与实现代码 ✅

**3. 类型/签名一致性**
- `AlphaQuotaService::creditEarnings` 返回 `['available'=>string,'frozen'=>string]`，Task 4 用 `array_values(...)` 解构一致 ✅
- `AlphaOrderService::createOrder(User, AlphaProduct, string, array): AlphaOrder` 在 Task 3/5/6 一致 ✅
- `AlphaOrder::STATUS_*`、`PayOrder::TYPE_ALPHA_BUY`、`WalletLog::TYPE_ALPHA_*` 各 Task 引用一致 ✅

---

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-08-31-nox-alpha-power-package.md`. Two execution options:**

**1. Subagent-Driven（推荐）** - 每个 Task 派一个全新 subagent 执行，任务间我做两阶段评审，迭代快、上下文干净。

**2. Inline Execution** - 在当前会话内用 executing-plans 批量执行，带检查点评审。

Which approach?
