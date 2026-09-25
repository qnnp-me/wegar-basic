<?php

namespace Tests\Integration;

class InstallTest extends HostTestCase
{
  public function testInstallCopiesPluginConfigWithPreservedNamespace(): void
  {
    $dir = $this->hostDir() . '/config/plugin/wegar/basic';
    foreach (['app.php', 'command.php', 'process.php', 'helper/SessionHelper.php'] as $file) {
      $this->assertFileExists("$dir/$file");
    }
    $this->assertStringContainsString(
      'namespace config\plugin\wegar\basic\helper;',
      (string) file_get_contents("$dir/helper/SessionHelper.php")
    );
  }

  public function testReinstallIsIdempotent(): void
  {
    $session = $this->hostDir() . '/config/plugin/wegar/basic/helper/SessionHelper.php';
    $before = (string) file_get_contents($session);
    $script = $this->hostDir() . '/reinstall.php';
    file_put_contents($script, <<<'PHP'
<?php
chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/support/bootstrap.php';
\Wegar\Basic\Install::install();
echo "REINSTALL_OK\n";
PHP);
    [$exit, $out] = $this->runHost(['php', 'reinstall.php']);
    $this->assertSame(0, $exit, $out);
    $this->assertSame($before, (string) file_get_contents($session));
  }
}
