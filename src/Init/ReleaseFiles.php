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
   * 汇总待释放清单（纯逻辑，可单测）。宿主显式配置（`extract.list` / `app.build_release`）
   * 优先且允许覆盖；插件默认清单（`plugin.wegar.basic.app.release`）仅补齐未配置项，
   * 且不覆盖宿主已有文件。
   *
   * @param array<string, string> $host 宿主显式配置：from => to
   * @param array<string, string> $pluginDefaults 插件默认：from => to
   * @return array<string, array{to: string, overwrite: bool}>
   */
  public static function mergeReleaseList(array $host, array $pluginDefaults): array
  {
    $list = [];
    foreach ($host as $from => $to) {
      $list[$from] = ['to' => $to, 'overwrite' => true];
    }
    foreach ($pluginDefaults as $from => $to) {
      if (!isset($list[$from])) {
        $list[$from] = ['to' => $to, 'overwrite' => false];
      }
    }
    return $list;
  }
}
