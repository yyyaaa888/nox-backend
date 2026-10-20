# NOX Alpha 子项目 1：算力资产包 + 2.5 倍额度槽 + 首月试用退出 — 设计文档

日期：2026-08-31
范围：仅覆盖「算力资产包」子项目。节点加速、节点池分红、等级、推广/团队收益、提现手续费、多语言均属后续子项目，不在本文范围（见文末"不在范围内"）。

## 1. 背景与目标

NOX Alpha 是现有 nox-backend 的换代产品：一套以 USDT 计价、按算力资产包承接用户资金的高频量化资管模式。核心是"刚性封顶"——用户动静态总产出不超过其本金的 2.5 倍，保障生态可持续。

本子项目落地：

1. 算力资产包产品与订单（申购走**直接链上支付**，不复用"先充值进余额再扣减"）。
2. 每日静态产出结算（USDT 入账，订单级 2.5× 封顶自动完成）。
3. 用户级 2.5× 额度槽：产出超槽冻结、复投（新订单）解冻。
4. 首月试用期退出（退订单本金 80%，已发收益保留）。
5. 删除旧质押 / DAO 节点业务（代码 + 表 + 路由 + 后台）。

## 2. 已确认的业务规则

| 规则 | 内容 |
|---|---|
| 产品档位 | 基础：100–30000 U，基础月产 17%，持节点加速月产 20%；高额：5000–100000 U，基础月产 21%，持节点加速月产 30%。区间重叠由用户所选档位决定产率 |
| 静态产出 | 每日发放，日额 = 本金 × 月产率 ÷ 30（bcmath 高精度）。**含本金返还** |
| 订单级封顶 | 单订单累计静态产出 = 本金 × 2.5 时，订单**自动完成**（停止产出） |
| 额度槽 | S = 2.5 × 当前总本金（进行中订单本金之和；节点金额后续子项目计入） |
| 计入额度槽的 C | 静态产出累计 + 动态收益累计（动态收益子项目 4 接入，统一走 `creditEarnings`） |
| 冻结 | C + E > S 时，超出部分进入冻结（`user_datas.alpha_frozen`），不进可用余额；C 封顶记到 S |
| 解冻 | 新订单支付成功 → 本金增加 → S 增大 → 冻结部分按可解冻额度转可用（sweep） |
| 不回溯 | 订单完成 / 退出使 S 缩小时，**只影响后续判定，不回溯冻结已到账余额** |
| 首月退出 | 下单后 30 天内可退：退订单本金 **80%** 入内部可用 USDT，订单终止；已发动的静态+动态收益**保留不退**；退款不计入 C |
| 复投 | 新购订单，各自独立 2.5× 静态封顶 |
| 申购支付 | 下单即发起链上支付（复用 `pay_orders` + `app:order-pay` 对账），内部余额不扣减 |
| 充值功能 | 保留（用于内部余额入金） |

## 3. 数据模型

### 3.1 新增表 `alpha_products`（资产包产品）

| 字段 | 类型 | 说明 |
|---|---|---|
| id | bigint PK | |
| name | string | 名称 |
| tier | string | 档位：`basic` / `high` |
| min_amount | decimal(20,6) | 最小申购额 |
| max_amount | decimal(20,6) | 最大申购额 |
| base_rate | decimal(10,2) | 基础月产率（17 / 21） |
| node_rate | decimal(10,2) | 持节点加速月产率（20 / 30），子项目 2 接入，本期预留字段 |
| status | tinyint | 1=启用 0=停用 |
| sort | int | 排序 |
| created_at / updated_at | datetime | |

种子数据：基础档（100–30000，17/20）、高额档（5000–100000，21/30）。

### 3.2 新增表 `alpha_orders`（资产包订单）

| 字段 | 类型 | 说明 |
|---|---|---|
| id | bigint PK | |
| user_id | int | 用户 |
| product_id | int | 产品 |
| sn | varchar(20) UNIQUE | 单号（与 `pay_orders.sn` 一致，供对账） |
| amount | decimal(20,6) | 本金 P |
| reward_rate | decimal(10,2) | 月产率快照（申购时锁定，防产品改价影响存量订单） |
| quota_power | decimal(20,6) | **静态封顶 = 2.5 × P** |
| rem_power | decimal(20,6) | 剩余可产出额度，每日扣减 |
| rel_reward | decimal(20,6) | 累计已产出 |
| last_reward | decimal(20,6) | 最近一笔产出额 |
| last_day | date | 最近产出日期 |
| trial_end | datetime | 首月试用截止（下单时 now+30 天） |
| status | tinyint | 0=待支付 1=进行中 2=已完成 3=已退出 4=取消 |
| pay_at / done_at / exit_at | datetime nullable | 支付 / 完成 / 退出时间 |
| created_at / updated_at | datetime | |

索引：`user_id`、`sn`(unique)。

### 3.3 新增表 `alpha_rewards`（每日产出记录，幂等）

