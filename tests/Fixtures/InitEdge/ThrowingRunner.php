<?php

namespace Tests\Fixtures\InitEdge;

use RuntimeException;
use Wegar\Basic\Abstract\InitAbstract;

class ThrowingRunner extends InitAbstract
{
  public int $weight = 2;

  public function run(): void
  {
    throw new RuntimeException('boom');
  }
}
