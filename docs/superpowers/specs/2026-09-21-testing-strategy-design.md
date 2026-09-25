# wegar/basic 完整测试方案（Design Spec）

- 日期：2026-09-21
- 状态：已定稿（阶段一/二/三已落地，覆盖率门禁与集成 CI 已启用；阶段四变异、阶段五 Roave BC 待推进）
- 维护约定：本文档为唯一设计基线。已落地的阶段只更新状态/追加事实，**不重写正文、不再新增独立 plan 文件**；实现过程记录见 PR 描述或 issue。
- 范围：`wegar/basic` 插件库的全面质量工程（静态检查 → 单元 → 集成 → 覆盖率 → 变异 → CI 分层门禁）

## 1. 背景与现状

`wegar/basic` 是 webman 插件库（composer `type=library`），不可独立运行，被宿主 webman 项目 composer 引入。现状：

- 单元测试：7 个测试类、43 tests / 58 assertions，仅覆盖 5 个源文件（`env()`、`DTO`、`InitHelper` 部分、`IOHelper::scan_files`、`CommandHelper`、`Trait\Command`、`SessionHelper` 异常分支）。
- 集成测试：无。`docs/BACKLOG.md:7` BL-001 暂缓。
- 静态：PHPStan level 5（无 baseline，仅扫 `src`）、PHP-CS-Fixer（轻规则，扫 `src`+`tests`）。
- CI：`.github/workflows/quality.yml` 在 push/PR 上跑 PHP 8.3/8.4 矩阵的 `composer check`；**无覆盖率采集**。
- 覆盖率驱动：本机与 CI 均未安装 xdebug/pcov（`php -m` 无）。
- 关键依赖：`workerman/webman-framework`（helper 为全局函数，依赖全局常量 `BASE_PATH` 与 `support\Config`，见 `vendor/workerman/webman-framework/src/support/helpers.php:74,334`）、`webman/database`、`robmorgan/phinx`、`symfony/console`、`workerman/crontab`（dev）、`webman/redis-queue`（dev）。
- 无 mockery/faker 等替身库——既有的 I/O 测试用真实 Symfony 对象（`StringInput`/`BufferedOutput`），应保持"真实优先"。

## 2. 目标与非目标

### 目标
1. 将 `src/` 单元覆盖补到可度量、可门禁的水平。
2. 建立**真实 webman 宿主**上的集成测试，端到端验证 Install、Init、Cron、Phinx、Updater、ReleaseFiles、CheckFilesDirectories。
3. 引入覆盖率门槛与**变异测试**，防止弱断言与假绿。
4. CI 分层：PR 快、nightly 全、tag 验收。
5. 明确**测试替身保真**纪律，避免 mock 与真实行为漂移。

### 非目标
- 不追求 100% 覆盖；不测 HTTP 路由/中间件（本库无 HTTP 面）。
- 不为覆盖率而写无意义断言。
- 不引入重量级测试框架（保持 PHPUnit 12）。
- phar 只在本平台验证，不做跨平台发布物矩阵。

## 3. 分层设计

### L0 静态与规范（快线）
- PHPStan：L5 → 分阶段抬 L6（本仓库规模可控）；`src` 继续扫描，`tests` 视情另配。
- `composer validate --strict` 纳入门禁。
- PHP-CS-Fixer：保持现状（轻规则、兼容 2 空格）。
- **Roave Backward Compatibility Check**：作为被依赖的库，发版前对照 `main` 检查破坏性 API 变更（tag 门禁，不进 PR）。

