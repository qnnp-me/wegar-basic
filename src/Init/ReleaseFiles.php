<?php

namespace Wegar\Basic\Init;

use Phar;
use Wegar\Basic\Abstract\InitAbstract;
use Wegar\Basic\Helper\CommandHelper;
use Wegar\Basic\Helper\IOHelper;

class ReleaseFiles extends InitAbstract
{
  public int $weight = 0;

  function run(): void
  {
    if (is_phar()) {
      $command_helper = new CommandHelper();
      if (Phar::running()) {
        $command_helper->notice("Releasing files...");
        $host = config('extract.list', []) + config('app.build_release', []);
        $defaults = config('plugin.wegar.basic.app.release', []);
        foreach (static::mergeReleaseList($host, $defaults) as $from => $entry) {
          IOHelper::release($from, $entry['to'], $entry['overwrite']);
        }
        $command_helper->success("Release success.");
      }
    }
  }

  /**
   * 合并待释放清单（纯逻辑，可单测）：宿主显式配置（`extract.list` / `app.build_release`）
   * 优先，同 `from` 以宿主为准；插件默认清单（`plugin.wegar.basic.app.release`）补齐其余项。
   *
   * 条目支持两种写法：
   * - 字符串：`'from' => to`，默认覆盖；
   * - 数组：`'from' => ['to' => to, 'overwrite' => false]`，可显式不覆盖（保留宿主已有文件）。
   *
   * @param array<string, string|array{to: string, overwrite?: bool}> $host
   * @param array<string, string|array{to: string, overwrite?: bool}> $pluginDefaults
   * @return array<string, array{to: string, overwrite: bool}>
   */
  public static function mergeReleaseList(array $host, array $pluginDefaults): array
  {
    $list = [];
    foreach ($host + $pluginDefaults as $from => $entry) {
      $list[$from] = self::normalizeEntry($entry);
    }
    return $list;
  }

  /**
   * @param string|array{to: string, overwrite?: bool} $entry
   * @return array{to: string, overwrite: bool}
   */
  private static function normalizeEntry(string|array $entry): array
  {
    if (is_array($entry)) {
      return [
        'to' => $entry['to'],
        'overwrite' => $entry['overwrite'] ?? true,
      ];
    }
    return ['to' => $entry, 'overwrite' => true];
  }
}
