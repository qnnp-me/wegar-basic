# BACKLOG

> 仓库未接入工作项 tracker；「以后做 / 暂不做」的决策落库于此，避免丢失。
> 状态取值：`暂缓`（以后做）、`不做`（明确否决）。已完成项不写在此文件。

| ID | 状态 | 来源 | 内容 | 理由 / 备注 | 日期 |
|---|---|---|---|---|---|
| BL-001 | 暂缓 | 阶段一质量设施评审 | 测试方案剩余阶段：阶段四 Infection 变异 baseline + 门槛 + nightly 全量变异；阶段五 Roave BC + tag 验收 | 设计基线见 spec `docs/superpowers/specs/2026-09-21-testing-strategy-design.md`（唯一设计文档）。阶段一/二/三已落地：Unit 补齐、覆盖率真门禁（阈值 47%，CI 拦截）、集成套件 9/9 已进 CI（缓存 `.webman-host`） | 2026-09-21 |
| BL-002 | 暂缓 | 阶段一最终评审 | `env()` 数字类型不一致：`'1E3'` 经 `(int)` 得 `1000`（int）而非 `1000.0`（float），因 `str_contains($value,'e')` 仅匹配小写（`src/functions_psr.php:75-80`） | int/float 类型不一致；值的数值结果正确（`1000`）；属类型语义缺口，未掩盖行为错误 | 2026-09-21 |
| BL-010 | 暂缓 | 阶段一最终评审 | Codecov 上传未接入 | `--coverage-text` 与 clover artifact 已接入；Codecov 需外部 SaaS token，需要时再接 | 2026-09-21 |
| BL-014 | 暂缓 | 阶段二最终评审 | `SessionHelper::__call` 成功路径（`session()` 需 `request()`） | 控制台引导的宿主无真实 HTTP 上下文，`request()`/`session()` 返回空/null；需真实 HTTP 级 / 进程级测试 | 2026-09-21 |
| BL-015 | 暂缓 | 阶段二最终评审 | `json_error` 的 `DEBUG` 分支与 `error_with_status` 真值为 true 的 `withStatus` 分支（`src/functions_psr.php:18-29`） | 依赖 `request()->all()/header()/rawBuffer()` 真实 HTTP 上下文；需 HTTP 级测试 | 2026-09-21 |
| BL-016 | 暂缓 | 阶段三最终评审 | `IOHelper::release` 在 `is_phar()` 守卫前无条件执行 `new Phar(Phar::running())`；非 phar 下 `new Phar('')` 未被任何 catch 接住（`src/Helper/IOHelper.php:20-23`） | 当前唯一调用方 `Init/ReleaseFiles::run()` 有 `is_phar()` 守卫，实际触达不到；是否修由用户定；最小修复：把 `new Phar(...)` 移进 `if (is_phar())` 块 | 2026-09-21 |
| BL-017 | 暂缓 | 覆盖率门禁校准 | 覆盖率阈值当前 47%（实测基线 48.08%），随单测补齐逐步抬升至 spec 梯度目标（`src` 行覆盖 ≥85%） | 见 spec §3 L4；每次抬高前需实测确认 | 2026-09-25 |