| 字段 | 类型 | 说明 |
|---|---|---|
| id | bigint PK | |
| user_id | int | |
| order_id | int | |
| order_am | decimal(20,6) | 订单金额 |
| rate | decimal(10,2) | 当日适用月产率（%） |
| reward_am | decimal(20,6) | 本次产出额 |
| quota_used_am | decimal(20,6) | 本次计入额度槽的金额 |
| frozen_am | decimal(20,6) | 本次被冻结的金额 |
| settle_date | date | 结算日 |
| created_at | datetime | |

唯一约束：`(order_id, settle_date)`（幂等）。

### 3.4 `user_datas` 新增列

| 列 | 类型 | 说明 |
|---|---|---|
| alpha_quota_used | decimal(20,6) default 0 | **C**：累计计入额度槽的产出（静态+动态） |
| alpha_frozen | decimal(20,6) default 0 | **冻结收益**：超槽冻结、待解冻 |

### 3.5 `WalletLog` 常量扩展

新变动类型/来源类型（沿用现有 TYPE 数值段，避开已占用 10–120）：

- `TYPE_ALPHA_REWARD_IN = 130` 每日产出入账（USDT IN）
- `TYPE_ALPHA_EXIT = 131` 首月退出退款（USDT IN）
- `TYPE_ALPHA_UNFREEZE = 132` 冻结收益解冻转可用（USDT IN）

申购本身无内部资金变动（直接链上支付），不需要扣款类型。

### 3.6 `pay_orders` 扩展

- 新增 `PayOrder::TYPE_ALPHA_BUY` 类型常量。
- `app:order-pay` 的 `completeOrder` / `failOrder` / `timeoutOrder` 目前只处理 `TYPE_RECHARGE`，需扩展资产包类型分支。

## 4. 核心机制

### 4.1 额度槽统一记账入口 `creditEarnings(int $uid, string $E, int $type)`

子项目 4 的动态收益也走此函数。逻辑（事务内）：

```
S = 2.5 × Σ(alpha_orders.status=进行中 的 amount)   // 倍数取 configs.alpha_quota_multiple，默认 2.5
C = user_datas.alpha_quota_used
if bcadd(C, E, 6) <= S:
    入可用余额 WalletLog(USDT, IN, $type)
    C = bcadd(C, E, 6)
else:
    available_part = bcsub(S, C, 6)          // 可入部分
    frozen_part   = bcsub(bcadd(C, E, 6), S, 6)  // 超出部分
    入可用余额 available_part；alpha_frozen += frozen_part
    C = S
写入 user_datas.alpha_quota_used = C
```

### 4.2 解冻 sweep（新订单支付成功后触发）

```
newS = 2.5 × 新总本金
headroom = bcsub(newS, C, 6)
sweep = min(alpha_frozen, headroom)
alpha_frozen -= sweep；入可用余额 WalletLog(USDT, IN, TYPE_ALPHA_UNFREEZE)
```

解冻不新增产出，只是放行已冻结收益。C 不随解冻变化（冻结部分已计入 C）。

### 4.3 每日产出结算 `AlphaRewardService`（命令 `app:alpha-reward`）

替代旧 `app:stake-reward` 的调度位。复用现有已验证模式：`Cache::lock` 防重、`chunkById(100)`、按 `settle_date` 幂等。

对 status=进行中 且 `created_at ≤ 当日 23:59:59` 的订单：

1. `E = bcdiv(bcmul(amount, reward_rate, 8), 3000, 8)`（= 本金 × 月产率% ÷ 30）。
2. `E = min(E, rem_power)`；`rem_power -= E`、`rel_reward += E`；更新 `last_reward`、`last_day`。
3. 写 `alpha_rewards` 记录（幂等：`(order_id, settle_date)` 已存在则跳过）。
4. 调 `creditEarnings($uid, $E, TYPE_ALPHA_REWARD_IN)`，记录本次 `quota_used_am`、`frozen_am`。
5. 若 `rel_reward ≥ quota_power` → status=已完成，`done_at`（S 自然缩小，不回溯冻结）。

### 4.4 首月退出 `AlphaOrderService::exit`

- 条件：订单属该用户、status=进行中、`now ≤ trial_end`。
- 退款 `80% × amount`：`WalletLog(USDT, IN, TYPE_ALPHA_EXIT)` 入可用余额。
- 订单 → 已退出、`exit_at`。
- 已发收益保留；退款不计入 C；订单本金从总本金剔除（S 缩小，仅影响后续）。

## 5. 申购与对账流程

1. `POST power/create`（auth:sanctum + checkSign + language）：
   - 校验产品启用、`amount` 在 `[min_amount, max_amount]`。
   - `sn = randOrderNo()`；建 `alpha_orders`（status=待支付、`reward_rate` 快照、`quota_power = 2.5×amount`、`rem_power = quota_power`、`trial_end = now+30天`）。
   - 建 `pay_orders`（`type=TYPE_ALPHA_BUY`、`sn`、amount、decimals、pay_contract、pay_addr、token_addr、dest_addr、last_time、status=WAIT）——参数复用现有充值取配置的方式（asset/chain/pay_contract）。
   - 返回 `{ pay_contract, pay_addr, amount, sn, deadline }` 供前端拉起支付。
   - 内部余额不扣减。
