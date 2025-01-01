<?php

namespace Wegar\Basic\init;

use Wegar\Basic\abstract\InitAbstract;
use Wegar\Basic\helper\PhinxHelper;

class Phinx extends InitAbstract
{

  function run(): void
  {
    PhinxHelper::load(base_path('phinx.php'));
  }
}
