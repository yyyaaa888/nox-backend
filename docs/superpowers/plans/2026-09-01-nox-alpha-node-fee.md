# NOX Alpha 子项目 2（算力加速节点 + 提现手续费路由 + 节点池分红）实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 在 nox-backend 上落地 NOX Alpha 子项目 2：单档位永久算力加速节点（链上直付、可多买、按节点数均分节点池分红）、提现手续费路由到节点池（既有手续费机制，`doneCallback` 累加进 `fees`）、持节点期间资产包订单按 `node_rate` 加速、节点本金计入额度槽 S。

**Architecture:** 新增 `node_products`/`node_orders`/`node_fee_allocations`/`node_fee_logs` 表与模型、`NodeBuyService`（仿 `AlphaOrderService`）、`NodeFeeService`（仿 `AlphaRewardService` 的每日结算模式），补齐 `withdraws`/`fees` 迁移（既有表无迁移）。复用 `pay_orders` + `app:order-pay` 链上对账、`AlphaQuotaService` 额度槽、`WalletLog` 账本、`app:alpha-reward` 结算模式。

**Tech Stack:** Laravel 12 / PHP 8.2 / MySQL（测试 sqlite :memory:）/ Redis / dcat-admin / Pest / bcmath。

**Spec:** `docs/superpowers/specs/2026-09-01-nox-alpha-node-fee-design.md`

## Global Constraints

- PHP >= 8.2；所有金额一律 **bcmath 字符串运算**（`bcadd`/`bcsub`/`bcmul`/`bccomp`，账本 scale 6，中间计算 scale 8，或 `bcround2`/`bcfloor2`/`bcceil2` 来自 `app/helpers.php`），禁止 float/double 处理金额。
- 所有可用余额变动必须走 `WalletLog::addLog()`（写 `asset_logs` 账本 + 更新 `user_datas`），禁止直接 `DB::raw` 改 `user_datas` 余额列。额度槽列（`alpha_quota_used`/`alpha_frozen`）由 `AlphaQuotaService` 专管。
- 节点池分红入账走 `AlphaQuotaService::creditEarnings`（计入额度槽 C，超槽冻结）；项目收入走 `WalletLog::addLog`（不入额度槽）。
- 订单 `sn` 与对应 `pay_orders.sn` 一致（`randOrderNo()`，19 位数字）。
- UI/提示文案走 `__('...')`（中文）。
- 遵循现有代码风格：Service 多为静态方法；Admin 控制器继承 `AdminController`（grid()/form() 模式）；API 控制器用 `Validator` + `apiError`/`apiSuccess`。
- 新增 `WalletLog`/`PayOrder` 类型常量必须同步 `TYPE_MAP` 与用户侧 `getTypeMapUser()`/`getTypeMapUserFilter()`（子项目 1 教训）。

---

## 文件结构

**新增**
- `database/migrations/2026_09_01_000001_create_withdraws_table.php`（既有表补迁移，含 `real_am`/`fee`/`fee_rate`）
- `database/migrations/2026_09_01_000002_create_fees_table.php`
- `database/migrations/2026_09_01_000003_create_node_products_table.php`
- `database/migrations/2026_09_01_000004_create_node_orders_table.php`
- `database/migrations/2026_09_01_000005_create_node_fee_allocations_table.php`
- `database/migrations/2026_09_01_000006_create_node_fee_logs_table.php`
- `database/migrations/2026_09_01_000007_insert_node_fee_configs.php`（configs：`alpha_node_fee_rate='90'`、`site_fee_project_wallet=''`）
- `database/migrations/2026_09_01_000008_seed_node_product.php`（node_products 种子）
- `app/Models/NodeProduct.php`
- `app/Models/NodeOrder.php`
- `app/Models/NodeFeeAllocation.php`
- `app/Models/NodeFeeLog.php`
- `app/Services/NodeBuyService.php`
- `app/Services/NodeFeeService.php`
- `app/Console/Commands/NodeFeeCmd.php`
- `app/Http/Controllers/Api/NodeController.php`
- `app/Admin/Controllers/NodeProductsController.php`
- `app/Admin/Controllers/NodeOrdersController.php`
- `app/Admin/Controllers/NodeFeeAllocsController.php`
- 测试：`tests/Feature/NodeOrderTest.php`、`tests/Feature/NodeFeeTest.php`、`tests/Feature/NodeBuyHttpTest.php`

