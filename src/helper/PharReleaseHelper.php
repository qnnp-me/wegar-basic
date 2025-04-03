<?php

namespace Wegar\Basic\helper;

use Phar;

class PharReleaseHelper
{
  static function release(string $from, string $to, $overwrite = true): void
  {
    if (is_phar()) {
      $command_helper = new CommandHelper();
      $phar = Phar::running();
      if ($phar) {
        $phar = new Phar($phar);
        $command_helper->info("Release $from to $to");
        $phar->extractTo($to, $from, $overwrite);
      }
    }
  }
}