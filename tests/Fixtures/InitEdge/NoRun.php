<?php

namespace Tests\Fixtures\InitEdge;

use Tests\Fixtures\Init\Recorder;

class NoRun
{
  public int $weight = 0;

  public function __call(string $name, array $args): mixed
  {
    Recorder::add('NoRunCalled');

    return null;
  }
}
