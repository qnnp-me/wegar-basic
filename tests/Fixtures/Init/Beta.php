<?php

namespace Tests\Fixtures\Init;

use Wegar\Basic\Abstract\InitAbstract;

class Beta extends InitAbstract
{
  public int $weight = 5;

  public function run(): void
  {
    Recorder::add('Beta');
  }
}