### L1 单元（快线）
补齐 `src/` 未覆盖面：
- `src/functions_psr.php`：`json_error`/`json_success`（真实 `support\Response`）、`ss()`、`getAllFiles()`；`env()` 边界（非法 JSON、嵌套引号、多行）。
- `src/functions.php`：全局同名包装的**成对性**测试——与 psr 版签名/行为一致（`function_exists` 守卫生效）。
- `src/Helper/DTO.php`：null/缺失键、深层嵌套、数组含对象、`offsetExists`。
- `config\...\SessionHelper`：`__call` 全部动作（`get/set/put/pull/has/exists/forget/delete`）、多 session 属性、`__get`/`__set` happy path（现仅异常分支）。
- `src/Helper/CommandHelper.php` 与 `src/Trait/Command.php`：`error/warning/notice/alert/confirm/select/input/failed` 全表面 + `$quiet` 组合 + 颜色分支 + 构造守卫抛错分支。
- `src/Helper/IOHelper.php::release`：用临时 phar fixture（子进程 `php -d phar.readonly=0` 构建）。
- `src/Helper/InitHelper.php`：类不存在、命名空间探测失败、非 `InitAbstract`、weight 相等稳定序、目录不存在。
- `src/Abstract/InitAbstract.php`、`src/Attribute/CronRule.php`（属性反射读取）。

**阶段一实测调整（运行时依赖项下移）**：以下项依赖真实上下文，纯单元环境不可靠，移到对应阶段验证，不在单元层替身伪装：
- `SessionHelper` 的 `__call` 成功路径（`session()` 需 `request()`，见 `vendor/workerman/webman-framework/src/support/helpers.php:374`）；需要真实 HTTP request/session 上下文，console-boot 宿主无法提供，留待未来 HTTP 级 / 进程级测试（阶段三或独立任务）。
- `json_error` 的 `DEBUG` 分支与 `error_with_status` 真值为 true 的 withStatus 分支（`src/functions_psr.php:18-29`）；同样依赖 `request()->all()/header()/rawBuffer()` 的真实 HTTP 上下文，留待同上。
- `IOHelper::release` 的 phar 释放路径（需真实 phar，见 `src/Helper/IOHelper.php:22`），并入阶段三 phar 场景。

### L2 集成（真 webman 宿主，独立套件）

**状态：阶段二/三已落地（2026-09-21）。** 落地形态：`tests/Integration/{HostTestCase,HostSmokeTest,InstallTest,InitPipelineTest,CronRegistrationTest,PhinxMigrationTest,UpdaterTest,PharReleaseTest}.php`，入口命令 `composer test:integration`（前置 `composer host:setup`），运行在真实 `.webman-host` 之上。

已交付场景（覆盖原设计目标 1–7 全部 7 项，含 ReleaseFiles）：

1. **Install 全量复制 + 命名空间 + 幂等**（`InstallTest::testInstallCopiesPluginConfigWithPreservedNamespace`、`testReinstallIsIdempotent`）。
2. **Host 引导 + 插件命令注册**（`HostSmokeTest::testHostIsBootstrappableAndPluginCommandsRegistered`）。
3. **Init 流水线（artifact 创建 + 幂等）**（`InitPipelineTest::testInitProcessCreatesExpectedFiles`、`testSecondInitIsIdempotent`）。
4. **Cron 注册**（`CronRegistrationTest::testCronRuleClassIsRegistered`，断言 `Workerman\Crontab\Crontab::getAll()` 计数）。
5. **Phinx migrate + seed（sqlite）**（`PhinxMigrationTest::testMigrateAndSeedAgainstSqlite`，断言表行）。
6. **Updater 子进程交互**（`UpdaterTest::testUpdaterRestoresModifiedConfigFile`，还原被篡改的 `config/plugin/wegar/basic/helper/SessionHelper.php`）。
7. **ReleaseFiles / phar**（`PharReleaseTest::testBuildPharThenReleaseFilesOnRun`：宿主副本内 `php -d phar.readonly=0 webman build:phar` → `php build/webman.phar phar:init` → 断言哨兵文件经 `Phar::extractTo` 释放并内容匹配）。

