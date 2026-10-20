# NOX Alpha 子项目 5：多语言调整（简/繁/越/日/泰/英）— 设计文档

日期：2026-09-02
前置：子项目 1-4 已上线（master `9b16d7f`）。现有多语言机制本身已完善，**本子项目不做功能重构**，只做两件事：①调整语言种类（补泰语、修繁体映射）；②补齐用户 API 可见的翻译。

范围：用户 API 提示文案（`__()` / `lang/{locale}.json`）。**不包括** DB 内容（文章/公告/产品文案）多语言、用户级语言偏好、admin 面板本地化。

## 1. 背景与目标

- 目标语言 6 种：简体（zh-CN）、繁体（zh-TW）、越南（vi）、日（ja）、泰（th）、英（en）。
- 现状：`Language` 中间件精确匹配 `accept-language`；`zh-TW` 头映射到 `zh_CN`（繁体用户实际拿简体）；`th` 完全缺失（无 `lang/th.json`、无映射）。
- 翻译机制：源码用中文做 key，`__('中文')` → `lang/{locale}.json` 解析，未命中回退中文原文。
- 规范 key 集合（全集）：**208 个**（150 个代码 `__()` key + 58 个仅存在于 JSON 的历史 key）。**61 个 key 在所有语言文件中缺失**（当前回退中文），需补齐。

## 2. 已确认的规则

| 项 | 内容 |
|---|---|
| 语言集合 | zh-CN / zh-TW / vi / ja / th / en 六种；`ko`/`ms`/`es` 保持现状（不做删除，不做解析重构） |
| 语言选择 | 仅 `accept-language` 请求头，无用户级偏好 |
| 本地化范围 | 仅用户 API 可见的 `__()` 提示文案；DB 内容保持中文 |
| 繁体映射 | `zh-TW` 改为映射 `zh_TW`（行为变化，已确认） |
| 泰语 | 中间件新增 `th`，新建 `lang/th.json` |
| 占位符 | 3 个 `:amount` 占位符 key 在所有语言中保留占位符 |
| 代码修正 | `MyTeamController.php:123` 硬编码中文包 `__()`（已确认） |

## 3. 语言文件数据

### 3.1 规范 key 全集（208）
来源：`__()` 代码字面量 150 + `lang/zh_CN.json` 既有 key 147 的并集（含 58 个历史/legacy key）。本子项目以该并集为翻译完整性基准。无动态 `__($var)` 调用（全部静态字面量，可静态校验）。

3 个占位符 key（翻译时必须保留 `:amount`）：
- `提现金额超限，剩余个人可提现金额为 :amount`
- `提现金额超限，剩余全站可提现金额为 :amount`
- `本周提现金额超限，剩余可提现金额为 :amount`

### 3.2 各语言文件处理
| 文件 | 处理 |
|---|---|
| `lang/zh_CN.json` | 补 61 个代码 key（中文恒等），全集达 208 |
| `lang/zh_TW.json` | 补 61 个 key 繁体翻译 |
| `lang/vi.json` | 补 61 个 key 越南语翻译 |
| `lang/ja.json` | 补 61 个 key 日语翻译 |
| `lang/en.json` | 补 61 个 key 英语翻译 |
| `lang/th.json`（新建） | 208 个 key 完整泰语翻译 |

## 4. 中间件调整（`app/Http/Middleware/Language.php`）

仅改映射表 `LANGS` 两行：
- `'zh-TW' => 'zh_CN'` → `'zh-TW' => 'zh_TW'`
- 新增 `'th' => 'th'`

不做 accept-language 解析重构（`en-US` 等带地区后缀的精确匹配行为保持现状）、不删 `ko`/`ms`/`es`、不动 `DEFAULT_LANG`。

## 5. 代码修正

- `app/Http/Controllers/Api/MyTeamController.php:123`：`apiError('用户不存在或关系不存在')` → `apiError(__('用户不存在或关系不存在'))`（该 key 一并加入语言文件，使此文案可翻译）。

## 6. 测试（Pest）

- **`LanguageMiddlewareTest`**：中间件映射——`th` → `th`、`zh-TW` → `zh_TW`、`zh-CN` → `zh_CN`、`en` → `en`、未支持语言 → `zh_CN`（默认）。现有 `ko`/`ms`/`es` 映射保持断言。
- **`TranslationCompletenessTest`**：
  - 从 `app/` 静态抽取全部 `__('literal')` key，与 `lang/zh_CN.json` 并集得到规范全集；
  - 对 6 个语言文件逐一断言：全集中每个 key 均存在；
  - 3 个占位符 key 在 en/th/vi/ja/zh_TW 中的值包含 `:amount`；
  - 抽样断言：设置 locale 为 `th`/`en` 后 `__()` 返回非中文（验证文件可加载、locale 生效）。
- **回归**：子项目 1-4 全部用例通过。

## 7. 不在本子项目范围内（后续）

- DB 内容（`articles`/`announcements`/`alpha_products` 等内容列）按语言维护。
- 用户级语言偏好（`users` 存偏好 + 切换接口）。
- admin（dcat）面板本地化。
- `ko`/`ms`/`es` 语言支持调整。
- accept-language 带地区后缀（`en-US`/`th-TH`/`vi-VN`）的解析改进。
