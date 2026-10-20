# NOX Alpha 子项目 2：算力加速节点 + 提现手续费 + 节点池分红 — 设计文档

日期：2026-09-01
前置：子项目 1（算力资产包 + 额度槽）已上线（master `79ea996`）。本子项目建立在其上，复用 `alpha_products.node_rate` 预留字段、`AlphaQuotaService` 额度槽、`WalletLog` 账本、`pay_orders` 链上对账、`app:alpha-reward` 结算模式。**提现手续费机制已存在于既有代码**（按 `site_w_fee_rate_*` 配置计算 `fee`/`real_am`），本子项目将其**路由到节点池**作为分红来源。

范围：算力加速节点（单档位、永久、链上直付、可多买）、提现 5% 手续费、节点池每日分红。等级 / 推广团队收益 / 多语言仍属后续子项目。

## 1. 背景与目标

- 用户可购买**算力加速节点**（单档位，一次性永久持有）：持有节点使其全部进行中资产包订单按月产率 `node_rate` 结算（加速）；节点本金计入用户额度槽 S（放大收益空间）；节点本金不可退。
- 提现收取 5% 手续费（到账扣 5%），其中 **90% 进节点池**、10% 为项目收入。
- 节点池每日按「每个节点 = 1 份」均分给节点持有人，分红经 `creditEarnings` 计入额度槽（与资产包静态收益同一记账入口，遵守刚性封顶）。

## 2. 已确认的业务规则

| 规则 | 内容 |
|---|---|
| 节点档位 | 单档位固定节点，`node_products` 表一条产品，价格后台可配 |
| 节点获取 | 链上 USDT 直付购买（复用 `pay_orders` + `app:order-pay` 对账），内部余额不扣 |
| 节点生命周期 | 永久有效、本金**不可退**；支付成功即永久持有；可多买（每节点独立订单） |
| 节点加速 | 持有 ≥1 个有效节点的用户，其**全部进行中**资产包订单按对应产品 `node_rate` 结算；不持有则按订单快照 `reward_rate`（= 申购时 `base_rate`）。`node_rate = 0` 表示无加速 |
| 加速与封顶 | 订单 2.5× 静态封顶仍按**订单自身本金**计算；加速只提高日产出、不改变封顶额 |
| 额度槽 | `S = 2.5 × (Σ 进行中资产包订单本金 + Σ 持有中节点订单本金)`（子项目 1 设计文档「节点金额后续子项目计入」在此落地） |
| 节点分红 | 每日手续费池 × `alpha_node_fee_rate`(90%) ÷ 活跃节点份数，每份均分；同一用户持 N 节点得 N 份 |
| 分红入账 | 经 `AlphaQuotaService::creditEarnings`（计入 C，超槽冻结），不新增独立 2.5× 封顶（受额度槽 S 约束） |
| 提现手续费 | **机制已存在**（`WithdrawController` 按配置 `site_w_fee_rate_usdt`/`site_w_fee_rate_dtp` 计算 `fee`，`real_am = amount − fee`，签名按 `real_am`；`withdraws` 表含 `real_am`/`fee`/`fee_rate` 列）。子项目2 新增：`doneCallback` 将本次 `fee` **累加进当日 `fees` 行**（date 唯一）。启用 5% = 后台将费率配置设为 5（运营动作，非代码） |
| 手续费去向 | `alpha_node_fee_rate`(90%) 进节点池按节点均分；剩余(10%) 为项目收入，优先进 `site_fee_project_wallet`（若对应某用户则 `WalletLog` 入账），否则留在 `node_fee_allocations.remain` |
| 结算节奏 | 节点分红每日结算（命令 `app:node-fee`，daily 00:30，错开资产包 00:20） |
| 幂等 | 分红按 `(sett_date)` 唯一；结算复用 `Cache::lock` + `chunkById` + 幂等模式 |

## 3. 数据模型

### 3.1 新增表 `node_products`（节点产品）
| 字段 | 类型 | 说明 |
|---|---|---|
| id | int PK | |
| name | string | 名称 |
| price | decimal(20,6) unsigned | 节点价格（本金） |
| status | tinyint | 1=启用 0=停用 |
| sort | int | 排序 |
| created_at / updated_at | datetime | |