**修改**
- `app/Models/WalletLog.php`（TYPE_NODE_FEE_IN=140、TYPE_NODE_FEE_PROJECT=141 + TYPE_MAP + getTypeMapUser/getTypeMapUserFilter）
- `app/Models/PayOrder.php`（TYPE_NODE_BUY=3 + TYPE_MAP）
- `app/Services/AlphaQuotaService.php`（currentSlot 计入节点本金）
- `app/Services/AlphaRewardService.php`（settleOne 用有效 rate：持节点 → product.node_rate）
- `app/Services/WithdrawOrderService.php`（doneCallback 累加 fee 进 fees）
- `app/Console/Commands/OrderPay.php`（TYPE_NODE_BUY 分支）
- `routes/api.php`（node/* 路由）
- `routes/console.php`（app:node-fee 调度）
- `app/Admin/routes.php`（nodeProducts/nodeOrders/nodeFeeAllocs resource）
- `tests/Pest.php`（共享测试辅助函数，见 Task 2）

---

## Task 1: node 表迁移 + 模型 + 常量

**Files:**
- Create: `database/migrations/2026_09_01_000001_create_withdraws_table.php`
- Create: `database/migrations/2026_09_01_000002_create_fees_table.php`
- Create: `database/migrations/2026_09_01_000003_create_node_products_table.php`
- Create: `database/migrations/2026_09_01_000004_create_node_orders_table.php`
- Create: `database/migrations/2026_09_01_000005_create_node_fee_allocations_table.php`
- Create: `database/migrations/2026_09_01_000006_create_node_fee_logs_table.php`
- Create: `database/migrations/2026_09_01_000007_insert_node_fee_configs.php`
- Create: `database/migrations/2026_09_01_000008_seed_node_product.php`
- Create: `app/Models/NodeProduct.php`
- Create: `app/Models/NodeOrder.php`
- Create: `app/Models/NodeFeeAllocation.php`
- Create: `app/Models/NodeFeeLog.php`
- Modify: `app/Models/WalletLog.php`
- Modify: `app/Models/PayOrder.php`
- Test: `tests/Feature/NodeOrderTest.php`（本任务冒烟测试）

**Interfaces:**
- Produces: 模型 `NodeProduct`（`STATUS_ON=1`、`STATUS_OFF=0`、字段 name/price/status/sort）；`NodeOrder`（`STATUS_WAIT=0`、`STATUS_PAID=1`、`STATUS_CANCEL=2`、`getBySn()`、`getByPk()`、`user()`、`product()`）；`NodeFeeAllocation`（`STATUS_RUNNING=1`、`STATUS_DONE=2`、`STATUS_FAIL=3`，字段 sett_date/total_fee/node_fee_am/node_count/per_share/project_am/remain/status）；`NodeFeeLog`（字段 alloc_id/user_id/order_id/am，唯一 `(alloc_id, order_id)`，`UPDATED_AT = null`）。`WalletLog::TYPE_NODE_FEE_IN=140` / `TYPE_NODE_FEE_PROJECT=141`；`PayOrder::TYPE_NODE_BUY=3`。迁移 000007 写入 `alpha_node_fee_rate='90'`、`site_fee_project_wallet=''`；000008 种子 `node_products` 一条。

- [ ] **Step 1: 写迁移文件**

`2026_09_01_000001_create_withdraws_table.php`（从 sql/nox.sql 忠实重建，**不新增列**）：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraws', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->string('sn', 255)->comment('单号');
            $table->string('symbol', 100)->comment('账户类型');
            $table->decimal('amount', 20, 6)->default(0)->comment('金额');
            $table->decimal('real_am', 20, 6)->default(0)->comment('到手金额');
            $table->decimal('fee', 20, 6)->default(0)->comment('手续费');
            $table->decimal('fee_rate', 20, 2)->default(0)->comment('手续费率');
            $table->decimal('back_am', 20, 6)->default(0)->comment('已返还金额');
            $table->unsignedTinyInteger('status')->default(1)->comment('1=待审核 2=转帐中 3=已完成 4=失败 5=已驳回');
            $table->unsignedTinyInteger('withdraw_mode')->default(1)->comment('1=审核 2=自动 3=合约');
            $table->string('to_addr', 255)->nullable()->comment('用户钱包地址');
            $table->string('signature', 255)->nullable()->comment('签名');
            $table->string('message', 255)->nullable()->comment('消息');
            $table->string('withdraw_contract', 255)->nullable()->comment('提现合约');
            $table->string('project_signer', 255)->nullable()->comment('项目方');
            $table->dateTime('deadline')->nullable()->comment('签名有效时间');
            $table->unsignedInteger('try_num')->default(0)->comment('重试次数');
            $table->unsignedTinyInteger('is_back')->default(0)->comment('是否已返还');
            $table->string('hash', 255)->nullable()->comment('交易凭据');
            $table->dateTime('audit_at')->nullable();
            $table->dateTime('done_at')->nullable();
            $table->dateTime('fail_at')->nullable();
            $table->dateTime('back_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('sn');
            $table->unique('hash');
            $table->index('user_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
```

`2026_09_01_000002_create_fees_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('amount', 20, 6)->unsigned()->comment('金额');
            $table->date('date')->comment('日期');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('date');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
```

`2026_09_01_000003_create_node_products_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('节点名称');
            $table->decimal('price', 20, 6)->unsigned()->comment('价格');
            $table->unsignedTinyInteger('status')->default(1)->comment('1=启用 0=停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('node_products');
    }
};
```

`2026_09_01_000004_create_node_orders_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('product_id')->comment('产品id');
            $table->string('sn', 20)->comment('单号');
            $table->decimal('amount', 20, 6)->unsigned()->comment('本金');
            $table->unsignedTinyInteger('status')->default(0)->comment('0=待支付 1=持有中 2=取消');
            $table->dateTime('pay_at')->nullable();
            $table->dateTime('fail_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('sn');
            $table->index('user_id');
            $table->index('status');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('node_orders');
    }
};
```

`2026_09_01_000005_create_node_fee_allocations_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_fee_allocations', function (Blueprint $table) {
            $table->increments('id');
            $table->date('sett_date')->comment('结算日');
            $table->decimal('total_fee', 20, 6)->unsigned()->default(0)->comment('当日手续费总额');
            $table->decimal('node_fee_am', 20, 6)->unsigned()->default(0)->comment('进节点池金额');
            $table->unsignedInteger('node_count')->default(0)->comment('活跃节点份数');
            $table->decimal('per_share', 20, 6)->unsigned()->default(0)->comment('每份分红额');
            $table->decimal('project_am', 20, 6)->unsigned()->default(0)->comment('项目收入');
            $table->decimal('remain', 20, 6)->unsigned()->default(0)->comment('未分配余量');
            $table->unsignedTinyInteger('status')->default(1)->comment('1=进行中 2=已结束 3=失败');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('sett_date');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('node_fee_allocations');
    }
};
```

`2026_09_01_000006_create_node_fee_logs_table.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_fee_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('alloc_id')->comment('分配记录id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('order_id')->comment('节点订单id');
            $table->decimal('am', 20, 6)->unsigned()->comment('入账额');
            $table->dateTime('created_at')->nullable();
            $table->unique(['alloc_id', 'order_id']);
            $table->index('user_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('node_fee_logs');
    }
};
```

`2026_09_01_000007_insert_node_fee_configs.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('configs')->insertOrIgnore([
            'group' => 'node',
            'key'   => 'alpha_node_fee_rate',
            'value' => '90',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('configs')->insertOrIgnore([
            'group' => 'node',
            'key'   => 'site_fee_project_wallet',
            'value' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function down(): void
    {
        DB::table('configs')->whereIn('key', ['alpha_node_fee_rate', 'site_fee_project_wallet'])->delete();
    }
};
```

`2026_09_01_000008_seed_node_product.php`：
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('node_products')->insertOrIgnore([
            'name' => '算力加速节点',
            'price' => '1000.000000',
            'status' => 1,
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function down(): void
    {
        DB::table('node_products')->where('name', '算力加速节点')->delete();
    }
};
```

- [ ] **Step 2: 写模型文件**

`app/Models/NodeProduct.php`：
```php
<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class NodeProduct extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'node_products';
    protected $guarded = [];

    public const STATUS_ON  = 1;
    public const STATUS_OFF = 0;
    public const STATUS_MAP = [
        self::STATUS_ON  => '启用',
        self::STATUS_OFF => '停用',
    ];

    public function orders()
    {
        return $this->hasMany(NodeOrder::class, 'product_id');
    }
}
```

`app/Models/NodeOrder.php`：
```php
<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class NodeOrder extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'node_orders';
    protected $guarded = [];
    protected $casts = [
        'amount'  => 'decimal:6',
        'pay_at'  => 'datetime',
        'fail_at' => 'datetime',
    ];

    public const STATUS_WAIT   = 0; // 待支付
    public const STATUS_PAID   = 1; // 持有中
    public const STATUS_CANCEL = 2; // 取消

    public const STATUS_MAP = [
        self::STATUS_WAIT   => '待支付',
        self::STATUS_PAID   => '持有中',
        self::STATUS_CANCEL => '取消',
    ];

    public const STATUS_COLOR = [
        self::STATUS_WAIT   => 'default',
        self::STATUS_PAID   => 'success',
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
        return $this->belongsTo(NodeProduct::class, 'product_id');
    }
}
```

`app/Models/NodeFeeAllocation.php`：
```php
<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class NodeFeeAllocation extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'node_fee_allocations';
    protected $guarded = [];

    public const STATUS_RUNNING = 1; // 进行中
    public const STATUS_DONE    = 2; // 已结束
    public const STATUS_FAIL    = 3; // 失败

    public const STATUS_MAP = [
        self::STATUS_RUNNING => '进行中',
        self::STATUS_DONE    => '已结束',
        self::STATUS_FAIL    => '失败',
    ];
}
```

`app/Models/NodeFeeLog.php`：
```php
<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

class NodeFeeLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'node_fee_logs';
    protected $guarded = [];
    public const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(NodeOrder::class, 'order_id');
    }
}
```

- [ ] **Step 3: 加 WalletLog / PayOrder 常量**

`app/Models/WalletLog.php` — 在 `TYPE_ALPHA_UNFREEZE = 132;` 之后加：
```php
    // NOX Alpha 节点
    public const TYPE_NODE_FEE_IN      = 140; // 节点池分红入账
    public const TYPE_NODE_FEE_PROJECT = 141; // 节点池项目收入
```
在 `TYPE_MAP` 末尾加：
```php
        // NOX Alpha 节点
        self::TYPE_NODE_FEE_IN      => '节点池分红',
        self::TYPE_NODE_FEE_PROJECT => '节点池项目收入',
```
在 `getTypeMapUser()` 末尾加：
```php
        self::TYPE_NODE_FEE_IN      => __('节点池分红'),
        self::TYPE_NODE_FEE_PROJECT => __('节点池项目收入'),
```
在 `getTypeMapUserFilter()` 的 USDT 分支末尾加（与 alpha 类型同处）：
```php
            self::TYPE_NODE_FEE_IN      => __('节点池分红'),
            self::TYPE_NODE_FEE_PROJECT => __('节点池项目收入'),
```

`app/Models/PayOrder.php` — 在 `TYPE_ALPHA_BUY = 2;` 后加：
```php
    const TYPE_NODE_BUY = 3; // 节点申购
```
并在 `TYPE_MAP` 加：
```php
        self::TYPE_NODE_BUY => '节点申购',
```

- [ ] **Step 4: 写测试验证常量与模型**

`tests/Feature/NodeOrderTest.php`（冒烟）：
```php
<?php

use App\Models\NodeFeeAllocation;
use App\Models\NodeFeeLog;
use App\Models\NodeOrder;
use App\Models\NodeProduct;
use App\Models\PayOrder;
use App\Models\WalletLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('node constants and models are registered', function () {
    expect(PayOrder::TYPE_NODE_BUY)->toBe(3);
    expect(WalletLog::TYPE_NODE_FEE_IN)->toBe(140);
    expect(WalletLog::TYPE_NODE_FEE_PROJECT)->toBe(141);

    $product = NodeProduct::create([
        'name' => '算力加速节点',
        'price' => '1000.000000',
        'status' => NodeProduct::STATUS_ON,
        'sort' => 1,
    ]);
    expect(NodeProduct::find($product->id)->price)->toBe('1000.000000');

    $order = NodeOrder::create([
        'user_id' => 1,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => '1000.000000',
        'status' => NodeOrder::STATUS_PAID,
    ]);
    expect(NodeOrder::getBySn($order->sn)->id)->toBe($order->id);
    expect(NodeOrder::STATUS_MAP[NodeOrder::STATUS_PAID])->toBe('持有中');

    $alloc = NodeFeeAllocation::create([
        'sett_date' => now()->toDateString(),
        'total_fee' => '100.000000',
        'node_fee_am' => '90.000000',
        'node_count' => 2,
        'per_share' => '45.000000',
        'project_am' => '10.000000',
        'remain' => '0.000000',
        'status' => NodeFeeAllocation::STATUS_DONE,
    ]);
    expect(NodeFeeAllocation::find($alloc->id)->per_share)->toBe('45.000000');

    NodeFeeLog::create([
        'alloc_id' => $alloc->id,
        'user_id' => 1,
        'order_id' => $order->id,
        'am' => '45.000000',
    ]);
    expect(NodeFeeLog::count())->toBe(1);
});
```

- [ ] **Step 5: 跑测试确认通过**

Run: `php artisan test tests/Feature/NodeOrderTest.php`
Expected: PASS。

- [ ] **Step 6: 提交**

```bash
git add database/migrations/2026_09_01_00000* app/Models/Node*.php app/Models/WalletLog.php app/Models/PayOrder.php tests/Feature/NodeOrderTest.php
git commit -m "feat: add node package tables, models and wallet/pay type constants"
```

---

## Task 2: NodeBuyService（申购/对账）

**Files:**
- Create: `app/Services/NodeBuyService.php`
- Modify: `tests/Pest.php`（共享辅助函数 makeNodeUser/makeNodeOrder）
- Test: `tests/Feature/NodeOrderTest.php`（追加用例）

**Interfaces:**
- Consumes: `NodeOrder::STATUS_*`、`NodeProduct`、`PayOrder`（`TYPE_NODE_BUY`）、`WalletLog::SYMBOL_USDT`、`randOrderNo()`。
- Produces: `NodeBuyService::createOrder(User $user, NodeProduct $product, array $pay): NodeOrder`；`payCallback(NodeOrder $o, PayOrder $p, ?string $hash, string $pay_total): void`；`payCancel/payTimeout/payFail(NodeOrder $o, PayOrder $p, ?string $fail_reason = null): void`。**无 exit、无 sweep**（节点永久持有，不产生产出）。

- [ ] **Step 1: 在 tests/Pest.php 加共享辅助函数**

在 `tests/Pest.php` 的 Functions 段（`makeAlphaUser`/`makeAlphaOrder` 之后）加：
```php
function makeNodeUser(): \App\Models\User
{
    $user = \App\Models\User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    \App\Models\UserData::addInfo($user->id);
    return $user;
}

function makeNodeProduct(string $price = '1000.000000'): \App\Models\NodeProduct
{
    return \App\Models\NodeProduct::create([
        'name' => '算力加速节点',
        'price' => $price,
        'status' => \App\Models\NodeProduct::STATUS_ON,
        'sort' => 1,
    ]);
}

function makeNodeOrder(\App\Models\User $user, string $amount = '1000.000000', int $status = \App\Models\NodeOrder::STATUS_PAID): \App\Models\NodeOrder
{
    $product = makeNodeProduct();
    return \App\Models\NodeOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => $amount,
        'status' => $status,
    ]);
}
```

- [ ] **Step 2: 写失败测试（追加到 NodeOrderTest.php）**

```php
use App\Models\NodeProduct;
use App\Models\PayOrder;
use App\Services\NodeBuyService;

test('createOrder builds node order and pay order', function () {
    $user = makeNodeUser();
    $product = makeNodeProduct('1000.000000');
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];

    $order = NodeBuyService::createOrder($user, $product, $pay);

    expect($order->amount)->toBe('1000.000000');
    expect($order->status)->toBe(NodeOrder::STATUS_WAIT);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
    expect($payOrder->type)->toBe(PayOrder::TYPE_NODE_BUY);
    expect($payOrder->order_id)->toBe($order->id);
    expect($payOrder->amount)->toBe('1000.000000');
});

test('payCallback activates node order permanently', function () {
    $user = makeNodeUser();
    $product = makeNodeProduct();
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = NodeBuyService::createOrder($user, $product, $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    NodeBuyService::payCallback($order, $payOrder, '0x' . str_repeat('f', 64), '1000.000000');

    expect(NodeOrder::find($order->id)->status)->toBe(NodeOrder::STATUS_PAID);
    expect(NodeOrder::find($order->id)->pay_at)->not->toBeNull();
});

test('payCancel marks both orders cancelled', function () {
    $user = makeNodeUser();
    $product = makeNodeProduct();
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = NodeBuyService::createOrder($user, $product, $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    NodeBuyService::payCancel($order, $payOrder, '用户取消支付');

    expect(PayOrder::find($payOrder->id)->status)->toBe(PayOrder::STATUS_CANCEL);
    expect(NodeOrder::find($order->id)->status)->toBe(NodeOrder::STATUS_CANCEL);
});
```

> 注：Pest 的 `not->toBeNull()` 是 Carbon 可空断言的正确写法（Carbon 无 `notNull()` 方法）。

- [ ] **Step 3: 跑测试确认失败**

Run: `php artisan test tests/Feature/NodeOrderTest.php`
Expected: FAIL（`NodeBuyService` 不存在）。

- [ ] **Step 4: 写实现**

`app/Services/NodeBuyService.php`：
```php
<?php

namespace App\Services;

use App\Exceptions\LogicException;
use App\Models\NodeOrder;
use App\Models\NodeProduct;
use App\Models\PayOrder;
use App\Models\User;
use App\Models\WalletLog;
use Illuminate\Support\Carbon;

class NodeBuyService
{
    /**
     * 创建节点申购订单 + 支付单（链上直付，不扣内部余额）
     */
    public static function createOrder(User $user, NodeProduct $product, array $pay): NodeOrder
    {
        $sn = randOrderNo();
        $now = Carbon::now();

        $order = new NodeOrder();
        $order->user_id     = $user->id;
        $order->product_id  = $product->id;
        $order->sn          = $sn;
        $order->amount      = $product->price;
        $order->status      = NodeOrder::STATUS_WAIT;
        $order->save();

        $payOrder = new PayOrder();
        $payOrder->user_id      = $user->id;
        $payOrder->order_id     = $order->id;
        $payOrder->sn           = $sn;
        $payOrder->type         = PayOrder::TYPE_NODE_BUY;
        $payOrder->amount       = $product->price;
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
     * 支付确认：节点永久持有（无退出、无产出，不触发 sweep）
     */
    public static function payCallback(NodeOrder $order, PayOrder $payOrder, ?string $hash, string $pay_total)
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
        $re2 = NodeOrder::query()->where('id', $order->id)->where('status', NodeOrder::STATUS_WAIT)->update([
            'status' => NodeOrder::STATUS_PAID,
            'pay_at' => $now,
        ]);
        if (!$re2) {
            throw new LogicException(__('订单状态异常'));
        }
    }

    public static function payCancel(NodeOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_CANCEL, NodeOrder::STATUS_CANCEL, $fail_reason);
    }

    public static function payTimeout(NodeOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_TIMEOUT, NodeOrder::STATUS_CANCEL, $fail_reason);
    }

    public static function payFail(NodeOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_FAIL, NodeOrder::STATUS_CANCEL, $fail_reason);
    }

    private static function markTerminal(NodeOrder $order, PayOrder $payOrder, int $payStatus, int $orderStatus, ?string $fail_reason)
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
        $re2 = NodeOrder::query()->where('id', $order->id)->where('status', NodeOrder::STATUS_WAIT)->update([
            'status'  => $orderStatus,
            'fail_at' => $now,
        ]);
        if (!$re2) {
            throw new LogicException(__('订单状态异常'));
        }
    }
}
```

- [ ] **Step 5: 跑测试确认通过**

Run: `php artisan test tests/Feature/NodeOrderTest.php`
Expected: PASS（冒烟 + 3 个用例）。

- [ ] **Step 6: 提交**

```bash
git add app/Services/NodeBuyService.php tests/Pest.php tests/Feature/NodeOrderTest.php
git commit -m "feat: add NodeBuyService purchase/payback/cancel"
```

---

## Task 3: OrderPay 扩展（节点申购对账分支）

**Files:**
- Modify: `app/Console/Commands/OrderPay.php`（completeOrder/failOrder/timeoutOrder 增加 TYPE_NODE_BUY）
- Test: `tests/Feature/NodeOrderTest.php`（追加对账调用用例）

**Interfaces:**
- Consumes: `NodeOrder::getBySn`、`NodeBuyService::payCallback/payFail/payTimeout`、`PayOrder::TYPE_NODE_BUY`。

- [ ] **Step 1: 写失败测试（追加到 NodeOrderTest.php）**

```php
test('order-pay completes a node pay order', function () {
    config(['app.check.pay' => false]);
    $user = makeNodeUser();
    $product = makeNodeProduct();
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = NodeBuyService::createOrder($user, $product, $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    $cmd = new \App\Console\Commands\OrderPay();
    $cmd->setOutput(new \Symfony\Component\Console\Style\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\BufferedOutput()));
    $ref = new ReflectionClass($cmd);
    $m = $ref->getMethod('processOrder');
    $m->setAccessible(true);
    $result = $m->invoke($cmd, $payOrder);

    expect($result)->toBe('success');
    expect(NodeOrder::find($order->id)->status)->toBe(NodeOrder::STATUS_PAID);
});
```

> 注：`setOutput` 是子项目 1 的既定做法（bare `new OrderPay()` 的 `info()` 会 `writeln on null`）。`app.check.pay=false` 走 mock 分支。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/NodeOrderTest.php`
Expected: FAIL（`completeOrder` 对 TYPE_NODE_BUY 抛「订单类型异常」）。

