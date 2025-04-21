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
        $extract_list = config('extract.list', []) + config('app.build_release', []);
        foreach ($extract_list as $from => $to) {
          IOHelper::release($from, $to);
        }
        $command_helper->success("Release success.");
      }
    }
  }
}
