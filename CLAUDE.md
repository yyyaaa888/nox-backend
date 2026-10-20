# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

NOX / "DTP" — a crypto investment platform backend. The current product is the **算力资产包 (Alpha computing-power package)**: users authenticate with Ethereum-style wallet addresses (signature login), buy Alpha packages (产品), earn daily static rewards in USDT (order-level 2.5× hard cap), and deposit/withdraw USDT/DTP on-chain (BSC, chain id 56). The legacy staking / DAO-node business (nodes, staking/machine products) was **removed** in the Alpha subproject (2026-09) — see Gotchas for what remains.

Stack: **Laravel 12** (PHP 8.2+, `ext-bcmath`/`gmp`/`gd` required), **MySQL** (sqlite `:memory:` in tests), **Redis** (session/cache/queue), **dcat/laravel-admin** admin panel, **laravel/sanctum** API tokens, **web3p/web3.php** for BSC contract calls. UI/translation strings are Chinese-first (`lang/*`, `__()` helper).

## Commands

- Tests (Pest): `composer test` (runs `artisan config:clear` then `artisan test`), or `php artisan test`.
- Single test / file: `php artisan test --filter AlphaRewardTest` or `vendor/bin/pest tests/Feature/AlphaRewardTest.php`.
- Dev server trio: `composer dev` (concurrently runs `php artisan serve`, `php artisan queue:listen --tries=1`, `npm run dev`).
- Frontend assets (Vite, used for admin skins): `npm run dev` / `npm run build`.
- Code style: `vendor/bin/pint`.
- Scheduled jobs are defined in `routes/console.php` and run via the scheduler (`php artisan schedule:run`). Key ones, also runnable manually:
  - `php artisan app:order-pay` — reconcile pending on-chain payments (recharge **and** Alpha package purchases) (cron: every minute).
  - `php artisan app:withdraw-scan` — reconcile pending contract-mode withdrawals (cron: every minute).
  - `php artisan app:alpha-reward {date?} {--skip}` — daily Alpha package reward settlement (cron: daily 00:20). Pass `--skip` to re-run a date past the idempotency guard.
- Reference DB dumps: `sql/nox.sql` (full Navicat dump) and `sql/nox-clear.sql`; migrations under `database/migrations/` are the schema source of truth. (Note: `chains`/`assets`/`pay_orders` historically existed only in the dump — they now have migrations too, `2026_08_30_*`.)

## Architecture

The app is split into four layers; **all money movement must go through the service layer, never raw model updates.**

### API (`routes/api.php`, `app/Http/Controllers/Api/`)
Three route groups:
1. Public (`language` middleware): index, partners, announcements, database documents.
2. Login: `POST user-login` — wallet signature login. New users **must** provide a valid `invite_code` (from `users.invite_code`).
3. Protected (`language` + `auth:sanctum` + `checkSign`): assets, team, Alpha package (`power/*`), recharge, withdraw, pay password.

Middleware aliases registered in `bootstrap/app.php`: `language` (accept-language → locale) and `checkSign`. The `checkSign` middleware (HMAC-SHA256 of `authorization token + times` with `SIGN_KEY`, from `sign`/`times` headers) is **only enforced in production** — it passes through in non-prod.

### Admin (`app/Admin/`)
dcat-admin panel at `/admin` (prefix from `ADMIN_ROUTE_PREFIX`). Routes in `app/Admin/routes.php`, controllers under `app/Admin/Controllers/` mirror the business domains (Alpha products/orders, PayOrders, Withdraws, Team, Config, etc.). `app/Admin/bootstrap.php` hard-blocks admin extension uploads/actions and rich-text file uploads, and registers custom column displayers (`address`, `hash`, `number`). Login uses Google2FA + captcha. Custom middleware: `CheckUserSession`, `CheckFirstLogin`.

