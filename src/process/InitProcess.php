<?php

namespace Wegar\Basic\process;

use Exception;
use ReflectionClass;
use Wegar\Basic\attribute\CronRule;
use Wegar\Basic\helper\CommandHelper;
use Wegar\Basic\helper\InitHelper;
use Workerman\Crontab\Crontab;

class InitProcess
{

  public function __construct()
  {
    InitHelper::loadInitFiles(
      dirname(__DIR__) . DIRECTORY_SEPARATOR . 'init',
      'Wegar\\Basic\\init\\'
    );
    InitHelper::loadInitFiles(base_path('app' . DIRECTORY_SEPARATOR . 'init'));
  }

  function onWorkerStart(): void
  {
    $command_helper = new CommandHelper();
    $path = base_path('app/cron');
    if (!file_exists($path)) {
      return;
    }
    $files = scandir($path);
    $command_helper->notice('Loading Crontab...');
    foreach ($files as $file) {
      $class = 'app\\cron\\' . str_replace('.php', '', $file);
      if (
        str_ends_with($class, "Cron")
        && class_exists($class)
        && ($ref = new ReflectionClass($class))->hasMethod('run')
      ) {
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
            } catch (Exception $e) {
              $command_helper->error("add Crontab Error: $class -> " . $e->getMessage());
            }
          }
        }
      }
    }
    $command_helper->info('Crontab loaded.');
  }
}
