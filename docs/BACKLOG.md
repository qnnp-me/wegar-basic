# BACKLOG

> 仓库未接入工作项 tracker；「以后做 / 暂不做」的决策落库于此，避免丢失。
> 状态取值：`暂缓`（以后做）、`不做`（明确否决）。已完成项不写在此文件。

| ID | 状态 | 来源 | 内容 | 理由 / 备注 | 日期 |
|---|---|---|---|---|---|
| BL-001 | 暂缓 | 阶段一质量设施评审 | 集成测试：引导最小 webman 应用，端到端验证 Init 加载、Cron 注册、Phinx 自动 migrate/seed、phar 释放分支 | 阶段一先做静态检查 + 纯单元测试 + CI；集成测试会触及有副作用的 Init（Phinx/ReleaseFiles），成本高、待有真实 bug 驱动再做 | 2026-09-21 |
| BL-004 | 暂缓 | implementer 交付报告 | `InitHelperTest` 的 `ConsoleOutput` 直写 fd 1，`ob_start` 无法拦截，测试输出有 8 行噪音 | 不影响结果；彻底消除需改 `CommandHelper` 或在 OS 层重定向 fd，收益低 | 2026-09-21 |
| BL-005 | 暂缓 | 阶段一评审 | `phpstan-baseline.neon` 内 8 个历史问题（Phinx match 未覆盖、Updater 的 HelperInterface::ask、Trait 的 is_subclass_of、SessionHelper static/return 等） | 逐步消解后从 baseline 移除；新增错误禁止入 baseline | 2026-09-21 |
| BL-006 | 暂缓 | 阶段一评审 | `composer.lock` 被 `.gitignore` 忽略，CI `composer install` 实际执行 update，构建不可复现 | 库项目惯例不提交 lock；若要可复现 CI，需取舍后提交 lock | 2026-09-21 |
| BL-007 | 暂缓 | webman/admin 耦合评估 | 已移除 `PermissionTSGenerator`（及 `require-dev webman/admin`），属**破坏性变更**；发布时需定版本号（semver major/minor）与 release note | 已发布历史 `v1.0.x`；宿主若曾设 `permission_types_save_path` 将失效（仅遗留无效配置，无报错） | 2026-09-21 |
