# BACKLOG

> 仓库未接入工作项 tracker；「以后做 / 暂不做」的决策落库于此，避免丢失。
> 状态取值：`暂缓`（以后做）、`不做`（明确否决）。已完成项不写在此文件。

| ID | 状态 | 来源 | 内容 | 理由 / 备注 | 日期 |
|---|---|---|---|---|---|
| BL-001 | 暂缓 | 阶段一质量设施评审 | 集成测试：引导最小 webman 应用，端到端验证 Init 加载、Cron 注册、Phinx 自动 migrate/seed、phar 释放分支 | 阶段一先做静态检查 + 纯单元测试 + CI；集成测试会触及有副作用的 Init（Phinx/ReleaseFiles），成本高、待有真实 bug 驱动再做 | 2026-09-21 |
| BL-007 | 暂缓 | webman/admin 耦合评估 | 已移除 `PermissionTSGenerator`（及 `require-dev webman/admin`），属**破坏性变更**；发布时需定版本号（semver major/minor）与 release note | 已发布历史 `v1.0.x`；宿主若曾设 `permission_types_save_path` 将失效（仅遗留无效配置，无报错） | 2026-09-21 |
