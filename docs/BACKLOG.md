# BACKLOG

> 仓库未接入工作项 tracker；「以后做 / 暂不做」的决策落库于此，避免丢失。
> 状态取值：`暂缓`（以后做）、`不做`（明确否决）。已完成项不写在此文件。

| ID | 状态 | 来源 | 内容 | 理由 / 备注 | 日期 |
|---|---|---|---|---|---|
| BL-001 | 暂缓 | 阶段一质量设施评审 | 剩余：阶段五 Roave BC + tag 验收 | 阶段四已落地（2026-10-03）：`infection.json5`（source=src、exclude src/config、只跑 Unit），本地 baseline Covered MSI 45%（412 变异/覆盖 100%），门槛 minMsi/minCoveredMsi=45，nightly `.github/workflows/nightly.yml` 跑全量变异。阶段五 Roave BC 8.x 需 PHP ~8.4，故未入 require-dev | 2026-10-03 |
| BL-010 | 暂缓 | 阶段一最终评审 | Codecov 上传：action 已接入，待配置 token | quality.yml unit job 已加 `codecov/codecov-action@v4`（files=clover.xml、fail_ci_if_error=false）；私有仓库需配置 `secrets.CODECOV_TOKEN`，公开仓库可无 | 2026-10-03 |
| BL-014 | 暂缓 | 阶段二最终评审 | `SessionHelper::__call` 成功路径（`session()` 需 `request()`） | 控制台引导的宿主无真实 HTTP 上下文，`request()`/`session()` 返回空/null；需真实 HTTP 级 / 进程级测试 | 2026-09-21 |
| BL-015 | 暂缓 | 阶段二最终评审 | `json_error` 的 `DEBUG` 分支与 `error_with_status` 真值为 true 的 `withStatus` 分支（`src/functions_psr.php:18-29`） | 依赖 `request()->all()/header()/rawBuffer()` 真实 HTTP 上下文；需 HTTP 级测试 | 2026-09-21 |
| BL-017 | 暂缓 | 覆盖率门禁校准 | 覆盖率继续抬升：门槛 85%，实测 91.99%（310/337） | 2026-10-03 口径修订（见 spec §L4）：排除 `src/config` + 9 个进程/CLI 边界文件（由集成子进程覆盖，pcov 采不到）；门槛 47%→85% 已达标。核心 Helper：CommandHelper 99%、IOHelper 96.6%、InitHelper 98%、DTO 100% 达标；待补 `Trait\Command` 89.5%、`functions_psr` 89.8%、`functions.php` 别名包装，再逐步抬高门槛 | 2026-10-03 |
| BL-018 | 暂缓 | 依赖评审 / 数据备份功能讨论 | 数据导出/备份功能（物理整库备份+恢复、业务数据导出） | 结论倾向拆为独立插件承载物理层：依赖 `spatie/db-dumper ^4` + 原生 mysqldump/pg_dump/sqlite3（支持 MySQL/MariaDB/PG/SQLite/Mongo，含 gzip/bzip2 压缩；无 restore，需自研反向导入）；逻辑导出零新依赖，可留 `wegar/basic`（illuminate + 原生 fputcsv）。未定：restore 是否进 v1、存储是否仅本地、是否独立插件。未落 spec | 2026-10-03 |
