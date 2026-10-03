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
        foreach (static::mergeReleaseList($host, $defaults) as $from => $to) {
          IOHelper::release($from, $to);
        }
        $command_helper->success("Release success.");
      }
    }
  }

  /**
   * 合并待释放清单（纯逻辑，可单测）：宿主显式配置（`extract.list` / `app.build_release`）
   * 优先，同 `from` 以宿主为准；插件默认清单（`plugin.wegar.basic.app.release`）补齐其余项。
   *
   * @param array<string, string> $host 宿主显式配置：from => to
   * @param array<string, string> $pluginDefaults 插件默认：from => to
   * @return array<string, string>
   */
  public static function mergeReleaseList(array $host, array $pluginDefaults): array
  {
    return $host + $pluginDefaults;
  }
}