### Services (`app/Services/`)
All business logic lives here (controllers stay thin). Central ones:
- `UserService` — login/register; creates `users`, `user_datas`, and team closure rows via `UserRelationService::setupUserRelations`.
- `AlphaQuotaService` — **the single unified earnings-crediting entry point** for the user-level 2.5× quota slot: `creditEarnings($uid, $amount, $type)` credits earnings into the slot (excess over the slot goes to `user_datas.alpha_frozen`), `sweep($uid)` unfreezes frozen earnings after a new order grows the slot. Static rewards use it today; future dynamic earnings (推广/团队) must also route through it.
- `AlphaOrderService` — Alpha package purchase: `createOrder` builds an `alpha_order` + `pay_order` for direct chain payment (internal balance not debited); `payCallback`/`payCancel`/`payTimeout`/`payFail` state machine; `exit` (first-month 80% refund).
- `AlphaRewardService` — daily static reward settlement (`run($settle_date, $skip)`): per-order `amount × reward_rate ÷ 3000`, capped by `rem_reward`, idempotent on `(order_id, settle_date)`, auto-completes an order when its cumulative reward hits the 2.5× cap.
- `UserUpgradeService`, `UserRechargeService`, `WithdrawOrderService` — level upgrades, recharge/withdraw state machines.
- `RedLockService` — distributed lock helper (Redis).

### Web3 (`app/Services/Web3/` + `app/Services/EthService.php`)
- `PaymentContractService` — calls `getPayInfo(uint256 orderSn)` on the payment contract (BSC) with a hard-coded method selector and parses the 7-field return by offsets; used by `app:order-pay` to confirm on-chain payment (recharge + Alpha purchase).
- `WithdrawContractService` — withdraw contract interaction; `WithdrawScanCmd` polls whether a signature was used.
- `SignService::signAdd` / `personalSign` — builds and signs the withdraw authorization message.
- Legacy duplicates live in `app/Services/Web3Service.php`, `Web3NewService.php`, and `app/Services/EthService.php` — prefer the `Web3/` sub-namespace unless working on the legacy path.

### Ledger model — the single most important pattern
- Wallets are **not** on `users`. Balances live on `user_datas` as `decimal(20,6)` string columns (`current_usdt`, `freeze_usdt`, `current_dtp`, `freeze_dtp`, plus `total_*` accumulators and reward counters `invite_reward`, `team_reward`, …). Plus the Alpha quota columns: `alpha_quota_used` (C, slot used) and `alpha_frozen` (over-slot frozen earnings).
- **Every** balance change goes through `WalletLog::addLog($user_id, $amount, $symbol, $change_type, $type)` (`app/Models/WalletLog.php` → table `asset_logs`). It writes an append-only ledger row (with before/after amounts) and then updates `user_datas` via `UserData::changeUsdtBalance()`/`changeDtpBalance()`. Change types: IN=1, OUT=2 (unfreeze-spend), FREEZE=3, BACK=4, FAST_OUT=5.
- Never update `user_datas` balance columns directly with `DB::raw` — always route through `WalletLog::addLog` so the ledger stays consistent and balance checks (negative-balance throws `LogicException('余额不足')`) apply. **Exception**: the Alpha quota bucket (`alpha_quota_used`/`alpha_frozen`) is managed only by `AlphaQuotaService`.
- All money math uses **BC Math string functions** (`bcadd`/`bcsub`/`bcmul`/`bccomp` with scale 6, or `bcround2`/`bcceil2`/`bcfloor2` from `app/helpers.php`). Never use float/double arithmetic on amounts.

### Config
- Static env config via `config/site.php` (`site.*`): `asset.chain_id`, `pay_contract`, `withdraw_contract`, `withdraw_address`/`withdraw_key`, ABI paths (`storage/abi/payment.json`, `storage/abi/withdraw.json`).
- Business config is a key/value `configs` table read via `getGlobalConfig($key, $default)` / `siteConfig($key)` helpers (`app/helpers.php`). The whole table is cached (`getTableCache`); call `SysDataClearCache`/`getTableCache($table, forget: true)` after changing rows. Admin edits these through the Config screen. Notable keys: `alpha_quota_multiple` (default `'2.5'`, the quota-slot multiple). The Config screen also still renders legacy keys `site_allow_node_buy`/`site_allow_stake_buy`/`site_node_marketing_wallet` — keys only, no business logic reads them.

