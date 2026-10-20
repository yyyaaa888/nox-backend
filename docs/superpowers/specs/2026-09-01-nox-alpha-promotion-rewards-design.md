# NOX Alpha 子项目 4：推广收益 / 团队收益 / 平级奖 — 设计文档

日期：2026-09-01
前置：子项目 1（算力资产包 + 额度槽）、子项目 2（算力加速节点 + 提现手续费路由 + 节点池分红）、子项目 3（等级 V1–V5 + 周提现上限 + DTP→NOX 改名）已上线（master `5cdd40f`）。本子项目建立在 `creditEarnings` 额度槽统一记账入口、`levels.reward_rate`、`invite_rewards`/`team_rewards` 保留表之上。

范围：推广收益（下线购买资产包抽成）、团队收益（下线静态收益抽成）、平级奖（同等级上级的团队奖加成）。等级/节点/提现/多语言均已完成或属后续。

## 1. 背景与目标

- **推广收益**：下线**购买算力资产包**（`alpha_orders` 支付成功）时，沿推荐链向上按**推荐层数**向各上级抽成（下线新增算力本金 × 率）。
- **团队收益**：下线**产生静态收益**（每日资产包结算）时，沿推荐链向上按层向各上级抽成（下线量化利润 × 率）。
- **平级奖**：静态收益结算时，若某上级与下线 **V 等级相同**，该上级额外获得**其团队奖的 10%**。
- 全部奖励经 `AlphaQuotaService::creditEarnings` 入额度槽 C（超槽冻结），新增 `WalletLog` 类型。

**奖励率**由两张表（推广/团队）× 5 等级 × 2 节点态（基础/持节点）× 最多 5 层决定，存 `reward_rates` 表（后台可配），种子填满既定制度。

## 2. 已确认的业务规则

| 规则 | 内容 |
|---|---|
| 触发 | 推广奖 = 下线购买资产包（`AlphaOrderService::payCallback`）；团队/平级奖 = 下线静态收益（`AlphaRewardService::settleOne`）。**节点申购不触发奖励**（仅作"持节点"提率） |
| 层级 | 推荐关系深度：直推=1 层、间推=2 层…；上限 = 该上级等级率表长度（V1-V2 3 层、V3-V4 4 层、V5 5 层），超出层无率即无奖励 |
| 率来源 | `reward_rates` 表：`type`(invite/team) × `rank`(1-5) × `has_node`(0/1) × `layer`(1-5) → `rate`(%) |
| 持节点 | 上级持有 PAID 节点订单 → 用 `has_node=1` 的率（更高） |
| 推广奖 | `上级第 L 层奖励 = 下线订单额 × rate[invite][上级rank][上级has_node][L]` |
| 团队奖 | `上级第 L 层奖励 = 下线静态收益额 × rate[team][上级rank][上级has_node][L]` |
| 平级奖 | 团队奖基础上，`上级 rank == 下线 rank` 时额外 `团队奖 × 10%` |
| 入账 | 全部经 `creditEarnings`（计入额度槽 C，超槽冻结）；`WalletLog` 类型 150/151/152 |
| 累加列 | `user_datas.invite_reward`/`team_reward` 计数器同步增加（供 MyTeamController 展示；非余额列） |
| 幂等 | 推广按 `invite_rewards.order_id`、团队按 `team_rewards(order_id, sett_date)`（payCallback/settleOne 本身有状态守卫） |
| 首月退出 | 已发奖励不退（与资产包"已发收益保留"一致） |

## 3. 数据模型

### 3.1 新增表 `reward_rates`（奖励率配置，后台可配）
| 字段 | 类型 | 说明 |
|---|---|---|
| id | int PK | |
| type | varchar(10) | `invite`（推广）/ `team`（团队） |
| rank | tinyint | V 等级 1-5 |
| has_node | tinyint | 0=基础 1=持节点 |
| layer | tinyint | 推荐层 1-5 |
| rate | decimal(10,2) unsigned | 奖励率(%) |
| created_at / updated_at | datetime | |

唯一约束：`(type, rank, has_node, layer)`。

**种子数据**（用户既定制度）：

推广收益 `invite`：
| rank | 基础 has_node=0 | 持节点 has_node=1 |
|---|---|---|
| 1 | 2/1/1 | 3/2/1 |
| 2 | 5/2/1 | 7/3/1 |
| 3 | 7/2/1/1 | 10/3/1/1 |
| 4 | 7/4/2/1 | 10/5/3/1 |
| 5 | 8/4/2/2/2 | 12/5/3/3/3 |

团队收益 `team`：
| rank | 基础 has_node=0 | 持节点 has_node=1 |
|---|---|---|
| 1 | 2/1/1 | 3/1/1 |
| 2 | 4/2/1 | 5/3/1 |
| 3 | 5/2/2/1 | 7/3/3/1 |
| 4 | 7/4/2/2 | 10/5/3/3 |
| 5 | 7/5/4/2/2 | 10/7/5/3/3 |

（每行第 i 个值 = layer i 的率；该 rank 的行数即其最大奖励层数。）

### 3.2 `team_rewards` 表加列（保留既有 level/prev_level/child_level/sett_date）
- `layer` tinyint — 推荐层（该上级到下单下线的深度）
- `has_node` tinyint — 该上级是否持节点（决定所用率）
- `is_same_level` tinyint — 1=平级奖（该行含平级加成）；team 行里记录
- 语义：`level` = 上级 V 等级、`child_level` = 下线 V 等级、`prev_level` = 0 或上级之上的等级（保留列，写入上级 rank 或 0）

### 3.3 `invite_rewards` 表（保留，无需改）
- 字段 user_id/child_id/order_id/order_am/reward_am/reward_rate 已够（rate 记录实际所用率）

