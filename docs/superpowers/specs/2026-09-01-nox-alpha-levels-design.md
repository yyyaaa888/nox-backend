# NOX Alpha 子项目 3：等级 V1–V5 + 周提现上限 + DTP→NOX 全面改名 — 设计文档

日期：2026-09-01
前置：子项目 1（算力资产包 + 额度槽）、子项目 2（算力加速节点 + 提现手续费路由 + 节点池分红）已上线（master `022f80c`）。本子项目建立在 `UserUpgradeService`/`Level`/`LevelHistory`（既有等级机制）、`WithdrawController` 每日限额（既有）、`WalletLog` 账本之上。

范围：① DTP→NOX 代币全面改名（前提）；② 等级体系重构为 V1–V5（5 级，admin 可配）；③ 按等级 + 按币种（USDT/NOX）的周提现上限（自然周周一起）。推广/团队收益/平级奖（子项目 4）、多语言（子项目 5）仍属后续。

## 1. 背景与目标

- 代币正名为 **NOX**（原 DTP）：符号、列名、config 键、资产表、展示文案全量改名，避免新旧命名混杂。
- 等级体系重构为 **V1–V5**（5 级）：以 `total_perf`（总业绩）为唯一判定阈值（沿用 `UserUpgradeService` 现有逻辑），每级配置 USDT/NOX 周提现上限。
- **周提现上限**：用户在自然周（周一 00:00 起）内累计提现（含进行中/已完成/失败，排除驳回/取消）不得超过其等级对应币种的上限；无等级用户按 V1 上限。与现有每日限额（全站/用户）并存。

## 2. 已确认的业务规则

| 规则 | 内容 |
|---|---|
| 等级数 | V1–V5 共 5 级，admin 可配；阈值仅用 `total_perf`（个人业绩 >0 且团队总业绩达标） |
| 默认等级配置 | V1(3000, 周 5000/5000)、V2(5000, 10000/10000)、V3(10000, 30000/30000)、V4(50000, 100000/100000)、V5(100000, 300000/300000)（total_perf, 周USDT/周NOX） |
| 无等级用户 | `level_id = 0` 视为 V1 的周上限 |
| 周窗口 | 自然周（周一 00:00 起至当前时刻），按 `withdraws.created_at` 统计 |
| 计入项 | `status IN (PENDING, DONE, FAIL)` 的提现（沿用现有每日限额约定，排除驳回/取消） |
| 币种维度 | USDT、NOX 各自独立的周上限（`weekly_limit_usdt`/`weekly_limit_nox`） |
| 与每日限额 | 并存：全站每日 + 用户每日 + 用户每周，全部通过才放行 |
| 升级机制 | `UserUpgradeService` 不变（仍按 `total_perf` 判定 + `LevelHistory` 记录），仅阈值表改为 V1–V5 |
| DTP 改名 | 符号 `SYMBOL_DTP`→`SYMBOL_NOX`（'DTP'→'NOX'）、`Asset::CODE_DTP`→`CODE_NOX`、`user_datas` 的 `current_dtp/freeze_dtp/total_dtp`→`current_nox/freeze_nox/total_nox`、config 键 `site_*_dtp`→`site_*_nox`、`assets` 表 DTP 行→NOX、`asset_logs`/`withdraws` 既有 `symbol='DTP'` 数据→'NOX'、展示文案/测试全量同步 |

## 3. 数据模型

### 3.1 DTP→NOX 改名涉及的迁移
- `2026_09_01_000001_rename_dtp_to_nox_columns.php`：`user_datas` 列 `total_dtp`/`current_dtp`/`freeze_dtp` → `total_nox`/`current_nox`/`freeze_nox`（`renameColumn`，数据自动保留）。
- `2026_09_01_000002_rename_dtp_to_nox_data.php`：
  - `assets` 表 `code='DTP'`（含 `tDTP` 测试币）→ `code='NOX'`，同步 `name`/`title` 文案（如 'DTP正式币'→'NOX正式币'）。
  - `asset_logs` 表 `symbol='DTP'` → `'NOX'`（update 数据迁移）。
  - `withdraws` 表 `symbol='DTP'` → `'NOX'`（update 数据迁移）。
  - `configs` 表 key 改名：`site_allow_recharge_dtp`/`site_allow_w_dtp`/`site_w_min_dtp`/`site_w_fee_rate_dtp`/`site_w_dtp_global_limit`/`site_w_dtp_user_limit` → 对应 `*_nox`。
- `asset_logs`/`withdraws` 为既有表（子项目 2 已补迁移）；`assets` 已有迁移（子项目 1）。均只需 update/rename 数据迁移。

### 3.2 等级表改动
- `levels` 新增列：`weekly_limit_usdt` decimal(20,6) unsigned default 0（周 USDT 提现上限）、`weekly_limit_nox` decimal(20,6) unsigned default 0（周 NOX 提现上限）。
- `2026_09_01_000003_reconfigure_levels_to_v1_v5.php`：
  - 现有 T1–T5（id 1-5）改名：`name`/`code` → `V1`..`V5`；设置 `weekly_limit_usdt`/`weekly_limit_nox` 为默认配置值；`reward_rate` 保留现有值（3/5/7/9/11）。
  - 删除 T6–T10（id 6-10）。
  - 存量 `users.level_id` > 5 → 改为 5（V5）。