- [ ] **Step 3: 写实现**

`app/Console/Commands/OrderPay.php` — 在 `completeOrder`、`failOrder`、`timeoutOrder` 三个方法的 `TYPE_ALPHA_BUY` `elseif` 分支之后各加一个 `TYPE_NODE_BUY` 分支：

`completeOrder`（在 alpha 分支后、else 之前加）：
```php
        } elseif ($order->type == PayOrder::TYPE_NODE_BUY) {
            $bus_order = \App\Models\NodeOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\NodeBuyService::payCallback($bus_order, $order, $hash, $payTotal);
            }
        } else {
```

`failOrder`：
```php
        } elseif ($order->type == PayOrder::TYPE_NODE_BUY) {
            $bus_order = \App\Models\NodeOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\NodeBuyService::payFail($bus_order, $order, $fail_reason);
            }
        } else {
```

`timeoutOrder`：
```php
        } elseif ($order->type == PayOrder::TYPE_NODE_BUY) {
            $bus_order = \App\Models\NodeOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\NodeBuyService::payTimeout($bus_order, $order, $fail_reason);
            }
        } else {
```

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/NodeOrderTest.php`
Expected: PASS（全部用例）。

- [ ] **Step 5: 提交**

```bash
git add app/Console/Commands/OrderPay.php tests/Feature/NodeOrderTest.php
git commit -m "feat: extend OrderPay reconciliation for node purchases"
```

---

## Task 4: 额度槽计入节点本金 + 资产包加速结算

**Files:**
- Modify: `app/Services/AlphaQuotaService.php`（currentSlot）
- Modify: `app/Services/AlphaRewardService.php`（settleOne 有效 rate）
- Test: `tests/Feature/NodeOrderTest.php`（追加）

**Interfaces:**
- Consumes: `AlphaOrder::STATUS_PAID`、`NodeOrder::STATUS_PAID`、`AlphaOrder::product()`、`NodeOrder::STATUS_PAID`。
- Produces: `currentSlot` 现返回 `S = alpha_quota_multiple × (Σ alpha PAID + Σ node PAID)`；`settleOne` 用有效 rate。

- [ ] **Step 1: 写失败测试（追加到 NodeOrderTest.php）**

```php
use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\UserData;
use App\Services\AlphaQuotaService;

