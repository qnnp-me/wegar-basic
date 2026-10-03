# Changelog

本仓库的发布记录。版本按 [Semantic Versioning](https://semver.org/)，
且遵循本仓库 `composer.json` 的 `php >= 8.3` 约束。

> 注：`v2.0.0`–`v2.0.3` 与 `v1.0.51`–`v1.0.53` 实为同一批提交的双线标签。自 `v2.1.0`
> 起仅发 v2 线：本版已将 webman/workerman 收敛到 `^2.0`、放弃装不上的 1.x。

## [2.1.0] - 2026-10-03

新增 `.env` 优化命令与 Phar 释放增强，并放宽/清理依赖。对下游基本兼容；仍需注意依赖契约收敛到 webman/workerman 2.x。

### Added

- **`wegar:basic:env` 命令**：扫描 `config/`（默认跳过 `config/plugin`），挑出连接/凭据类
  字面量并生成/更新 `.env.example`。默认只预演；加 `--write` 才把 config 字面量改写为
  `env('KEY', 原值)`，改写前原文件备份为 `*.bak`。
- **Phar 释放增强**：插件默认把 `database/` 释放到 `runtime_path('phinx')`，与 `phinx.php`
  的 phar 路径（`runtime_path('phinx/database/migrations|seeds')`）一致；释放条目支持
  `['to' => .., 'overwrite' => false]` 只补缺失、不覆盖宿主已有文件。

### Changed

- 依赖放宽到兼容最新：`monolog ^2.10 || ^3.0`、`symfony/console ^6.4 || ^7.0 || ^8.0`、
  `vlucas/phpdotenv ^4.0 || ^5.0`、`sebastian/diff ^6 || ^7 || ^8 || ^9`、
  `phpunit ^12.1 || ^13.0`（dev）。
- webman/workerman 相关收敛至 `^2.0`（放弃装不上的 1.x）。
- 插件自带 `app.php` 的 `enable` 改为 `env('WEGAR_BASIC_ENABLE', true)`。
- 覆盖率门禁口径修订（排除进程/CLI 边界文件，由集成套件行为覆盖）并抬升至 **85%**
  （实测 91%）。

### Removed

- 移除零引用的 `spatie/db-dumper`。

### Fixed

- `IOHelper::scan_files()` 单文件路径也套用 `include`/`exclude` 过滤。

### Internal

- CI 新增 latest 依赖组合 job（PHPUnit 13 / symfony 8 / monolog 3 等）；接入 Infection
  变异测试（nightly）与 Codecov；新增 `.gitattributes` export-ignore 与 composer `suggest`。

## [1.0.53] - 2026-10-02

修复两处缺陷并加固测试。对下游基本无影响；唯一可能的行为变化见 `scan_files`
一条（仅当调用方同时传 `include` 与 `exclude` 时）。

### Fixed

- **`IOHelper::scan_files()` 的 include/exclude 组合语义**：原实现是异或判断，
  会把「命中 `exclude` 但未命中 `include`」的文件错误保留。现改为白名单
  （`include`）先筛、黑名单（`exclude`）再剔除，即
  `include_match && !exclude_match`。仓库内现有调用只传 `include`，不受影响。

- **守护模式下错误被静默丢弃**：Workerman `-d` 启动会
  `fclose(STDOUT)` 与 `fclose(STDERR)`，`ConsoleOutput` 构造失败退化为
  `NullOutput`，`CommandHelper::error()/failed()` 仅写控制台，导致 init / cron /
  文件释放等错误彻底丢失（`error_log` 默认写已关闭的 stderr，同样无效）。
  现仅在控制台降级时改落 webman 的 `support\Log`（Monolog 文件，可存活于
  守护进程），兜底 `error_log`，`failed()` 映射为 critical；任何落点失败都被
  吞掉，绝不影响主流程。

### Tests

- 加固弱断言：`testThrowingRunIsCaught` 增加计数证明确实抛出；`IOHelperTest`
  释放用例改为真实 no-op 校验；显式 namespace 用例更名并注明其契约属性。
- 新增守护模式错误落点回归，覆盖注入 sink 与真实 `support\Log` 两条路径。

## [1.0.52] - 2026-10-02

仅修复测试与 CI，无生产代码变更，对下游无行为影响。

### Tests

- 修复 `unit` 矩阵（PHP 8.3/8.4）上 3 个颜色用例失败：
  `CommandHelperTest::testColorAndBgColorWrapText`、
  `CommandTraitTest::testColorContainsText` / `testBgColorWrapsText`。
  根因是这些用例断言数字色号产生的 ANSI 转义，而
  `php-console-color` 的 256 色判断依赖 `getenv('TERM')` 含 `256color`；
  CI runner 未设置 `TERM`，颜色退化为纯文本。现通过 `phpunit.xml` 的
  `<env name="TERM" value="xterm-256color" force="true"/>` 固定测试进程
  环境，断言不再依赖运行环境。

## [1.0.51] - 2026-10-02

修复两个下游真实故障（`project.datahub-business` 报告，SJTPL-103），
不引入破坏性变更，下游 `composer update wegar/basic` 即可修复。

### Fixed

- **守护模式 `-d` 启动崩溃**：`CommandHelper::__construct()` 在
  STDOUT 已关闭的 Workerman 守护进程下，会因
  `PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor` 的
  `posix_isatty()` 抛 `TypeError` 而让异常蔓延到 `InitProcess`，
  `plugin.wegar.basic.init` worker 以 exit 64000 被反复重启。
  现已在库内把 `new ConsoleColor()` 与 `new ConsoleOutput()` 包进
  try-catch，失败时 `color()/bgColor()` 降级为纯文本，
  `write()` 降级为静默 `NullOutput`，不再依赖第三方库改动。

- **`app/init/` 含非 PHP 文件时 init 静默跳过**：当 `app/init/` 下存在
  `.gitkeep` / `.md` 等非 PHP 文件且按 `scandir` 字典序排在 `.php` 前
  时，`InitHelper::prepare_init_functions()` 的命名空间推导留空，
  导致整批 init 类被解析为不存在的类名并被静默跳过。现已在
  `IOHelper::scan_files()` 入口显式 `include: '*.php'` 过滤，并在
  `prepare_init_functions()` 内继续兜底跳过非 `.php`；空 init 集合
  也会产生可见 `notice`。

### Tests

新增 3 个单测与 1 个集成测试文件（`tests/Integration/DefectRegressionTest.php`）
覆盖以上两条修复路径；`composer check`（validate / lint / analyse / unit）
与 `composer test:integration` 在 PHP 8.5.7 全绿。

## [1.0.50] 及更早

历史 tag 见 `git tag -l`。