### 3.3 常量
- `WalletLog::SYMBOL_DTP` → `SYMBOL_NOX`（值 `'NOX'`），并加入/更新 `SYMBOL_MAP` 等映射。
- `Asset::CODE_DTP` → `CODE_NOX`（值 `'NOX'`）。
- `UserData::changeDtpBalance()` → `changeNoxBalance()`。

## 4. 核心机制

### 4.1 DTP→NOX 改名（跨切面）
- 常量/枚举：`WalletLog::SYMBOL_DTP`、`Asset::CODE_DTP`、`UserData` 的 dtp 列访问 → 全量 NOX。
- `UserData` 模型：`changeDtpBalance()` → `changeNoxBalance()`；子项目 1 加入的 `decimal:6` cast 键（`current_dtp`/`freeze_dtp`/`total_dtp`）随列改名同步为 `current_nox`/`freeze_nox`/`total_nox`。
- 业务分支：`WithdrawController`/`RechargeController`/`MyAssetController` 中 `$symbol == WalletLog::SYMBOL_DTP` 分支 → `SYMBOL_NOX`；对应 `site_*_nox` config 键读取。
- `ConfigController` 的表单键/文案：DTP → NOX。
- 展示：所有 `__('...DTP...')` / 'DTP' 文案 → NOX（含 Admin grid、API 返回字段名不做变化，仅符号值/文案）。
- 测试：`tests/Pest.php` 辅助函数、`AlphaOrderTest`/`NodeFeeTest` 中 DTP 引用 → NOX。

### 4.2 等级 V1–V5 重构
- `levels` 数据：V1–V5（id 1-5），`total_perf` = 3000/5000/10000/50000/100000，`reward_rate` = 3/5/7/9/11，`weekly_limit_usdt`/`weekly_limit_nox` = 5000/5000、10000/10000、30000/30000、100000/100000、300000/300000。
- `UserUpgradeService::checkUserLevel` 不变（`team_data['user_perf'] > 0` 且 `team_data['all'] >= total_perf`，取第一个达标等级）。
- 用户侧 `users.level_id` 引用 V1–V5；`LevelHistory` 沿用。

### 4.3 周提现上限
- `Withdraw` 模型新增：
  ```php
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
- `WithdrawController::create` 在 `checkWithdrawGlobalLimit`/`checkWithdrawUserLimit` 之后新增 `checkWithdrawWeeklyLimit($user_id, $amount, $symbol)`：
  - `$level = User::find($user_id)->level_id ?: 1`（无等级按 V1）。
  - `$levelRow = Level::find($level)`；取 `$symbol === SYMBOL_USDT ? $levelRow->weekly_limit_usdt : $levelRow->weekly_limit_nox`。
  - 周起点：`Carbon::now()->startOfWeek()`（Laravel 默认周一起）。
  - `$total = Withdraw::getWeekUserTotalWithdrawAmount($user_id, $symbol, $weekStart)`。
  - 剩余 = `bcsub($limit, $total, 2)`；≤0 或 < amount → 抛 `LogicException`（提示剩余额度，与每日限额同款文案风格）。

## 5. API / Admin / 配置

### 5.1 前台 API
- 无新端点。`POST withdraw` 的限额检查链：全站每日 → 用户每日 → 用户每周（新增）。
- `MyAssetController`（我的资产）读取 NOX 相关 config/余额展示。

### 5.2 Admin
- `Team\LevelsController`：grid 加 `weekly_limit_usdt`/`weekly_limit_nox` 列（`removeTrailingZeros` 展示）；form 加两个 `decimal` 输入（rules numeric min:0）。
- `ConfigController`：DTP → NOX 的表单键与文案更新。

### 5.3 配置项
- 无新增 config 键（周上限在等级表）。既有 `site_w_*_nox` 键由改名迁移同步。

## 6. 测试（Pest）

- **改名**：`WalletLog::SYMBOL_NOX === 'NOX'`；`user_datas` 新列存在、旧列不存在；`assets` 无 `code='DTP'` 行、有 `code='NOX'`；`asset_logs`/`withdraws`/`configs` 数据迁移正确；提现 NOX 分支可用。
- **等级**：迁移后 `levels` 恰为 V1–V5、周上限字段正确、T6–T10 不存在；`UserUpgradeService` 按新阈值升级（V1 阈值 3000 等）；旧 `level_id>5` 用户映射到 V5。
- **周限额**：周一起累计、周一重置（跨周不累计）、超限拒绝、按币种独立（USDT 超限不影响 NOX）、无等级按 V1、与每日限额并存（每日过/每周不过则拒）。
- **回归**：子项目 1/2 全部用例通过（改名后测试同步）。

## 7. 不在本子项目范围内（后续）

- 推广收益 / 团队收益 / 平级奖（`reward_rate` 启用，经 `creditEarnings` 接入额度槽）— 子项目 4。
- 多语言调整（简/繁/越/日/泰）— 子项目 5。
- 等级判定补全 `dept_num`/`dept_perf` 部门条件 — 暂不纳入（本子项目仅 `total_perf`）。