test('currentSlot includes node principal', function () {
    $user = makeNodeUser();
    makeNodeOrder($user, '1000.000000'); // node 1000
    makeAlphaOrderForNode($user, '1000.000000'); // alpha 1000

    $slot = AlphaQuotaService::currentSlot($user->id);

    expect($slot)->toBe('5000.000000'); // 2.5 × (1000+1000)
});

test('settlement uses node_rate when user holds a node', function () {
    $user = makeNodeUser();
    makeNodeOrder($user, '1000.000000'); // 持有节点
    $product = AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);
    $order = AlphaOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => '1000.000000',
        'reward_rate' => '17.00',
        'quota_power' => '2500.000000',
        'rem_power' => '2500.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);

    (new \App\Services\AlphaRewardService())->run(now()->toDateString());

    $fresh = AlphaOrder::find($order->id);
    expect($fresh->rel_reward)->toBe('6.666667'); // 1000×20/3000=6.66666666→6.666667
});

test('settlement uses snapshot rate when user holds no node', function () {
    $user = makeNodeUser();
    $product = AlphaProduct::create([
        'name' => '基础算力方案', 'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000', 'max_amount' => '30000.000000',
        'base_rate' => '17.00', 'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON, 'sort' => 1,
    ]);
    $order = AlphaOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => '1000.000000',
        'reward_rate' => '17.00',
        'quota_power' => '2500.000000',
        'rem_power' => '2500.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);

    (new \App\Services\AlphaRewardService())->run(now()->toDateString());

    $fresh = AlphaOrder::find($order->id);
    expect($fresh->rel_reward)->toBe('5.666667'); // 1000×17/3000=5.66666666→5.666667
});
```

`makeAlphaOrderForNode`（加到 tests/Pest.php）：
```php
function makeAlphaOrderForNode(\App\Models\User $user, string $amount, string $rate = '17.00'): \App\Models\AlphaOrder
{
    $product = \App\Models\AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => \App\Models\AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'base_rate' => $rate,
        'node_rate' => '20.00',
        'status' => \App\Models\AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    return \App\Models\AlphaOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => $amount,
        'reward_rate' => $rate,
        'quota_power' => bcmul($amount, '2.5', 6),
        'rem_power' => bcmul($amount, '2.5', 6),
        'status' => \App\Models\AlphaOrder::STATUS_PAID,
    ]);
}
```

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/NodeOrderTest.php`
Expected: FAIL（`currentSlot` 未计入节点；`settleOne` 未用 node_rate）。

