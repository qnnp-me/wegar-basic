<?php

namespace Tests\Fixtures\Init;

class Beta
{
  public int $weight = 5;

  public function run(): void
  {
    Recorder::add('Beta');
  }
}