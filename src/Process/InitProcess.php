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
      CronHelper::load(
        base_path('app' . DIRECTORY_SEPARATOR . 'cron'),
        'app\\cron\\'
      );
    }
  }
}
