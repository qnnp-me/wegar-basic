<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Tester\CommandTester;
use Wegar\Basic\Command\Env;

class EnvCommandTest extends TestCase
{
  private string $dir;

  protected function setUp(): void
  {
    $this->dir = sys_get_temp_dir() . '/wegar-env-' . bin2hex(random_bytes(4));
    mkdir($this->dir, 0777, true);
    file_put_contents($this->dir . '/database.php', <<<'PHP'
    <?php
    return [
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'password' => 'your_password',
            ],
        ],
    ];
    PHP);
  }

  protected function tearDown(): void
  {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
      $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    @rmdir($this->dir);
  }

  public function testDryRunOnlyGeneratesEnvExample(): void
  {
    $envFile = $this->dir . '/.env.example';
    (new CommandTester(new Env()))->execute(['--path' => $this->dir, '--env-file' => $envFile]);

    $this->assertStringContainsString('DATABASE_CONNECTIONS_MYSQL_HOST', (string)file_get_contents($envFile));
    $this->assertStringContainsString("'host' => '127.0.0.1'", (string)file_get_contents($this->dir . '/database.php'));
    $this->assertFileDoesNotExist($this->dir . '/database.php.bak');
  }

  public function testWriteRewritesConfigAndKeepsBackup(): void
  {
    $envFile = $this->dir . '/.env.example';
    (new CommandTester(new Env()))->execute([
      '--path' => $this->dir,
      '--env-file' => $envFile,
      '--write' => true,
    ]);

    $rewritten = (string)file_get_contents($this->dir . '/database.php');
    $this->assertStringContainsString("'host' => env('DATABASE_CONNECTIONS_MYSQL_HOST', '127.0.0.1')", $rewritten);
    $this->assertStringContainsString("'password' => env('DATABASE_CONNECTIONS_MYSQL_PASSWORD', 'your_password')", $rewritten);
    $this->assertStringContainsString("'driver' => 'mysql'", $rewritten);
    $this->assertFileExists($this->dir . '/database.php.bak');
    $this->assertStringContainsString('DATABASE_CONNECTIONS_MYSQL_PASSWORD', (string)file_get_contents($envFile));
  }
}
