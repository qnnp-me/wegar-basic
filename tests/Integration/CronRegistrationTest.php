<?php

namespace Tests\Integration;

class CronRegistrationTest extends HostTestCase
{
  public function testCronRuleClassIsRegistered(): void
  {
    $dir = $this->hostDir();
    file_put_contents($dir . '/app/cron/DemoCron.php', <<<'PHP'
<?php

namespace app\cron;

use Wegar\Basic\Attribute\CronRule;

class DemoCron
{
  #[CronRule('* * * * *')]
  public function run(): void
  {
  }
}
PHP);

    $script = $dir . '/cron-boot.php';
    file_put_contents($script, <<<'PHP'
<?php
chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/support/bootstrap.php';
\Wegar\Basic\Helper\CommandHelper::$quiet = true;
\Wegar\Basic\Helper\CronHelper::load(__DIR__ . '/app/cron', 'app\\cron\\');
$tasks = \Workerman\Crontab\Crontab::getAll();
echo "CRON_COUNT=" . count($tasks) . "\n";
PHP);
    [$exit, $out] = $this->runHost(['php', 'cron-boot.php']);
    $this->assertSame(0, $exit, $out);
    $this->assertStringContainsString('CRON_COUNT=1', $out);
  }
}