阶段三已落地（2026-09-21）：原设计目标中的 **ReleaseFiles（phar）** 场景（`tests/Integration/PharReleaseTest::testBuildPharThenReleaseFilesOnRun`）。机制：宿主副本内 `php -d phar.readonly=0 webman build:phar` 产出 `build/webman.phar`；phar 内通过自定义命令（`app/command/PharInit.php` → `phar:init`）实例化 `Wegar\Basic\Process\InitProcess` 触发 `Init/ReleaseFiles::run()`；在 `config/app.php` 中配 `build_release = ['release-sentinel.txt' => run_path('phar-out')]`（webman `run_path` 在 phar 上下文解析为 `dirname(Phar::running(false)) . '/phar-out'`，即宿主的 `<host>/build/phar-out/`），断言哨兵文件被 `Phar::extractTo` 释放并内容匹配。

落地期间验证的 spike 事实（写入本节，避免再走一遍）：

- 宿主 = `workerman/webman` v2.2.x + `workerman/webman-framework` v2.2.x（`.webman-host/composer.lock`）。
- 宿主必须安装 `webman/console`、`workerman/crontab`、`webman/redis-queue` 才能跑齐命令/Cron/队列；缺失则命令列表缺项 / Cron 注册静默 0。
- Phinx 集成采用 sqlite（`HostTestCase::rewritePhinxToSqlite()` 重写 `phinx.php` 为 `runtime/init.sqlite`），跳过 Install 生成的 MySQL 配置。
- 集成的 Init 入口不是 `start.php start`，而是在已 `support/bootstrap.php` 引导的宿主体内 `new Wegar\Basic\Process\InitProcess()`（`HostTestCase::bootInit()`），避免起停 Workerman 服务。
- `sebastian/diff` 已是运行时 `require`（`composer.json`），原因：`Wegar\Basic\Command\Updater`（`src/Command/Updater.php:5-6,34-43`）通过 `SebastianBergmann\Diff\Differ` 做升级前后文件 diff 渲染；此前仅作为 PHPUnit 的传递依赖存在，由阶段二实现显式补齐为直接 `require`。
- 宿主副本必须**真实拷贝**而非软链：`php webman build:phar` 走 `Phar::buildFromDirectory`，会跟随 `vendor/weger/basic` 的 path repo 软链到 `.webman-host` 之外的仓库工作副本，触发 "iterator returned a path ... not in the base directory"。`HostTestCase::makeHostCopy()` 现以 `cp -a` 全量复制后扫描替换 path repo 越界软链为 `src/` + `composer.json` 真实子集；这同时消除了原 BL-015（vendor-symlink autoload 隐患，已随本轮 BL 重编号移除）的 vendor-symlink autoload 隐患（宿主 autoload 现指向副本内 vendor，`app\command\*` / `app\cron\*` 可被 composer 自动发现）。
- webman `build:phar` 的默认 `exclude_pattern` 命中 `*.git*` / `tests` / `build` 等。**当且仅当宿主副本所在路径含 `/build/` 段**时，`build/` 自身会被排除导致 `build/webman.phar` 写出失败。宿主副本在 `/tmp/wegar-it-...`（无 `/build/` 段），默认排除模式已可正常工作，无需 override；如未来把副本挪到含 `/build/` 的路径需再评估。
- `Wegar\Basic\Process\InitProcess` 的 `mkdir(app/cron, ...)` 调用在 phar 内会因 phar 写禁用（`Phar running with WRITE_ON_CLOSE disabled` 等）抛 `BadMethodCallException`/warning。已在 `src/Process/InitProcess.php` 用 `!is_phar()` 守卫跳过该 mkdir（phar 模式下 `app/cron` 不可写，`Init/CheckFilesDirectories` 本就只用于源码宿主场景），阶段三集成运行 `phar:init` 命令验证通过。

原设计目标：

在真实 `create-project` 宿主上验证端到端场景：