- [ ] **Step 3: 写实现**

`app/Services/AlphaQuotaService.php` — `currentSlot`：
```php
    public static function currentSlot(int $uid): string
    {
        $alphaSum = AlphaOrder::query()
            ->where('user_id', $uid)
            ->where('status', AlphaOrder::STATUS_PAID)
            ->sum('amount');
        $nodeSum = NodeOrder::query()
            ->where('user_id', $uid)
            ->where('status', NodeOrder::STATUS_PAID)
            ->sum('amount');
        $sum = bcadd($alphaSum, $nodeSum, 6);
        $multiple = getGlobalConfig('alpha_quota_multiple', '2.5');
        return bcadd(bcmul($sum, $multiple, 8), '0', 6);
    }
```
并在文件顶部加 `use App\Models\NodeOrder;`。

`app/Services/AlphaRewardService.php` — `settleOne` 开头（在 `$reward = bcdiv(...)` 之前）：
```php
            // 有效月产率：持节点用户用 product.node_rate，否则用订单快照
            $rate = $order->reward_rate;
            $product = $order->product;
            if ($product && bccomp($product->node_rate, '0', 2) > 0
                && NodeOrder::query()->where('user_id', $order->user_id)->where('status', NodeOrder::STATUS_PAID)->exists()) {
                $rate = $product->node_rate;
            }
            $reward = bcdiv(bcmul($order->amount, $rate, 8), '3000', 8);
```
并把 `AlphaReward::create` 里的 `'rate' => $order->reward_rate,` 改为 `'rate' => $rate,`。
在文件顶部加 `use App\Models\NodeOrder;`。

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/NodeOrderTest.php`
Expected: PASS。再跑 `php artisan test tests/Feature/AlphaRewardTest.php` 确认不回归。

- [ ] **Step 5: 提交**

```bash
git add app/Services/AlphaQuotaService.php app/Services/AlphaRewardService.php tests/Pest.php tests/Feature/NodeOrderTest.php
git commit -m "feat: count node principal in quota slot and accelerate alpha rate for node holders"
```

---

## Task 5: 提现手续费路由（doneCallback 累加进 fees）

**Files:**
- Modify: `app/Services/WithdrawOrderService.php`（doneCallback）
- Test: `tests/Feature/NodeFeeTest.php`

**Interfaces:**
- Consumes: `Withdraw`（fee/fee_rate/real_am/amount 列）、`fees` 表、`Carbon`、`DB`。
- Produces: `doneCallback` 在完成提现时将本次 `fee` 累加进当日 `fees` 行（date 唯一，不存在则插入）。

- [ ] **Step 1: 写失败测试**

`tests/Feature/NodeFeeTest.php`：
```php
<?php

use App\Models\User;
use App\Models\UserData;
use App\Models\Withdraw;
use App\Services\WithdrawOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeFeeUser(): User
{
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    return $user;
}

function makeDoneWithdraw(User $user, string $amount, string $fee, string $fee_rate = '5.00'): Withdraw
{
    $order = WithdrawOrderService::createOrder($user, [
        'symbol' => 'USDT',
        'amount' => $amount,
        'real_am' => bcsub($amount, $fee, 6),
        'fee' => $fee,
        'fee_rate' => $fee_rate,
        'withdraw_mode' => Withdraw::WITHDRAW_MODE_CONTRACT,
    ], [
        'sign' => '0x' . str_repeat('f', 64),
        'message' => 'msg',
        'project_signer' => '0x' . str_repeat('e', 40),
        'withdraw_contract' => '0x' . str_repeat('d', 40),
        'deadline' => now()->addMinutes(5),
    ]);
    return $order;
}

test('doneCallback accumulates fee into daily fees table', function () {
    $user = makeFeeUser();
    $order = makeDoneWithdraw($user, '1000.000000', '50.000000');

    WithdrawOrderService::doneCallback($order, '0x' . str_repeat('a', 64));

    expect(Withdraw::find($order->id)->status)->toBe(Withdraw::STATUS_DONE);
    $feeRow = DB::table('fees')->where('date', now()->toDateString())->first();
    expect($feeRow->amount)->toBe('50.000000');
});

test('doneCallback accumulates multiple withdrawals on same day', function () {
    $user = makeFeeUser();
    $o1 = makeDoneWithdraw($user, '1000.000000', '50.000000');
    $o2 = makeDoneWithdraw($user, '2000.000000', '100.000000');

    WithdrawOrderService::doneCallback($o1, '0x' . str_repeat('a', 64));
    WithdrawOrderService::doneCallback($o2, '0x' . str_repeat('b', 64));

    $feeRow = DB::table('fees')->where('date', now()->toDateString())->first();
    expect($feeRow->amount)->toBe('150.000000');
});

test('doneCallback with zero fee writes no fees row', function () {
    $user = makeFeeUser();
    $order = makeDoneWithdraw($user, '1000.000000', '0.000000');

    WithdrawOrderService::doneCallback($order, '0x' . str_repeat('a', 64));

    expect(DB::table('fees')->where('date', now()->toDateString())->count())->toBe(0);
});
```

> 注：`Withdraw::WITHDRAW_MODE_CONTRACT` = 3；`createOrder` 已存 amount/real_am/fee/fee_rate。测试直接构造 fee，验证 `doneCallback` 的累加。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/NodeFeeTest.php`
Expected: FAIL（`doneCallback` 未写 `fees`）。

- [ ] **Step 3: 写实现**

`app/Services/WithdrawOrderService.php` — `doneCallback` 在 `WalletLog::addLog(...CHANGE_TYPE_OUT...)` 之后追加：
```php
        // 手续费进当日 fees 池（供节点池分红）
        if (bccomp($order->fee, '0', 6) > 0) {
            $date = Carbon::today()->toDateString();
            $re = DB::table('fees')->where('date', $date)->update([
                'amount'     => DB::raw('amount + ' . $order->fee),
                'updated_at' => now(),
            ]);
            if (!$re) {
                DB::table('fees')->insert([
                    'date'       => $date,
                    'amount'     => $order->fee,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
```
在文件顶部确认已有 `use Illuminate\Support\Facades\DB;`（若没有则加）。

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/NodeFeeTest.php`
Expected: PASS（3 个用例）。

- [ ] **Step 5: 提交**

```bash
git add app/Services/WithdrawOrderService.php tests/Feature/NodeFeeTest.php
git commit -m "feat: route withdrawal fees into daily fees pool for node dividends"
```

---

## Task 6: NodeFeeService 每日分红 + 命令 + 调度

**Files:**
- Create: `app/Services/NodeFeeService.php`
- Create: `app/Console/Commands/NodeFeeCmd.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/NodeFeeTest.php`（追加）

**Interfaces:**
- Consumes: `NodeOrder::STATUS_PAID`、`AlphaQuotaService::creditEarnings`、`WalletLog::TYPE_NODE_FEE_IN`/`TYPE_NODE_FEE_PROJECT`、`getGlobalConfig('alpha_node_fee_rate','90')`、`getGlobalConfig('site_fee_project_wallet','')`、`fees` 表。
- Produces: `NodeFeeService::run(string $settle_date, bool $skip=false): bool`；命令 `app:node-fee {date?} {--skip}`。

- [ ] **Step 1: 写失败测试（追加到 NodeFeeTest.php）**

```php
use App\Models\NodeFeeAllocation;
use App\Models\NodeFeeLog;
use App\Models\NodeOrder;
use App\Models\User;
use App\Services\AlphaQuotaService;
use App\Services\NodeFeeService;

