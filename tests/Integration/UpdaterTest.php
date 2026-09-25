<?php

namespace Tests\Integration;

/**
 * 验证 `wegar:basic:update` 命令作为 webman CLI 子进程运行时：
 *   - 能通过交互提示走完全流程
 *   - 能把被改坏的插件配置文件还原回插件源版本
 *
 * 交互协议（来自 `src/Command/Updater.php`）：
 *   1. `Trait\Command::alert()`（Updater.php:45）→ 一个 `ConfirmationQuestion(" ⏎ : ")`，吞一行输入
 *   2. `$this->confirm('是否选择删除自定义的新文件?', true)`（Updater.php:57）→ `Y/n`，默认 true，吞一行输入
 *   3. 对每个有差异的源文件 `confirmUpdateFile()`（Updater.php:193）→
 *      `confirm("是否更新 /<rel_path> ?", true)`，`Y/n`，默认 true，吞一行输入
 *
 * 当目标目录与源目录的文件列表相同时，删除与新增列表为空，所以总共 3 行 stdin。
 * 用空行喂入即可触发每个 ConfirmationQuestion 的默认值（true / 吞回车）。
 */
class UpdaterTest extends HostTestCase
{
  private const TARGET_REL = 'config/plugin/wegar/basic/helper/SessionHelper.php';

  public function testUpdaterRestoresModifiedConfigFile(): void
  {
    $target = $this->hostDir() . '/' . self::TARGET_REL;
    $original = (string) file_get_contents($target);
    $this->assertNotSame('', $original, 'host must ship helper/SessionHelper.php');

    file_put_contents($target, "<?php\n// tampered by integration test\n");
    $this->assertNotSame($original, (string) file_get_contents($target));

    // alert + delete-confirm + file-confirm，三个 ConfirmationQuestion 各吞一行默认输入
    [$exit, $out] = $this->runHost(['php', 'webman', 'wegar:basic:update'], "\n\n\n");

    $this->assertSame(0, $exit, $out);
    $this->assertSame($original, (string) file_get_contents($target), $out);
  }
}