1. **Install**：`Install::installByRelation()` 将 `src/config/plugin/wegar/basic` 全量复制到宿主 `config/plugin/wegar/basic`，命名空间保持 `config\plugin\wegar\basic\*`；重复安装幂等。
2. **Init 加载**：宿主 `php webman`（控制台）加载插件 config/command；`InitHelper::load()` 按 weight 排序、扫描 `plugin/Init` + `app/init`。
3. **Cron 注册**：宿主 `app/cron` 放带 `#[CronRule]` 的类，`CronHelper::load()` 注册到 `Workerman\Crontab\Crontab`，断言注册结果（收集器/反射）。
4. **Phinx**：宿主 `phinx.php` 指 sqlite，放 migration+seed，跑 Init 后断言表结构与数据（真实 migrate+seed）。
5. **Updater**：子进程 `php webman wegar:basic:update`，喂交互输入，断言文件被升级/写出。
6. **ReleaseFiles（phar）**：子进程 `php -d phar.readonly=0` 构建带 `extract.list` 的 phar，运行后断言释放到宿主。
7. **CheckFilesDirectories**：断言 `.env.example`、`app/init`、`app/cron`、`phinx.php`、`database/migrations|seeds` 被创建且幂等。

约束：
- 有副作用/不可重入的 Init、Phinx、phar 场景在**独立进程**中执行（`php` 子进程或 `@runInSeparateProcess`）。
- 每个场景用宿主**副本或快照还原**隔离，防串扰。
- **不 `start.php start` 起停 Workerman 服务**（易抖）。用 `php webman` 控制台 + 宿主侧 `tests/init-boot.php`（真实加载 `support/bootstrap.php` 后调 `InitHelper::load()`）模拟真实 Init 入口；确需验证进程注册时，另加隔离的启动-停止冒烟并标注易抖。

### L3 夜间 / 发版验收（慢）
- nightly：全量变异 + 版本矩阵（PHP 8.3/8.4 × webman 1.6/2.x × `workerman/crontab` 版本）。集成套件（`composer test:integration`）已作为独立 job 跑在 CI（见 §7）。
- tag：`ReleaseFiles` 发布物验收 + Roave BC 检查 + 干净 `create-project` + path 安装本插件跑 L2 全场景 + Updater 真实升级路径。

### L4 覆盖率
- 安装 **pcov**（本地 + CI，较 xdebug 快），`phpunit --coverage-clover`（Codecov）+ `--coverage-text`。
- 阈值：**先跑基线**再设门槛。2026-09-25 实测基线：`src` 行覆盖 **48.75%**（`src/config` 已排除）。当前 `composer test:coverage` 门禁阈值 **47%**（`.github/workflows/quality.yml` 已移除 `continue-on-error`，CI 真拦截）；梯度目标维持 `src` 行覆盖 ≥85%、核心 Helper（DTO/InitHelper/CommandHelper）≥95%，随测试补齐逐步抬高。
- `<source>`/`<exclude>`：排除 `src/config/**` 及 phar 等不可达分支（显式列明理由）。
- **门禁现状（2026-09-25）**：`continue-on-error` 已移除，CI 与本地 `composer test:coverage` 同为严格门禁；阈值 47% 由实测基线校准，随覆盖提升再抬。CI 不再重复跑单测（`test:coverage` 内含 phpunit；BL-006）。本机容器为静态 PHP，安装 pcov 需 `brew install php` + `pecl install pcov`（无 pecl/phpize 时无法采集）。

### L5 变异测试
- `infection/infection ^0.35`（已验证支持 PHPUnit 12）。
- `infection.json5`：`source.directories=[src]`、`source.excludes=[src/config]`；`testFramework=phpunit` 且**只跑单测**（集成成本高，不参与变异）。
- 门槛：`minMsi`/`minCoveredMsi` 由 baseline 起设、逐次抬高。
- 运行：nightly 全量；本地/手动可用 `--git-diff-filter=AM --git-diff-lines` 只变异改动行提速。**PR 不跑变异**（与第 7 节一致）。
- **等价变异/不可达分支**（如 phar 分支）显式 ignore，且在 spec/配置中**书面记录理由**，禁止静默忽略。

## 4. 宿主策略：真实 `create-project` + 缓存