种子：一条「算力加速节点」（价格由后台配置，如 1000 USDT）。

### 3.2 新增表 `node_orders`（节点订单）
| 字段 | 类型 | 说明 |
|---|---|---|
| id | int PK | |
| user_id | int | 用户 |
| product_id | int | 产品 |
| sn | varchar(20) UNIQUE | 单号（= `pay_orders.sn`） |
| amount | decimal(20,6) unsigned | 本金 |
| status | tinyint | 0=待支付 1=持有中 2=取消 |
| pay_at / fail_at | datetime nullable | 支付 / 取消时间 |
| created_at / updated_at | datetime | |

索引：`user_id`、`status`。无 done/exit（永久持有）。

### 3.3 新增表 `node_fee_allocations`（每日分红分配记录，幂等）
| 字段 | 类型 | 说明 |
|---|---|---|
| id | int PK | |
| sett_date | date UNIQUE | 结算日 |
| total_fee | decimal(20,6) | 当日手续费总额（来自 `fees`） |
| node_fee_am | decimal(20,6) | 进节点池金额（= total_fee × node_fee_rate%） |
| node_count | int | 活跃节点份数 |
| per_share | decimal(20,6) | 每份分红额 |
| project_am | decimal(20,6) | 项目收入（= total_fee − node_fee_am） |
| remain | decimal(20,6) | 未分配余量（舍入 / 项目钱包不可达） |
| status | tinyint | 1=进行中 2=已结束 3=失败 |
| created_at / updated_at | datetime | |

### 3.4 新增表 `node_fee_logs`（每节点入账记录）
| 字段 | 类型 | 说明 |
|---|---|---|
| id | int PK | |
| alloc_id | int | 对应 `node_fee_allocations.id` |
| user_id | int | |
| order_id | int | 节点订单 id（每节点一份） |
| am | decimal(20,6) | 本次入账额 |
| created_at | datetime | |

唯一约束：`(alloc_id, order_id)`。

### 3.5 复用表
- `fees`（已有 dump、无迁移）：`date`(unique) + `amount`。`doneCallback` 将当日手续费累加进去；`app:node-fee` 读取。
- `withdraws`（已有 dump、无迁移，**已含** `real_am`/`fee`/`fee_rate` 列）：新增迁移补建；`doneCallback` 累加 `fee` 进 `fees`。
- `alpha_products`：`node_rate` 字段本子项目启用。

### 3.6 新增/补齐迁移
- `2026_09_01_000001_create_withdraws_table.php`（从 sql/nox.sql 忠实重建，含既有 `real_am`/`fee`/`fee_rate` 列；**不新增列**）
- `2026_09_01_000002_create_fees_table.php`（从 sql/nox.sql 重建：id / amount / date(unique) / timestamps）
- `2026_09_01_000003_create_node_products_table.php`
- `2026_09_01_000004_create_node_orders_table.php`
- `2026_09_01_000005_create_node_fee_allocations_table.php`
- `2026_09_01_000006_create_node_fee_logs_table.php`
- `2026_09_01_000007_insert_node_fee_configs.php`（configs 种子：`alpha_node_fee_rate = '90'`；**不设提现费率**——`site_w_fee_rate_usdt`/`site_w_fee_rate_dtp` 为既有运营配置，默认 0，启用 5% 由后台配置）
- 种子：迁移内 `DB::table('node_products')->insertOrIgnore(...)` 一条（名称「算力加速节点」、价格 1000.000000、启用、sort 1）。

> 部署注意：`withdraws`/`fees` 为既有表，dump 导入的 DB 上迁移会「表已存在」报错——与子项目 1 的 `chains`/`assets`/`pay_orders` 同类，测试与 fresh 环境使用迁移，既有库跳过这两条 CREATE（无新列）。