### 3.4 `WalletLog` 常量
- `TYPE_INVITE_REWARD = 150`（推广收益，USDT IN，creditEarnings）
- `TYPE_TEAM_REWARD = 151`（团队收益，USDT IN，creditEarnings）
- `TYPE_SAME_LEVEL_REWARD = 152`（平级奖，USDT IN，creditEarnings）
- 均加入 `TYPE_MAP` 与用户侧 `getTypeMapUser()`/`getTypeMapUserFilter()`

### 3.5 `user_datas`
- `invite_reward`/`team_reward` 列已存在（累加计数器），本子项目开始写入（累计推广/团队奖励额，含平级并入 team_reward）。

## 4. 核心机制

### 4.1 `RewardService::distributeInvite(AlphaOrder $order)`
挂 `AlphaOrderService::payCallback`（激活订单 + sweep 之后）：
1. `$downline = $order->user_id`；`$chain = UserRelationClosure` 沿 ancestor 向上取（depth 1..N）。
2. 对每个深度 L 的上级 `$anc`：
   - `$rate = RewardRate::query()->where('type','invite')->where('rank',$anc->level_id)->where('has_node', $anc 持节点? 1:0)->where('layer',$L)->value('rate')`；无行 → 跳过（该层不奖励，继续向上）。
   - `$am = bcround2(bcdiv(bcmul($order->amount, $rate, 8), '100', 8), 6)`（订单额 × 率% ，scale 6）。
   - `$am > 0` → `creditEarnings($anc->id, $am, TYPE_INVITE_REWARD)`；写 `invite_rewards`（user_id=anc、child_id=downline、order_id、order_am、reward_am、reward_rate）；累加 `user_datas.invite_reward += am`。
3. 走至链尾或 L > 5。

### 4.2 `RewardService::distributeTeam(AlphaOrder $order, string $settle_date, string $static_reward)`
挂 `AlphaRewardService::settleOne`（入账静态收益之后）：
1. 同上取推荐链。
2. 每层 `$anc`：
   - `$team_rate = reward_rates[team][rank][has_node][L]`；无行跳过。
   - `$team_am = 静态收益额 × team_rate%` → `creditEarnings(TYPE_TEAM_REWARD)`。
   - 平级：若 `$anc->level_id == $order->user 的 level_id`（下线 V 等级）→ `$same_am = bcround2($team_am × 10%)` → `creditEarnings(TYPE_SAME_LEVEL_REWARD)`；`is_same_level=1`。
   - 写 `team_rewards` 行（level=anc rank、child_level=下线 rank、prev_level=0、layer、has_node、is_same_level、order_id、sett_date、order_am=静态收益额、reward_am=team_am、reward_rate=team_rate）；累加 `user_datas.team_reward += team_am + same_am`。

### 4.3 累加列更新
`user_datas.invite_reward`/`team_reward` 是**累计计数器（非余额列）**，经针对性 `update(['invite_reward' => DB::raw('invite_reward + '.$am)])` 更新（与旧 StakeBuyService 一致；不影响额度槽/账本，余额仍由 creditEarnings→WalletLog 管）。可选：给 `UserData` 加 `addRewardCounter($uid, $invite=0, $team=0)` 静态方法集中管理。

### 4.4 幂等
- 推广：`invite_rewards` 先 `where('order_id', $order->id)->exists()` 检查（payCallback 有 status 守卫，天然一次）。
- 团队：`team_rewards` 先 `where('order_id')->where('sett_date')->exists()` 检查；settleOne 本身按 alpha_rewards(order_id,sett_date) 幂等，团队分发同事务内执行。
- 每级分发可包 `DB::transaction`（creditEarnings 内部已事务，嵌套用 savepoint）。

## 5. API / Admin

### 5.1 前台 API
- 无新端点。`MyTeamController` 保留现有累加展示（`invite_reward`/`team_reward`/`dynamic_reward`）。
- 奖励自动入账，用户资金流水中可见（`TYPE_INVITE_REWARD`/`TYPE_TEAM_REWARD`/`TYPE_SAME_LEVEL_REWARD`）。

### 5.2 Admin
- `RewardRatesController`（奖励率 CRUD：type/rank/has_node/layer/rate，filter 按 type+rank）
- `InviteRewardsController`（推广记录，只读列表）
- `TeamRewardsController`（团队/平级记录，只读列表：user/child/order/layer/has_node/is_same_level/sett_date）
- `app/Admin/routes.php` 登记 resource

## 6. 测试（Pest）

- **推广**：V1 下线购买 1000 → V3 直推上级（持节点）得 10%（100）入 current_usdt、`invite_rewards` 行正确、`invite_reward` 累加、creditEarnings 计入额度槽 C；幂等（同一订单不重复）。
- **推广层数**：V1 上级（3 层率）对第 4 层下线无奖励；V5 上级对第 5 层下线按 2%/3% 奖励。
- **团队/平级**：静态收益 100 → 各层上级按 team 率得奖；同等级上级额外 `团队奖×10%`（`is_same_level=1`）；`team_rewards` 行含 layer/has_node/is_same_level；幂等（同 order+sett_date 不重复）。
- **持节点**：持节点上级用 node 列率（推广 12% vs 8%）。
- **额度槽**：奖励经 creditEarnings，超槽部分冻结（`alpha_frozen`）。
- **回归**：子项目 1/2/3 全部用例通过。

## 7. 不在本子项目范围内（后续）

- 多语言调整 — 子项目 5。
- 小区/大区平级、团队业绩奖励的额外变体 — 暂不纳入（仅本制度三奖）。
