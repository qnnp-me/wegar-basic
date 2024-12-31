<?php

namespace Wegar\Basic\init;

use Wegar\Basic\abstract\InitAbstract;
use Wegar\Basic\helper\PhinxHelper;

class Phinx extends InitAbstract
{

  function run(): void
  {
    if (!file_exists(base_path('phinx.php'))) {
      return;
    }
    PhinxHelper::load(base_path('phinx.php'));
  }
}
