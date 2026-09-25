<?php

namespace Tests\Fixtures\InitEdge;

use Tests\Fixtures\Init\Recorder;

class StaticRunner
{
  public int $weight = 1;

  public static function run(): void
  {
    Recorder::add('Static');
  }
}
