<?php

namespace Tests\Fixtures\Init;

final class Recorder
{
  public static array $order = [];

  public static function add(string $name): void
  {
    self::$order[] = $name;
  }

  public static function reset(): void
  {
    self::$order = [];
  }
}