### Order state machines
- **Recharge**: `user_recharges` + a parallel `pay_orders` row (holds `sn`, token/pay contract addresses, decimals). `app:order-pay` polls the payment contract, validates amount ≥ order, contract address, and payer address, then `UserRechargeService::payCallback` credits via `WalletLog`. Statuses on `PayOrder`: WAIT → PAID / TIMEOUT / CANCEL / FAIL.
- **Alpha package purchase** (`PayOrder::TYPE_ALPHA_BUY`): `POST power/create` builds an `alpha_orders` row (WAIT) + a `pay_orders` row for direct chain payment (internal balance NOT debited). `app:order-pay` (or `POST power/done`) confirms → `AlphaOrderService::payCallback` activates the order (PAID) and calls `AlphaQuotaService::sweep` (new principal grows the slot → unfreezes frozen earnings). Within `trial_end` (30 days) the user may `POST power/exit` → refund 80% of principal via `WalletLog` (`TYPE_ALPHA_EXIT`), earnings kept. The order auto-completes (DONE) when its cumulative `rel_reward` reaches `quota_reward` (2.5× principal).
- **Withdraw**: `withdraws`. Create → freeze funds (`CHANGE_TYPE_FREEZE`) → mode `audit` (manual review in admin) or `contract` (needs project signature) → pending. `app:withdraw-scan` completes (deduct frozen via `CHANGE_TYPE_OUT`) or cancels (refund via `CHANGE_TYPE_BACK`) within 3 tries. Rejection refunds the freeze.

## Gotchas

- `WalletLog::SYMBOL_*` constants and `WalletLog::TYPE_*` codes are the source of truth for symbols/ledger types — add constants, don't inline strings. Alpha types: `TYPE_ALPHA_REWARD_IN=130`, `TYPE_ALPHA_EXIT=131`, `TYPE_ALPHA_UNFREEZE=132`. If a new type must appear in the user's 资金流水, add it to the user-facing maps `WalletLog::getTypeMapUser()`/`getTypeMapUserFilter()` too, not just `TYPE_MAP`.
- `User::getAllChilds` / team performance queries rely on the `user_relation_closures` closure table maintained by `UserRelationService` — direct `users.invited_uid` traversal is the fallback only.
- `app:order-pay` only scans orders created within the last 24h; `app:withdraw-scan` scans the last 24h with a 6-minute delay. Both use Redis `Cache::lock` (withdraw) to avoid overlap.
- `config('app.check.pay')` (env `APP_CHECK_PAY`, default true) switches the on-chain payment check to a mock "paid" response in `PowerController::done` and `OrderPay::getPayInfoFromContract` — used for dev/testing without a live RPC.
- `AlphaRewardService::run` skips a whole settle_date when every eligible PAID order already has a reward row (count-based idempotency); a partial per-order failure returns `false` and leaves that date recoverable via `app:alpha-reward <date> --skip`. Settlement runs `chunkById(100)` under `Cache::lock('alpha_reward_service', 1800)`.
- `user_datas` still carries legacy columns from the removed staking/DAO-node business: `node_id`/`node_at` and `total_stake`/`curr_stake`/`total_power`/`curr_power` (the tables were dropped, the columns were kept — `User::getDirectNodeCount`/`getTeamNodeCount` count by `node_id > 0`). Don't re-introduce node logic.
- The `DecryptAndVerifyRsaMessage` middleware and `Web3Service`/`Web3NewService` are legacy and effectively disabled — don't wire them into new routes.
- Env secrets to keep out of code/commits: `SIGN_KEY`, `WITHDRAW_KEY`, `APP_KEY`, `PAY_CONTRACT`/`WITHDRAW_CONTRACT` addresses, `ADMIN_ROUTE_PREFIX`.