2. 用户链上支付。
3. `app:order-pay` 轮询 `getPayInfo(sn)`：
   - `isPaid` 且校验通过（`payTotal ≥ 订单金额`、`contractAddress == token_addr`、`fromAddress == pay_addr`）→ **激活** alpha 订单（进行中、`pay_at`）→ 触发 **解冻 sweep**。
   - 超时（5 分钟）→ 订单取消（status=取消，`fail_at`）。
   - 校验失败 → 订单取消，记失败原因。
   - 充值类型分支保持原逻辑不变。

## 6. 旧业务删除清单

**删表**（新迁移 `Schema::dropIfExists`，fresh 环境安全）：
`stake_orders`、`stake_rewards`、`stake_speeds`、`machines`、`machine_attrs`、`machine_products`、`machine_product_attrs`、`machine_rates`、`nodes`、`node_orders`、`node_commissions`、`node_rewards`、`node_reward_logs`、`node_perf_logs`、`user_power_logs`。

**保留**：`user_relation_closures`（团队，子项目 4）、`levels` / `level_histories`（子项目 3）、`invite_rewards` / `team_rewards` / `fees` / `fee_logs` / `fee_allocs` / `fee_setts`（团队 / 节点池分红）、以及全部基础设施（`users`、`user_datas`、`configs`、`chains`、`assets`、`pay_orders`、`user_recharges`、`withdraws`、`asset_logs`）。

**删代码**：
- 模型：StakeOrder、StakeReward、StakeSpeeds、Machine、MachineAttr、MachineProduct、MachineProductAttr、MachineRate、Node、NodeOrder、NodeCommission、NodeReward、NodeRewardLog、UserPowerLog、MintSpeed。
- 服务：StakeBuyService、StakeRewardService、NodeBuyService、NodeCommissionService。
- API：StakeBuyController、StakeOrderController、NodeBuyController、NodeHomeController、StakeHomeV2Controller；`routes/api.php` 中 `node/*`、`stake/*` 路由。
- Admin：MachineProductsController、MachinesController、MachineRatesController、StakeOrdersController、StakeRewardsController、StakeSpeedsController、UserPowerLogsController、NodesController、NodeOrdersController、NodeRewardsController、NodeCommissionsController；`app/Admin/routes.php` 对应 resource。
- 命令：StakeRewardCmd。**保留** OrderPay（扩展）、WithdrawScanCmd、FeeSettCmd 等充值/提现/手续费命令。
- 创建被删表的 migration 文件一并删除（fresh 环境不再建表）。

**常量清理**：WalletLog / FeeLog 中仅被已删代码引用的 TPC/AFC 相关常量与符号按需清理（实现时逐个核对保留代码的引用）。

## 7. 后台与 API

### 7.1 配置项
- `configs` 新增 `alpha_quota_multiple` = `2.5`（额度槽倍数；"刚性封顶"语义不变，做成可调）。

### 7.2 Admin（dcat-admin）
- 新增 `AlphaProductsController`（产品 CRUD：档位/区间/产率/启停）。
- 新增 `AlphaOrdersController`（订单列表：用户、本金、进度、状态；展示额度槽 S/C/冻结）。
- `app/Admin/routes.php` 登记 resource，替换旧菜单。

### 7.3 前台 API（auth:sanctum + checkSign + language）
- `GET power/home` — 产品列表 + 我的资产包概览（S、C、冻结、各订单进度）。
- `POST power/create` — 申购（发起链上支付）。
- `GET power/orders` — 我的订单列表。
- `POST power/exit` — 首月退出。
- 支付成功后由 `app:order-pay` 后台确认激活（前端轮询订单状态即可）。

## 8. 测试（Pest）

- **申购**：区间校验、产品停用拒绝、订单快照（`quota_power=2.5P`、`trial_end`、`reward_rate`）、`pay_orders` 关联正确、内部余额不变。
- **每日结算**：产出额 = 本金×月产率÷30（bcmath）、`rem_power` 递减、同日幂等、满 2.5× 自动完成（status=已完成 + `done_at`）。
- **额度槽**：C≤S 全额入可用；C>S 超额进冻结；新订单支付成功触发解冻 sweep（冻结部分按可解冻额度转可用）；订单完成使 S 缩小时不回溯冻结已到账余额。
- **首月退出**：窗口内退 80% + 订单终止 + 已发收益保留；窗口外拒绝；退款不入 C。
- **对账**：`app:order-pay` 对资产包类型订单的激活/超时/失败分支；金额/合约/付款地址校验失败 → 订单取消。
- **清理回归**：旧路由、旧模型类不存在；保留模块（充值、提现、团队表）不受影响。

## 9. 不在本子项目范围内（后续）

- 算力加速节点 + 节点池手续费分红（含 `node_rate` 字段启用）— 子项目 2。
- 等级 V1–V5 + 周提现上限 — 子项目 3。
- 推广收益 / 团队收益 / 平级奖（通过 `creditEarnings` 接入额度槽）— 子项目 4。
- 提现 5% 手续费、多语言调整（简/繁/越/日/泰）— 子项目 5。
