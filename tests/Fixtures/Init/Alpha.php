<?php

namespace Tests\Fixtures\Init;

use Wegar\Basic\Abstract\InitAbstract;

class Alpha extends InitAbstract
{
  public int $weight = 20;

  public function run(): void
  {
    Recorder::add('Alpha');
  }
}
