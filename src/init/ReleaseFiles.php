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
      if (Phar::running()) {
        $command_helper->notice("Releasing files...");
        $extract_list = config('extract.list', []) + config('app.release', []) + [
            '.env.example' => run_path(),
          ];
        foreach ($extract_list as $from => $to) {
          IOHelper::release($from, $to);
        }
        $command_helper->success("Release success.");
      }
    }
  }
}
