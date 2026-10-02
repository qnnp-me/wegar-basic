<?php

namespace Tests\Fixtures\InitEdge;

use RuntimeException;
use Wegar\Basic\Abstract\InitAbstract;

class ThrowingRunner extends InitAbstract
{
  public int $weight = 2;
  public static int $thrown = 0;

  public function run(): void
  {
    self::$thrown++;
    throw new RuntimeException('boom');
  }
}
