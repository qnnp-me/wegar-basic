<?php

namespace Wegar\Basic\Init;

use Wegar\Basic\Abstract\InitAbstract;
use Wegar\Basic\Helper\PhinxHelper;

class Phinx extends InitAbstract
{

  function run(): void
  {
    $phinx_file = base_path('phinx.php');
    PhinxHelper::load($phinx_file);
  }
}
