<?php

namespace Wegar\Basic\process;

use Wegar\Basic\helper\CronHelper;
use Wegar\Basic\helper\InitHelper;
use Workerman\Crontab\Crontab;

class InitProcess
{
  public function __construct()
  {
    InitHelper::load(
      dirname(__DIR__) . DIRECTORY_SEPARATOR . 'init',
      'Wegar\\Basic\\init\\'
    );
    InitHelper::load(
      base_path('app' . DIRECTORY_SEPARATOR . 'init')
    );
    if (class_exists(Crontab::class)) {
      CronHelper::load(
        base_path('app' . DIRECTORY_SEPARATOR . 'cron'),
        'app\\cron\\'
      );
    }
  }
}
