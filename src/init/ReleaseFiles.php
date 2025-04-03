<?php

namespace Wegar\Basic\init;

use Phar;
use Wegar\Basic\abstract\InitAbstract;
use Wegar\Basic\helper\CommandHelper;
use Wegar\Basic\helper\IOHelper;

class ReleaseFiles extends InitAbstract
{
  public int $weight = 0;

  function run(): void
  {
    if (is_phar()) {
      $command_helper = new CommandHelper();
      $phar = Phar::running();
      if ($phar && (config('extract.enable') || config('app.release'))) {
        $command_helper->notice("Releasing files...");
        $extract_list = config('extract.list', []) + config('app.release', []);
        foreach ($extract_list as $from => $to) {
          IOHelper::release($from, $to);
        }
        $command_helper->success("Release success.");
      }
    }
  }
}
