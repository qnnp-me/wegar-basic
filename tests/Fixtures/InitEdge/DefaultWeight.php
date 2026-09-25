<?php

namespace Tests\Fixtures\InitEdge;

use Tests\Fixtures\Init\Recorder;

class DefaultWeight
{
  public function run(): void
  {
    Recorder::add('Default');
  }
}