- `.webman-host`（gitignore）由真实 `composer create-project workerman/webman:^2` 产出；再用 path repository 指向本仓库工作副本 `composer require wegar/basic:@dev`，保证测的是当前代码。
- **建一次、缓存复用**：`composer host:setup` 显式创建/刷新；`composer test:integration` 复用已存在的宿主，缺失时提示先 setup。避免每次跑集成都重装（分钟级 + 网络）。
- 版本固定（webman 主版本 + 插件 path 约束），保证确定性；CI 用缓存键（宿主定义 / composer.lock）决定是否重建。
- 必要性：真宿主使"安装→引导→Init→命令"走真实加载路径，是最接近生产的验证；本地保留为独立套件，不污染快线。

## 5. 模拟保真（test-double fidelity）

原则：**默认真实优先；模拟只用于不可控边界（时间、网络、随机、有副作用的 IO），且必须被真宿主测试兜底。**

1. **不手写宿主骨架**：宿主由真实 `create-project` 产出，配置形状 = 真实 webman 骨架，从根上消除手写 fixture 漂移。
2. **不 mock 框架 API，用真对象**：真实 `StringInput`/`BufferedOutput`、`support\Response`、Phinx（sqlite）、`Crontab`。保持项目现有纪律（无 mockery）。
3. **替身契约断言**：每个 fixture/替身加一条测试，断言其 `extends`/`implements` 真实基类/接口（如 `InitAbstract`）、真实属性/attribute 存在。替身与真实类型脱节即红。
4. **fake↔real 配对清单**：单元层每一处被替代的路径，L2 必须有一条覆盖真实实现的对应测试；评审按清单核对。
5. **断言可观察结果，不测"是否调用"**：避免 mock 交互断言掩盖真实行为差异。
6. **版本契约**：单元对 helper 签名（`base_path`/`config`/`is_phar`）的假设，由集成在真实 webman 版本、跨版本矩阵上验证。
7. **可选黄金表**：关键映射（如 SessionHelper 动作名 → session 函数）用一张表同时喂真实现与替身，断言一致。

## 6. 命令入口

```bash
composer check              # 快线（PR 门禁）：lint → analyse → test(unit)
composer test               # 仅 Unit（保持现状，快）
composer host:setup         # 一次性：create-project 真 webman → .webman-host（gitignore）
composer test:integration   # 真宿主集成；宿主不存在则提示先 setup（CI 独立 job 缓存宿主后运行）
composer mutate             # Infection 变异测试
composer check:full         # 快线 + 集成 + 变异（本地发版前）
```

PHPUnit 新增 testsuite：`Unit` → `tests/Unit`，`Integration` → `tests/Integration`（`composer test` 不跑后者）。

## 7. CI 分层门禁

- **unit job（PR/推送，PHP 8.3/8.4 矩阵）**：`composer validate --strict` → `lint` → `analyse` → `test:coverage`（pcov，含 47% 阈值；`continue-on-error` 已移除，真拦截）。单测只跑一次。
- **integration job（PR/推送）**：`composer host:setup`（缓存 `.webman-host`，key=`composer.lock` + `tests/Tools/HostApp.php`）→ `composer test:integration`。缓存命中时数秒；未命中时联网 create-project。
- **nightly**：全量变异 + PHP/Webman 版本矩阵（阶段四起）。
- **tag**：Roave BC 检查 + phar 发布物验收（含干净 `create-project` + path 安装 + L2 全场景 + Updater 真实升级路径）。
- Codecov 上传暂未接入（见 `docs/BACKLOG.md`）。
- 缓存：composer 依赖缓存；`.webman-host` 缓存 + 宿主定义/`composer.lock` 变更时重建。

## 8. 分阶段落地（建议）

