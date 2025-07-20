<?php

namespace Wegar\Basic\Process;

use Wegar\Basic\Helper\CronHelper;
use Wegar\Basic\Helper\InitHelper;
use Workerman\Crontab\Crontab;

class InitProcess
{
  public function __construct()
  {
    InitHelper::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Init');
    InitHelper::load(base_path('app' . DIRECTORY_SEPARATOR . 'init'));
    if (class_exists(Crontab::class)) {
      $dir = base_path('app' . DIRECTORY_SEPARATOR . 'cron');
      if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
      }
      CronHelper::load(
        $dir,
        'app\\cron\\'
      );
    }
  }
}