### 3.7 `WalletLog` / 其他常量
- `WalletLog::TYPE_NODE_FEE_IN = 140`（节点池分红入账，USDT IN，走 `creditEarnings`），并加入 `TYPE_MAP` 与用户侧 `getTypeMapUser()`/`getTypeMapUserFilter()`（子项目 1 教训）。
- `WalletLog::TYPE_NODE_FEE_PROJECT = 141`（项目收入入账，USDT IN，直接 `WalletLog::addLog`，**不走**额度槽），同加入 `TYPE_MAP` 与用户侧 map。
- `PayOrder::TYPE_NODE_BUY = 3`（节点申购，链上直付）。
- `NodeOrder::STATUS_WAIT=0 / STATUS_PAID=1 / STATUS_CANCEL=2`。
- `NodeFeeAllocation::STATUS_RUNNING=1 / STATUS_DONE=2 / STATUS_FAIL=3`。

## 4. 核心机制

### 4.1 节点申购（复用资产包直付对账）
`NodeBuyService`（仿 `AlphaOrderService`）：
- `createOrder(User, NodeProduct, array $pay): NodeOrder` — `sn = randOrderNo()`；建 `node_orders`（status=WAIT、amount=price）+ `pay_orders`（`TYPE_NODE_BUY`、sn 一致、金额、token/dest 地址、last_time）。内部余额不扣。
- `payCallback` — 校验通过后 `node_orders` → PAID、`pay_at`；节点永久持有。**不触发** sweep（节点本金计入 S 但购买本身不产生产出/解冻——解冻由新订单 S 增大驱动，资产包与节点订单都计入 S，见 4.3）。
- `payCancel/payTimeout/payFail` — 订单 → CANCEL、`fail_at`。

`app:order-pay` 的 `completeOrder/failOrder/timeoutOrder` 增加 `TYPE_NODE_BUY` 分支。

### 4.2 加速结算（`AlphaRewardService` 扩展）
`settleOne` 计算有效月产率：
```
rate = order.reward_rate                                  // 快照 base_rate
if product 存在 且 product.node_rate > 0
   且 NodeOrder 中该用户存在 status=PAID 的订单:
    rate = product.node_rate
reward = amount × rate ÷ 3000   （scale 8 → 6）
```
- `alpha_rewards.rate` 记录**有效 rate**（加速后）；订单 `reward_rate` 快照不改。
- 2.5× 封顶判断仍用 `quota_power`（= 2.5 × 订单本金）与 `rel_reward`，不变。
- 每订单查一次 product + 节点存在性；`chunkById(100)` 内可接受，必要时批量优化（实现时评估）。

### 4.3 额度槽扩展（`AlphaQuotaService::currentSlot`）
```
S = alpha_quota_multiple × (Σ alpha_orders[PAID].amount + Σ node_orders[PAID].amount)
```
`creditEarnings`/`sweep` 逻辑不变。节点订单 PAID 即永久计入 S（不可退、无完成态）。

### 4.4 提现手续费（机制已存在，仅补路由到节点池）
- 现状：`WithdrawController::create` 已按 `site_w_fee_rate_usdt`/`site_w_fee_rate_dtp`（默认 0）计算 `fee = A × rate%`、`real_am = A − fee`，签名与出账按 `real_am`；`withdraws` 存 `amount`（冻结全额 A）/`real_am`/`fee`/`fee_rate`。
- **新增**：`WithdrawOrderService::doneCallback` 在 `CHANGE_TYPE_OUT` 后，将本次 `fee` 累加进当日 `fees` 行（`DB::table('fees')->updateOrInsert(['date'=>今日], ['amount'=>DB::raw('amount + '.$fee)])`）。
- 取消/拒绝：`back_am` 返还、`is_back=1`，不产生节点池费用（`doneCallback` 才会累加）。
- 启用 5%：后台 Config 将 `site_w_fee_rate_usdt`/`site_w_fee_rate_dtp` 设为 `5`（运营动作，非代码；测试中显式 `config` 覆盖）。

### 4.5 节点池分红（`NodeFeeService` + `app:node-fee`）
`run(settle_date, skip)`（复用 alpha-reward 模式：`Cache::lock('node_fee_service', 1800)`、幂等、`chunkById`）：
1. 读当日 `fees.amount`（无则跳过返回 true）。
2. 幂等：`node_fee_allocations` 该 `settle_date` 已存在且 status=已结束 → 直接返回 true。
3. 取活跃节点：`NodeOrder::where(status, PAID)`，按 `user_id` 分组统计份数 `node_count`。
   - `node_fee_am = bcround2(total_fee × alpha_node_fee_rate ÷ 100)`；`project_am = total_fee − node_fee_am`。
   - 若 `node_count == 0` → 记录 allocation（node_fee_am 全入 remain），返回。
   - `per_share = bcfloor2(node_fee_am ÷ node_count)`（向下取整防超发）；`remain = node_fee_am − per_share × node_count`。
