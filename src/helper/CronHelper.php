<?php

namespace Wegar\Basic\helper;

use ReflectionClass;
use Throwable;
use Wegar\Basic\attribute\CronRule;
use Workerman\Crontab\Crontab;

class CronHelper
{
  static function load(string $cron_dir, string $namespace): void
  {
    $command_helper = new CommandHelper();
    if (!file_exists($cron_dir)) {
      $command_helper->error("Crontab dir not exists: $cron_dir");
      return;
    }
    $files = scandir($cron_dir);
    $command_helper->notice("Loading Crontab -> $cron_dir");
    foreach ($files as $file) {
      $class = $namespace . str_replace('.php', '', $file);
      if (class_exists($class) && ($ref = new ReflectionClass($class))->hasMethod('run')) {
        $method = $ref->getMethod('run');
        $attrs = $method->getAttributes(CronRule::class);
        $is_static = $method->isStatic();
        foreach ($attrs as $attr) {
          $rule = $attr->getArguments()[0] ?? false;
          if ($rule) {
            try {
              new Crontab($rule, [$is_static ? $class : $ref->newInstance(), 'run']);
              $rule_str = str_pad($rule, 20, ' ', STR_PAD_BOTH);
              $command_helper->info("Crontab: [$rule_str] <- $class");
            } catch (Throwable $e) {
              $command_helper->error("add Crontab Error: $class -> " . $e->getMessage());
            }
          }
        }
      }
    }
    $command_helper->info("Crontab loaded -> $cron_dir");

  }
}