1. **阶段一**：覆盖率驱动（pcov）+ L1 单测补齐 + 覆盖率门槛；保持 PR 快线。
2. **阶段二**：✅ `composer host:setup` + L2 集成套件（Install/Init/Phinx/Cron）+ 模拟保真契约断言 + Updater 真实升级路径；见 §3 L2「阶段二/三已落地」。BL-001 备注更新（详见 `docs/BACKLOG.md`）。
3. **阶段三**：✅ ReleaseFiles / phar 集成场景 + 独立进程隔离（原 L2 场景 6）。机制与限制详见 §3 L2 阶段三已落地段。
4. **阶段四**：Infection baseline + 门槛 + nightly 全量变异 + 版本矩阵。
5. **阶段五**：Roave BC + tag 验收；关闭 BL-001（改为"方案已定、按阶段落地"）。

## 9. 风险与取舍

- **create-project 慢/需网**：以缓存复用 + 仅 nightly 全量缓解；本地按需 setup。
- **Workerman 服务起停易抖**：不用服务起停，改用控制台 + 宿主侧 init-boot 脚本。
- **有副作用 Init 污染**：独立进程 + 宿主副本/快照还原。
- **覆盖率/变异耗时**：变异只跑单测；必要时 diff-only；nightly 全量。
- **版本漂移**：CI 矩阵固定 webman 版本区间，锁定基线。

## 10. 验收标准

- `composer check` 快线通过，且 `src` 行覆盖达梯度目标。
- `composer test:integration` 在真实宿主上全绿，且覆盖第 3 节 L2 设计目标中的全部 7 个场景（含 ReleaseFiles/phar）。
- Infection baseline 报告产出，门槛写入配置并有书面理由记录 ignore 项。
- CI：PR 快线、nightly 全量、tag 验收三条流水线就位。
- `docs/BACKLOG.md` BL-001 状态更新为"方案已定、分阶段落地"。
- 新增命令写入 `AGENTS.md`。

## 11. 宿主/集成环境事实（spike 记录，2026-09-21/25）

从原阶段二/三 plan 收敛，避免重走：

1. `composer create-project workerman/webman` 可联网安装（v2.2.x / framework v2.2.x / workerman v5.x）；宿主须显式 `require webman/console`、`workerman/crontab`、`webman/redis-queue`，否则命令列表缺项 / Cron 注册静默 0。
2. path repo 安装 `wegar/basic` 时，webman composer 插件钩子会调 `Wegar\Basic\Install::install()`，自动创建 `config/plugin/wegar/basic`、`.env.example`、`phinx.php`、`app/init`、`database/migrations`、`database/seeds`，并注册 `phinx` 与 `wegar:basic:update` 命令。path repo 必须 `symlink:false`（真实文件），否则 `build:phar` 越界报错。
3. `app/cron` 仅在宿主存在 `Workerman\Crontab\Crontab` 类时创建 → 宿主须装 `workerman/crontab`；redis-queue 消费目录同理须装 `webman/redis-queue`。
4. 生成的 `phinx.php` 默认 **mysql** 适配器 → 集成测试改写为 sqlite（`HostTestCase::rewritePhinxToSqlite()`，`runtime/init.sqlite`）。
5. `php webman` 控制台**不会**跑 `InitProcess`；Init 只在 Workerman 进程启动时执行。集成入口用宿主 boot 脚本实例化 `InitProcess`，且必须 `chdir(宿主根)` 后再 require（`BASE_PATH` 由 `helpers.php` 依据 `getcwd()` 推导）。
6. `php webman build:phar`（`BuildPharCommand`）需 `-d phar.readonly=0`；默认 `exclude_pattern` 含 `|/build/`，测试副本位于 `/tmp/wegar-it-*` 不含 `/build/`，默认即可打包。phar 内释放目标用 `run_path(...)`（= `dirname(Phar::running(false))`），不要用 `base_path()`。
7. phar 内 `InitProcess` 的 `mkdir(app/cron)` 因 phar 写禁用会抛错，已用 `!is_phar()` 守卫跳过（见 `src/Process/InitProcess.php`）。
8. `runHost` 通过 `proc_open` 合并 stderr 到 stdout（`2 => ['redirect', 1]`），循环内持续读取，无独立双管死锁风险。