4. 每用户入账：`user_share = per_share × 该用户份数`，`creditEarnings($uid, user_share, TYPE_NODE_FEE_IN)`；写 `node_fee_logs`（每节点订单一行 `am = per_share`）。
5. 项目收入：若 `site_fee_project_wallet` 对应某用户 → 直接 `WalletLog::addLog(..., TYPE_NODE_FEE_PROJECT)` 入账（**不走** `creditEarnings`，项目收入不受额度槽约束）；否则并入 `remain`。
6. allocation status → 已结束；`per_share`/`remain`/`project_am` 落库。

> 每订单结算失败（`creditEarnings` 异常）记日志、`failures++`、继续；`failures>0` 返回 false（可 `--skip` 重跑）。同一天多次分红不会重复（allocation 幂等）。

## 5. API / Admin / 命令 / 配置

### 5.1 前台 API（auth:sanctum + checkSign + language）
- `GET node/home` — 节点产品 + 我的节点概览（持有份数、累计分红、今日待分配）。
- `POST node/create` — 申购（校验产品启用、返回 `{sn, amount(wei), token_addr, last_time}`，内部余额不扣）。
- `GET node/orders` — 我的节点订单（分页）。
- `routes/api.php` 在 `power/*` 之后追加；`PowerController` 不动。

### 5.2 Admin
- `NodeProductsController`（产品 CRUD：名称/价格/启停/排序）。
- `NodeOrdersController`（订单列表：用户地址、单号、本金、状态、支付时间；只读）。
- `NodeFeeAllocsController`（每日分配：sett_date、总额、节点池、份数、每份、项目收入、未分配、状态）。
- `app/Admin/routes.php` 登记 resource。

### 5.3 命令与调度
- `app:node-fee {date?} {--skip}`，`routes/console.php` 调度 daily 00:30 `name('node-fee')`。

### 5.4 配置项（configs）
- `alpha_node_fee_rate` = `'90'`（手续费进节点池 %，本子项目种子）
- `site_fee_project_wallet` = `''`（项目收入钱包地址，可空，本子项目种子）
- 提现费率：既有 `site_w_fee_rate_usdt` / `site_w_fee_rate_dtp`（默认 0，后台可配；启用 5% 为运营动作）

## 6. 测试（Pest）

- **节点申购**：`node/create` 返回支付信息、内部余额不变、`pay_orders` 关联、产品停用/区间拒绝、`payCallback` 激活为 PAID。
- **额度槽**：`currentSlot` 计入节点本金；持节点用户 S 增大；`creditEarnings` 超槽冻结逻辑不变。
- **加速**：持节点时结算用 `product.node_rate`（`alpha_rewards.rate` 记录加速率）、日产出提高；不持节点用快照；`node_rate=0` 无加速；2.5× 封顶按订单本金不变。
- **提现手续费路由**：`doneCallback` 后 `fees` 当日累加该笔 `fee`（`updateOrInsert` date 今日、amount+=fee）；取消/拒绝不累加；费率由 `site_w_fee_rate_usdt`/`site_w_fee_rate_dtp` 控制（测试用 `config` 覆盖设 5）。
- **节点池分红**：每份均分、多节点用户多份、`creditEarnings` 入账、幂等（同日不重复）、`remain`/`project_am` 正确、无节点时全入 remain、`--skip` 重跑。
- **对账**：`app:order-pay` 对 `TYPE_NODE_BUY` 的激活/超时/失败分支。
- **清理回归**：子项目 1 全部用例仍通过。

## 7. 不在本子项目范围内（后续）

- 等级 V1–V5 + 周提现上限 — 子项目 3。
- 推广收益 / 团队收益 / 平级奖（经 `creditEarnings` 接入额度槽）— 子项目 4。
- 多语言调整（简/繁/越/日/泰）— 子项目 5（提现手续费已提前）。
- 节点池的额外来源（如交易量手续费）— 暂不纳入。
