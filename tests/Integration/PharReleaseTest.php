<?php

namespace Tests\Integration;

class PharReleaseTest extends HostTestCase
{
  public function testBuildPharThenReleaseFilesOnRun(): void
  {
    $dir = $this->hostDir();
    $sentinel = 'PHAR-SENTINEL-' . bin2hex(random_bytes(6));

    file_put_contents($dir . '/release-sentinel.txt', $sentinel . "\n");

    $app = (string) file_get_contents($dir . '/config/app.php');
    file_put_contents($dir . '/config/app.base.php', $app);
    file_put_contents($dir . '/config/app.php', <<<'PHP'
<?php
$config = require __DIR__ . '/app.base.php';
$config['build_release'] = ['release-sentinel.txt' => run_path('phar-out')];
return $config;
PHP);

    mkdir($dir . '/app/command', 0777, true);
    file_put_contents($dir . '/app/command/PharInit.php', <<<'PHP'
<?php

namespace app\command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('phar:init')]
class PharInit extends Command
{
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    new \Wegar\Basic\Process\InitProcess();
    $output->writeln('PHAR_INIT_OK');
    return self::SUCCESS;
  }
}
PHP);
    file_put_contents($dir . '/config/plugin/wegar/basic/command.php', <<<'PHP'
<?php

use Wegar\Basic\Command\Phinx;
use Wegar\Basic\Command\Updater;

return [
  Phinx::class,
  Updater::class,
  \app\command\PharInit::class,
];
PHP);

    @mkdir($dir . '/runtime', 0777, true);
    $db = $dir . '/runtime/phar.sqlite';
    file_put_contents($dir . '/phinx.php', <<<PHP
<?php
return [
  'paths' => ['migrations' => __DIR__ . '/database/migrations', 'seeds' => __DIR__ . '/database/seeds'],
  'environments' => ['default_migration_table' => 'phinxlog', 'default_environment' => 'default', 'default' => ['adapter' => 'sqlite', 'name' => '$db', 'suffix' => '']],
];
PHP);

    // 1) webman 自带命令打包（默认 exclude_pattern 在 /tmp 副本里可正常工作）
    [$buildExit, $buildOut] = $this->runHost(['php', '-d', 'phar.readonly=0', 'webman', 'build:phar'], null, 300);
    $this->assertSame(0, $buildExit, $buildOut);
    $phar = $dir . '/build/webman.phar';
    $this->assertFileExists($phar, $buildOut);

    // 2) 运行 phar 内命令触发 InitProcess → ReleaseFiles
    [$runExit, $runOut] = $this->runHost(['php', $phar, 'phar:init'], null, 120);
    $this->assertSame(0, $runExit, $runOut);
    $this->assertStringContainsString('PHAR_INIT_OK', $runOut);

    // 3) 断言释放内容
    $released = $dir . '/build/phar-out/release-sentinel.txt';
    $this->assertFileExists($released, $runOut);
    $this->assertSame($sentinel, trim((string) file_get_contents($released)));
  }
}
