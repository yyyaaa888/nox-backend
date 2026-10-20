# NOX Backend

NOX / "DTP" — 加密资产投资平台后端：钱包签名登录、算力资产包申购、每日静态收益（订单级 2.5× 封顶）、USDT/NOX 链上充提（BSC）。

## 技术栈

- Laravel 12 / PHP 8.2+（需要 `ext-bcmath`、`gmp`、`gd`）
- MySQL（测试用 sqlite `:memory:`）、Redis
- dcat/laravel-admin 后台、laravel/sanctum API、web3p/web3.php 链上交互

## 快速开始

```bash
composer install --no-dev
cp .env.example .env       # 配置数据库/Redis/SIGN_KEY 等
php artisan key:generate   # 生成应用密钥
php artisan storage:link   # 链接存储目录
php artisan migrate        # 迁移即 schema 来源；参考数据见 sql/nox.sql
composer dev               # php artisan serve + queue:listen + npm run dev
```

> 注意：仓库**没有内置业务 seeder**。参考数据以 `sql/nox.sql` 导入为准。

## 测试

```bash
composer test    # 即 php artisan config:clear && php artisan test
```

## 定时任务

`routes/console.php` 定义，由调度器（`php artisan schedule:run`）执行：

| 命令 | 说明 | 频率 |
|---|---|---|
| `app:order-pay` | 对账链上支付（资产包申购 + 节点申购） | 每分钟 |
| `app:withdraw-scan` | 对账合约提现 | 每分钟 |
| `app:alpha-reward {date?} {--skip}` | 资产包每日收益结算 | 每日 00:20 |

## 关键业务说明

- **账本模型**：所有余额变动必须走 `WalletLog::addLog()`（写 `asset_logs` + 更新 `user_datas`），禁止直接改 `user_datas` 余额列；金额一律 bcmath 字符串运算。
- **额度槽**：`AlphaQuotaService` 是统一收益记账入口，用户级 2.5× 额度槽（超槽冻结、复投解冻）。
- **资产包**：申购走链上直付（`power/create` → `pay_orders`），对账激活后每日结算，首月可退 80%。
