<?php

namespace Wegar\Basic\Process;

use Wegar\Basic\Helper\CronHelper;
use Wegar\Basic\Helper\InitHelper;
use Workerman\Crontab\Crontab;

class InitProcess
{
  public function __construct()
  {
    /**
     * 加载插件的初始化脚本
     */
    InitHelper::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Init');
    /**
     * 加载系统的初始化脚本
     */
    InitHelper::load(base_path('app' . DIRECTORY_SEPARATOR . 'init'));
    /**
     * 加载系统的定时任务
     */
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
