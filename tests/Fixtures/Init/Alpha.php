<?php

namespace Tests\Fixtures\Init;

class Alpha
{
  public int $weight = 20;

  public function run(): void
  {
    Recorder::add('Alpha');
  }
}