function makeNodeFeeUser(): User
{
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    return $user;
}

test('node fee distributes per share to node holders', function () {
    $u1 = makeNodeFeeUser();
    $u2 = makeNodeFeeUser();
    makeNodeOrder($u1, '1000.000000');
    makeNodeOrder($u2, '1000.000000');
    DB::table('fees')->insert([
        'date' => now()->toDateString(),
        'amount' => '100.000000',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $ok = (new NodeFeeService())->run(now()->toDateString());

    expect($ok)->toBeTrue();
    $alloc = NodeFeeAllocation::query()->where('settle_date', now()->toDateString())->first();
    expect($alloc->node_fee_am)->toBe('90.000000'); // 100 × 90%
    expect($alloc->node_count)->toBe(2);
    expect($alloc->per_share)->toBe('45.000000');
    expect(UserData::getInfo($u1->id)->current_usdt)->toBe('45.000000');
    expect(UserData::getInfo($u2->id)->current_usdt)->toBe('45.000000');
    expect(NodeFeeLog::count())->toBe(2);
});

test('user with two nodes gets two shares', function () {
    $u1 = makeNodeFeeUser();
    makeNodeOrder($u1, '1000.000000');
    makeNodeOrder($u1, '1000.000000'); // 第二个节点
    DB::table('fees')->insert([
        'date' => now()->toDateString(),
        'amount' => '100.000000',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (new NodeFeeService())->run(now()->toDateString());

    $alloc = NodeFeeAllocation::query()->where('settle_date', now()->toDateString())->first();
    expect($alloc->node_count)->toBe(2);
    expect(UserData::getInfo($u1->id)->current_usdt)->toBe('90.000000'); // 45 × 2
    expect(NodeFeeLog::count())->toBe(2); // 每节点一行
});

test('node fee settlement is idempotent', function () {
    $u1 = makeNodeFeeUser();
    makeNodeOrder($u1, '1000.000000');
    DB::table('fees')->insert([
        'date' => now()->toDateString(),
        'amount' => '100.000000',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (new NodeFeeService())->run(now()->toDateString());
    (new NodeFeeService())->run(now()->toDateString());

    expect(NodeFeeAllocation::query()->where('settle_date', now()->toDateString())->count())->toBe(1);
    expect(NodeFeeLog::count())->toBe(1);
    expect(UserData::getInfo($u1->id)->current_usdt)->toBe('90.000000');
});

test('no node holders leaves pool in remain', function () {
    DB::table('fees')->insert([
        'date' => now()->toDateString(),
        'amount' => '100.000000',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (new NodeFeeService())->run(now()->toDateString());

    $alloc = NodeFeeAllocation::query()->where('settle_date', now()->toDateString())->first();
    expect($alloc->node_fee_am)->toBe('90.000000');
    expect($alloc->node_count)->toBe(0);
    expect($alloc->remain)->toBe('90.000000');
});

test('project wallet receives project share', function () {
    $projectUser = User::factory()->create(['status' => 1, 'invited_uid' => 0, 'address' => '0x' . str_repeat('c', 40)]);
    UserData::addInfo($projectUser->id);
    DB::table('configs')->where('key', 'site_fee_project_wallet')->delete();
    DB::table('configs')->insert([
        'group' => 'node', 'key' => 'site_fee_project_wallet', 'value' => '0x' . str_repeat('c', 40),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    Cache::flush();
    $u1 = makeNodeFeeUser();
    makeNodeOrder($u1, '1000.000000');
    DB::table('fees')->insert([
        'date' => now()->toDateString(),
        'amount' => '100.000000',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (new NodeFeeService())->run(now()->toDateString());

    expect(UserData::getInfo($projectUser->id)->current_usdt)->toBe('10.000000'); // 100 - 90
    $alloc = NodeFeeAllocation::query()->where('settle_date', now()->toDateString())->first();
    expect($alloc->project_am)->toBe('10.000000');
    expect($alloc->remain)->toBe('0.000000');
});
```

> 注：`site_fee_project_wallet` 是 configs 表的值，`getGlobalConfig` 读 configs 并走 getTableCache——测试里改 configs 行后必须 `Cache::flush()`。文件顶部 `use Illuminate\Support\Facades\Cache;` 需包含。`NodeFeeTest.php` 的 `makeNodeFeeUser`/`makeNodeOrder` 依赖 tests/Pest.php 的共享辅助函数（Task 2 已建）。

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/NodeFeeTest.php`
Expected: FAIL（`NodeFeeService` 不存在）。

- [ ] **Step 3: 写实现**

`app/Services/NodeFeeService.php`：
```php
<?php

namespace App\Services;

use App\Models\NodeFeeAllocation;
use App\Models\NodeFeeLog;
use App\Models\NodeOrder;
use App\Models\User;
use App\Models\WalletLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NodeFeeService
{
    public function run(string $settle_date, bool $skip = false): bool
    {
        $lock = Cache::lock('node_fee_service', 1800);
        if (!$lock->get()) {
            throw new \Exception('任务运行中，请稍后再试');
        }

        if (!$skip && NodeFeeAllocation::query()
            ->where('settle_date', $settle_date)
            ->where('status', NodeFeeAllocation::STATUS_DONE)
            ->exists()) {
            $lock->release();
            return true;
        }

        $failures = 0;
        try {
            $fee = DB::table('fees')->where('date', $settle_date)->value('amount');
            if (!$fee || bccomp($fee, '0', 6) <= 0) {
                $lock->release();
                return true;
            }

            $alloc = NodeFeeAllocation::query()->where('settle_date', $settle_date)->first();
            if (!$alloc) {
                $alloc = NodeFeeAllocation::create([
                    'settle_date' => $settle_date,
                    'total_fee'   => $fee,
                    'node_fee_am' => '0.000000',
                    'node_count'  => 0,
                    'per_share'   => '0.000000',
                    'project_am'  => '0.000000',
                    'remain'      => '0.000000',
                    'status'      => NodeFeeAllocation::STATUS_RUNNING,
                ]);
            }

            $node_fee_rate = getGlobalConfig('alpha_node_fee_rate', '90');
            $node_fee_am = bcround2(bcdiv(bcmul($fee, $node_fee_rate, 8), '100', 8));
            $project_am = bcsub($fee, $node_fee_am, 6);

            $counts = [];
            NodeOrder::query()->where('status', NodeOrder::STATUS_PAID)->orderBy('user_id')->each(function (NodeOrder $o) use (&$counts) {
                $counts[$o->user_id] = ($counts[$o->user_id] ?? 0) + 1;
            });
            $node_count = array_sum($counts);

            $per_share = '0.000000';
            $remain = '0.000000';
            if ($node_count > 0) {
                $per_share = bcfloor2(bcdiv($node_fee_am, (string) $node_count, 8));
                $remain = bcsub($node_fee_am, bcmul($per_share, (string) $node_count, 8), 6);
            } else {
                $remain = $node_fee_am;
            }

            foreach ($counts as $uid => $cnt) {
                if (NodeFeeLog::query()->where('alloc_id', $alloc->id)->where('user_id', $uid)->exists()) {
                    continue;
                }
                try {
                    DB::transaction(function () use ($alloc, $uid, $cnt, $per_share) {
                        $user_share = bcmul($per_share, (string) $cnt, 8);
                        if (bccomp($user_share, '0', 6) <= 0) {
                            return;
                        }
                        AlphaQuotaService::creditEarnings($uid, bcround2($user_share), WalletLog::TYPE_NODE_FEE_IN);
                        $orderIds = NodeOrder::query()->where('user_id', $uid)->where('status', NodeOrder::STATUS_PAID)->pluck('id');
                        foreach ($orderIds as $oid) {
                            NodeFeeLog::create([
                                'alloc_id' => $alloc->id,
                                'user_id'  => $uid,
                                'order_id' => $oid,
                                'am'       => $per_share,
                            ]);
                        }
                    });
                } catch (\Throwable $e) {
                    Log::error('node_fee_credit_error', [$uid, $settle_date, $e->getMessage()]);
                    $failures++;
                }
            }

            // 项目收入：site_fee_project_wallet 对应某用户 → WalletLog 直接入账（不走额度槽）
            $project_done = false;
            if (bccomp($project_am, '0', 6) > 0) {
                $project_wallet = getGlobalConfig('site_fee_project_wallet', '');
                if ($project_wallet) {
                    $pu = User::query()->where('address', $project_wallet)->first();
                    if ($pu) {
                        WalletLog::addLog($pu->id, $project_am, WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_NODE_FEE_PROJECT);
                        $project_done = true;
                    }
                }
            }
            if (!$project_done) {
                $remain = bcadd($remain, $project_am, 6);
            }

            $alloc->update([
                'node_fee_am' => $node_fee_am,
                'node_count'  => $node_count,
                'per_share'   => $per_share,
                'project_am'  => $project_done ? $project_am : '0.000000',
                'remain'      => $remain,
                'status'      => NodeFeeAllocation::STATUS_DONE,
            ]);
        } catch (\Throwable $e) {
            Log::error('node_fee_settle_error', [$settle_date, $e->getMessage(), $e->getTraceAsString()]);
            $lock->release();
            return false;
        }

        $lock->release();
        return $failures === 0;
    }
}
```

`app/Console/Commands/NodeFeeCmd.php`：
```php
<?php

namespace App\Console\Commands;

use App\Services\NodeFeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NodeFeeCmd extends Command
{
    protected $signature = 'app:node-fee {date?} {--skip}';

    protected $description = '节点池每日分红结算';

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
            $service = new NodeFeeService();
            return $service->run($inputDate, $skip ? true : false) ? 0 : 1;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
```

`routes/console.php` — 在 alpha-reward 调度段之后加：
```php
// 节点池分红: app:node-fee 每天执行一次
Schedule::command('app:node-fee')
    ->withoutOverlapping()
    ->runInBackground()
    ->dailyAt('00:30')
    ->name('node-fee')
    ->appendOutputTo(storage_path('logs/node-fee.log'));
```

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/NodeFeeTest.php`
Expected: PASS（3 个 Task5 + 5 个 Task6 用例）。

- [ ] **Step 5: 提交**

```bash
git add app/Services/NodeFeeService.php app/Console/Commands/NodeFeeCmd.php routes/console.php tests/Feature/NodeFeeTest.php
git commit -m "feat: add daily node pool dividend settlement command and schedule"
```

---

## Task 7: NodeController（前台 API）+ 路由

**Files:**
- Create: `app/Http/Controllers/Api/NodeController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/NodeBuyHttpTest.php`

**Interfaces:**
- Consumes: `NodeProduct`、`NodeBuyService::createOrder`、`Asset::getPayInfo`、`Chain::getInfoByChainId`。
- Produces: 路由 `GET node/home`、`POST node/create`、`GET node/orders`（auth:sanctum + checkSign + language 组内）。

- [ ] **Step 1: 写失败测试**

`tests/Feature/NodeBuyHttpTest.php`：
```php
<?php

use App\Models\NodeOrder;
use App\Models\NodeProduct;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['site.asset.chain_id' => '97']);
    config(['site.pay_contract' => '0x' . str_repeat('a', 40)]);
    config(['app.check.pay' => true]);
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

test('node/create returns pay info without debiting internal balance', function () {
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    Sanctum::actingAs($user);
    NodeProduct::create([
        'name' => '算力加速节点', 'price' => '1000.000000',
        'status' => NodeProduct::STATUS_ON, 'sort' => 1,
    ]);

    $resp = $this->postJson('/api/node/create', ['product_id' => 1]);

    $resp->assertJson(['code' => 200]);
    $order = NodeOrder::first();
    expect($order->status)->toBe(NodeOrder::STATUS_WAIT);
    expect(UserData::getInfo($user->id)->current_usdt)->toBe('0.000000');
    expect($resp->json('data.sn'))->toBe($order->sn);
});

test('node/create rejects disabled product', function () {
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    Sanctum::actingAs($user);
    NodeProduct::create([
        'name' => '下架节点', 'price' => '1000.000000',
        'status' => NodeProduct::STATUS_OFF, 'sort' => 1,
    ]);

    $resp = $this->postJson('/api/node/create', ['product_id' => 1]);

    $resp->assertJson(['code' => 400]);
    expect(NodeOrder::count())->toBe(0);
});
```

- [ ] **Step 2: 跑测试确认失败**

Run: `php artisan test tests/Feature/NodeBuyHttpTest.php`
Expected: FAIL（路由 `node/create` 404）。

- [ ] **Step 3: 写实现**

`routes/api.php` — 在 `power/*` 路由之后加：
```php
    // 算力加速节点
    Route::get('node/home', [\App\Http\Controllers\Api\NodeController::class, 'home']); // 节点首页
    Route::post('node/create', [\App\Http\Controllers\Api\NodeController::class, 'create']); // 申购
    Route::get('node/orders', [\App\Http\Controllers\Api\NodeController::class, 'orders']); // 我的节点
```

`app/Http/Controllers/Api/NodeController.php`：
```php
<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Chain;
use App\Models\NodeOrder;
use App\Models\NodeProduct;
use App\Models\PayOrder;
use App\Models\UserData;
use App\Services\NodeBuyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NodeController extends Controller
{
    const CACHE_KEY = 'user_node_';

    // 节点首页：产品 + 我的节点概览
    public function home(Request $request)
    {
        $user = $request->user();
        $orders = NodeOrder::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (NodeOrder $o) {
                return [
                    'id' => $o->id,
                    'sn' => $o->sn,
                    'amount' => $o->amount,
                    'status' => $o->status,
                    'status_name' => NodeOrder::STATUS_MAP[$o->status],
                    'pay_at' => $o->pay_at?->toDateTimeString(),
                    'created_at' => $o->created_at?->toDateTimeString(),
                ];
            });

        return apiSuccess([
            'products' => NodeProduct::query()->where('status', NodeProduct::STATUS_ON)->orderBy('sort')->get(),
            'my_nodes' => [
                'count' => NodeOrder::query()->where('user_id', $user->id)->where('status', NodeOrder::STATUS_PAID)->count(),
            ],
            'orders' => $orders,
        ]);
    }

    // 申购：创建节点订单 + 支付单
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
        ], [
            'product_id.required' => __('节点不能为空'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $product = NodeProduct::query()->where('id', $request->input('product_id'))->where('status', NodeProduct::STATUS_ON)->first();
        if (!$product) {
            return apiError(__('节点不存在或已停用'));
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
            return apiError($e->getMessage());
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
            $order = NodeBuyService::createOrder($user, $product, $pay);
            if (!config('app.check.pay')) {
                $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
                NodeBuyService::payCallback($order, $payOrder, null, $order->amount);
            }
        } catch (LogicException $e) {
            DB::rollBack();
            $lock->release();
            return apiError($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            $lock->release();
            Log::error('node_create_error', [$e->getMessage(), $e->getTraceAsString()]);
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

    // 我的节点订单
    public function orders(Request $request)
    {
        $user = $request->user();
        $page = NodeOrder::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(20);

        return apiPage($page);
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
}
```

- [ ] **Step 4: 跑测试确认通过**

Run: `php artisan test tests/Feature/NodeBuyHttpTest.php`
Expected: PASS（2 个用例）。再跑 `php artisan test` 全量确认无回归。

- [ ] **Step 5: 提交**

```bash
git add app/Http/Controllers/Api/NodeController.php routes/api.php tests/Feature/NodeBuyHttpTest.php
git commit -m "feat: add node API (home/create/orders)"
```

---

## Task 8: Admin 后台（节点产品 + 订单 + 分红分配）

**Files:**
- Create: `app/Admin/Controllers/NodeProductsController.php`
- Create: `app/Admin/Controllers/NodeOrdersController.php`
- Create: `app/Admin/Controllers/NodeFeeAllocsController.php`
- Modify: `app/Admin/routes.php`
- Test: `php artisan test` 全量回归 + `php artisan route:list`

**Interfaces:**
- Consumes: `NodeProduct`、`NodeOrder`、`NodeFeeAllocation`、`User`。

- [ ] **Step 1: 写 NodeProductsController**

`app/Admin/Controllers/NodeProductsController.php`：
```php
<?php

namespace App\Admin\Controllers;

use App\Models\NodeProduct;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class NodeProductsController extends AdminController
{
    protected $title = '节点产品';

    protected function grid()
    {
        $grid = new Grid(new NodeProduct());
        $grid->model()->orderBy('sort', 'asc');

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '名称');
        $grid->column('price', '价格')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('status', '状态')->using(NodeProduct::STATUS_MAP)->dot();
        $grid->column('sort', '排序')->sortable();

        return $grid;
    }

    protected function form()
    {
        return Form::make(new NodeProduct(), function (Form $form) {
            $form->text('name', '名称')->required()->maxLength(50);
            $form->decimal('price', '价格')->required()->rules('required|numeric|min:0');
            $form->switch('status', '状态')->default(NodeProduct::STATUS_ON);
            $form->number('sort', '排序')->default(0);
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new NodeProduct(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '名称');
            $show->field('price', '价格');
            $show->field('status', '状态')->using(NodeProduct::STATUS_MAP);
            $show->field('sort', '排序');
        });
    }
}
```

- [ ] **Step 2: 写 NodeOrdersController**

`app/Admin/Controllers/NodeOrdersController.php`：
```php
<?php

namespace App\Admin\Controllers;

use App\Models\NodeOrder;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class NodeOrdersController extends AdminController
{
    protected $title = '节点订单';

    protected function grid()
    {
        $grid = new Grid(new NodeOrder());
        $grid->model()->orderByDesc('id');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '用户地址')->display(fn ($v) => $v ? substr($v, 0, 10) . '...' : '-');
        $grid->column('sn', '单号')->copyable();
        $grid->column('product.name', '产品');
        $grid->column('amount', '本金')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('status', '状态')->using(NodeOrder::STATUS_MAP)->dot();
        $grid->column('pay_at', '支付时间');
        $grid->column('created_at', '创建时间')->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('user_id', '用户ID');
            $filter->equal('sn', '单号');
            $filter->equal('status', '状态')->select(NodeOrder::STATUS_MAP);
        });

        $grid->disableCreateButton();
        $grid->disableEditButton();
        $grid->disableViewButton();
        $grid->disableDeleteButton();

        return $grid;
    }
}
```

- [ ] **Step 3: 写 NodeFeeAllocsController**

`app/Admin/Controllers/NodeFeeAllocsController.php`：
```php
<?php

namespace App\Admin\Controllers;

use App\Models\NodeFeeAllocation;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class NodeFeeAllocsController extends AdminController
{
    protected $title = '节点池分红';

    protected function grid()
    {
        $grid = new Grid(new NodeFeeAllocation());
        $grid->model()->orderByDesc('sett_date');

        $grid->column('id', 'ID')->sortable();
        $grid->column('sett_date', '结算日')->sortable();
        $grid->column('total_fee', '手续费总额')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('node_fee_am', '进节点池')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('node_count', '节点份数');
        $grid->column('per_share', '每份分红')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('project_am', '项目收入')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('remain', '未分配')->display(fn ($v) => removeTrailingZeros($v));
        $grid->column('status', '状态')->using(NodeFeeAllocation::STATUS_MAP)->dot();

        $grid->disableCreateButton();
        $grid->disableEditButton();
        $grid->disableViewButton();
        $grid->disableDeleteButton();

        return $grid;
    }
}
```

- [ ] **Step 4: 注册后台路由**

`app/Admin/routes.php` — 在 `alphaOrders` resource 之后加：
```php
    // 算力加速节点
    $router->resource('nodeProducts', 'NodeProductsController'); // 节点产品
    $router->resource('nodeOrders', 'NodeOrdersController'); // 节点订单
    $router->resource('nodeFeeAllocs', 'NodeFeeAllocsController'); // 节点池分红
```

- [ ] **Step 5: 全量验证**

Run: `php artisan test`
Expected: 全部 PASS。
Run: `php artisan route:list`
Expected: `admin/nodeProducts*`、`admin/nodeOrders*`、`admin/nodeFeeAllocs*` 存在。

- [ ] **Step 6: 提交**

```bash
git add app/Admin/Controllers/NodeProductsController.php app/Admin/Controllers/NodeOrdersController.php app/Admin/Controllers/NodeFeeAllocsController.php app/Admin/routes.php
git commit -m "feat: add node products/orders/fee-alloc admin controllers"
```

---

## Self-Review

**1. Spec 覆盖核对**
- 节点产品/订单/申购/对账：Task 1/2/3/7 ✅
- 提现手续费路由（doneCallback 累加 fees）：Task 5 ✅（机制已存在，未重复实现）
- 节点池每日分红（按份数均分/幂等/项目收入/remain）：Task 6 ✅
- 额度槽计入节点本金：Task 4 ✅
- 持节点加速（node_rate 覆盖快照）：Task 4 ✅
- Admin 三个控制器：Task 8 ✅
- 账本类型（TYPE_NODE_FEE_IN=140/PROJECT=141、TYPE_NODE_BUY=3）+ 用户侧 map：Task 1 ✅

**2. 占位符扫描**
- 无 TBD/TODO；所有 Task 含可运行测试代码与实现代码。Task 2 中误粘的 `NodeProduct` 内容已用「更正说明」标注，实际文件以最下方代码块为准（实现时若发现 NodeBuyService.php 内容不符，以 spec 为准）。

**3. 类型/签名一致性**
- `NodeBuyService::createOrder(User, NodeProduct, array): NodeOrder` 在 Task 2/3/7 一致 ✅
- `NodeFeeService::run(string, bool): bool` 在 Task 6 一致 ✅
- `NodeOrder::STATUS_*`、`PayOrder::TYPE_NODE_BUY`、`WalletLog::TYPE_NODE_FEE_*` 各 Task 引用一致 ✅
- `currentSlot` 返回字符串、`creditEarnings` 返回 `['available','frozen']` 与子项目 1 一致 ✅

**4. 已知风险/注意**
- `withdraws`/`fees` 迁移对 dump 导入的既有库会「表已存在」（无新列，可跳过）；测试与 fresh 环境用迁移。
- `getGlobalConfig` 走 getTableCache，测试插入 configs 后需 `Cache::flush()`（Task 6 project-wallet 测试已前置）。
- 项目收入入账（`TYPE_NODE_FEE_PROJECT`）与节点分红入账（`TYPE_NODE_FEE_IN`）均需在 Task 1 加入用户侧 `getTypeMapUser()`/`getTypeMapUserFilter()`（子项目 1 教训）。
- `settleOne` 每订单查 product + 节点存在性；`chunkById(100)` 下可接受，量级大时可后续批量优化。

---

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-09-01-nox-alpha-node-fee.md`. Two execution options:**

**1. Subagent-Driven（推荐）** - 每个 Task 派一个全新 subagent 执行，任务间我做两阶段评审，迭代快、上下文干净。

**2. Inline Execution** - 在当前会话内用 executing-plans 批量执行，带检查点评审。

Which approach?
