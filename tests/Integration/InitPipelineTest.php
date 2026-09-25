<?php

namespace Tests\Integration;

class InitPipelineTest extends HostTestCase
{
  private const ARTIFACTS = [
    '.env.example',
    'phinx.php',
    'app/init',
    'app/cron',
    'database/migrations',
    'database/seeds',
  ];

  private const RECREATED_ARTIFACTS = [
    '.env.example',
    'app/init',
    'app/cron',
    'database/migrations',
    'database/seeds',
  ];

  public function testInitProcessCreatesExpectedFiles(): void
  {
    $this->removeInitArtifacts();

    [$exit, $out] = $this->bootInit();
    $this->assertSame(0, $exit, $out);
    $this->assertStringContainsString('BOOT_OK', $out);

    foreach (self::RECREATED_ARTIFACTS as $rel) {
      $this->assertFileExists($this->hostDir() . '/' . $rel, $rel);
    }
  }

  public function testSecondInitIsIdempotent(): void
  {
    $phinx = $this->hostDir() . '/phinx.php';
    file_put_contents($phinx, <<<'PHP'
<?php
// IDEMPOTENCY-SENTINEL
return [
  'paths' => [
    'migrations' => __DIR__ . '/database/migrations',
    'seeds'      => __DIR__ . '/database/seeds',
  ],
  'environments' => [
    'default_migration_table' => 'phinxlog',
    'default_environment'     => 'default',
    'default'                 => [
      'adapter' => 'sqlite',
      'name'    => __DIR__ . '/runtime/init.sqlite',
      'suffix'  => '',
    ],
  ],
];
PHP);

    [$exit1, $out1] = $this->runRawInit();
    $this->assertSame(0, $exit1, $out1);
    $bytesAfterFirst = (string) file_get_contents($phinx);
    $this->assertStringContainsString('IDEMPOTENCY-SENTINEL', $bytesAfterFirst);

    [$exit2, $out2] = $this->runRawInit();
    $this->assertSame(0, $exit2, $out2);
    $bytesAfterSecond = (string) file_get_contents($phinx);
    $this->assertSame($bytesAfterFirst, $bytesAfterSecond);
    $this->assertStringContainsString('IDEMPOTENCY-SENTINEL', $bytesAfterSecond);
  }

  /**
   * Mirrors HostTestCase::bootInit() but does NOT call rewritePhinxToSqlite(),
   * so the real InitProcess sees the on-disk phinx.php as-is. Used to assert
   * that Init leaves an existing phinx.php untouched (idempotency).
   * @return array{0:int,1:string}
   */
  private function runRawInit(): array
  {
    $script = $this->hostDir() . '/wegar-boot-raw.php';
    file_put_contents($script, <<<'PHP'
<?php
chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/support/bootstrap.php';
new \Wegar\Basic\Process\InitProcess();
echo "BOOT_OK\n";
PHP);
    return $this->runHost(['php', 'wegar-boot-raw.php']);
  }

  private function removeInitArtifacts(): void
  {
    $base = $this->hostDir();
    foreach (self::ARTIFACTS as $rel) {
      $path = $base . '/' . $rel;
      if (!file_exists($path)) {
        continue;
      }
      if (is_dir($path)) {
        $this->rrmdir($path);
      } else {
        unlink($path);
      }
    }
    foreach (glob($base . '/database/phinxlog*') ?: [] as $extra) {
      if (is_dir($extra)) {
        $this->rrmdir($extra);
      } else {
        unlink($extra);
      }
    }
  }

  private function rrmdir(string $dir): void
  {
    if (!is_dir($dir)) {
      return;
    }
    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
      if ($item->isDir()) {
        rmdir($item->getPathname());
      } else {
        unlink($item->getPathname());
      }
    }
    rmdir($dir);
  }
}
