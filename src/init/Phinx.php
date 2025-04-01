<?php

namespace Wegar\Basic\init;

use Wegar\Basic\abstract\InitAbstract;
use Wegar\Basic\helper\PhinxHelper;

class Phinx extends InitAbstract
{

  function run(): void
  {
    $phinx_file = base_path('phinx.php');
    PhinxHelper::load($phinx_file);
  }
}
