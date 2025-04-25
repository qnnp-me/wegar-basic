<?php

namespace Wegar\Basic\Abstract;

abstract class InitAbstract
{
  public int $weight = 10;

  abstract public function run(): void;
}
