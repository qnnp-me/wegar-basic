<?php

namespace Wegar\Basic\Helper;

use ReflectionClass;
use ReflectionException;
use Throwable;
use Wegar\Basic\Attribute\CronRule;
use Workerman\Crontab\Crontab;

class CronHelper
{
  static function load(string $cron_dir, string $namespace): void
  {
    $command_helper = new CommandHelper();
    if (!file_exists($cron_dir)) {
      $command_helper->error("Crontab dir not exists: " . str_replace(base_path(), '', $cron_dir));
      return;
    }
    $files = scandir($cron_dir);
    $command_helper->notice("Loading Crontab -> " . str_replace(base_path(), '', $cron_dir));
    foreach ($files as $file) {
      $class = $namespace . str_replace('.php', '', $file);
      if (class_exists($class) && ($ref = new ReflectionClass($class))->hasMethod('run')) {
        try {
          $method = $ref->getMethod('run');
        } catch (ReflectionException $e) {
          $command_helper->error("Load Crontab Failed: $class -> " . $e->getMessage());
          continue;
        }
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
              $command_helper->error("Add Crontab Failed: $class -> " . $e->getMessage());
            }
          }
        }
      }
    }
    $command_helper->info("Crontab loaded -> " . str_replace(base_path(), '', $cron_dir));

  }
}