# AGENTS.md

## 项目定位

`wegar/basic` 是一个 **Webman 插件库**（composer type=library），本身**不可独立运行**：没有应用入口，测试跑在真实 webman 框架（其 helper 与 `support\Response|Model` 均为 `require` 硬依赖）上，且已配置 CI/lint。它被其他 webman 项目通过 composer 引入并在其进程/命令中生效。PHP 最低版本 **8.3**。

- PSR-4：`Wegar\Basic\` → `src/`
- composer `autoload.files` 依次加载 `src/functions_psr.php`（命名空间函数）与 `src/functions.php`（全局同名包装函数），二者**必须成对**修改。
- `src/functions_psr.php` 里的 `env()` 是本项目自定义实现（**不是** Laravel 的 env），支持引号、`true/false/null/empty`、JSON 与数字自动转换。

## 关键约定（容易改错）

- **配置类的命名空间故意不是 `Wegar\Basic`**：`src/config/plugin/wegar/basic/helper/SessionHelper.php` 用的是 `config\plugin\wegar\basic\helper`。`Install::install()` 会把 `src/config/plugin/wegar/basic` 整个复制到宿主项目的 `config/plugin/wegar/basic`（`Install.php:11`）。这里是给宿主项目**编辑扩展**的位置，不要把命名空间“修正”为 `Wegar\Basic`。
- `SessionHelper` 通过动态 `__call` 代理：类上新增 `$some_session_name` 属性即注册一个可管理 session，方法名 = 属性名 + 动作（`get/set/put/pull/has/exists/forget/delete`，`SessionHelper.php:31`）。
- README 中的 `Wegar\Basic\attribute\CronRule` 是笔误，实际是 **`Wegar\Basic\Attribute\CronRule`**（区分大小写）。
- `Command/Trait\Command.php` 与 `Helper/CommandHelper.php` 是两套高度重复的输出实现（Symfony Command 用前者，进程内日志用后者）。改一处时注意另一处。

## 启动 / 自动加载机制

进程配置 `src/config/plugin/wegar/basic/process.php` 注册 `InitProcess`，在启动时：

1. `InitHelper::load()` 扫描 `src/Init` 和宿主 `app/init`，按类上 `$weight`（默认 10，越小越先）排序执行 `run()`。**类名必须与文件名一致**，命名空间从文件内容正则探测（`InitHelper.php:55`）。
2. `CronHelper::load()` 扫描宿主 `app/cron`，对带 `#[CronRule('...')]` 的 `run()` 方法注册 `workerman/crontab`（需 `composer require workerman/crontab`，`CronHelper.php:42`）。
3. 内置 Init 有副作用：自动执行 Phinx **migrate + seed**（`Init/Phinx.php`）、创建 `.env.example`、`app/init`、`app/cron`、`phinx.php`、`database/migrations|seeds`（`Init/CheckFilesDirectories.php`）、phar 内释放文件（`Init/ReleaseFiles.php`）。
4. 大量逻辑用 `is_phar()` 分支；`config/app.php` 的 `build_release` 与 `extract.list` 控制 phar 释放映射。

## 常用命令

统一质量入口（本仓库有 composer scripts）：

- 全量检查：`composer check`（= `lint` → `analyse` → `test`）
- 单测：`composer test`（PHPUnit 12，`tests/Unit`，26 例）
- 静态分析：`composer analyse`（PHPStan level 5，历史问题在 `phpstan-baseline.neon`；新错误需修复，不要往 baseline 里塞）
- 风格：`composer lint`（PHP-CS-Fixer `--dry-run`，规则故意保持轻量、兼容 2 空格缩进）
- 单项语法检查：`php -l <file>`；依赖安装：`composer install`
- 测试无自定义桩：`tests/bootstrap.php` 只加载 composer autoload；webman helper 与 `support\Response|Model` 由硬依赖真实提供，直接使用即可。
- 在**宿主项目**中注册的命令：`php webman phinx ...`（代理 PhinxApplication）、`php webman wegar:basic:update`（交互式对比并升级 `config/plugin/wegar/basic` 文件，`Command/Updater.php:14`）

## 风格与提交

- `.editorconfig`：2 空格缩进、LF、UTF-8、行宽 120、文件末尾换行。
- 提交信息用中文 Conventional Commits：`type(scope): 简述`，如 `fix(config): 修复数据库配置中的命名空间转义问题`。已发布历史在 `main`，版本以 `v1.0.x` tag 标记。
- 代码注释极少；仅在必要处（如 phar 分支）保留。
