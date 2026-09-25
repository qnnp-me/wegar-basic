<?php

namespace Tests\Tools;

use RuntimeException;

class HostApp
{
  public const VERSION_MARKER = '.wegar-host';

  public static function path(?string $base = null): string
  {
    return ($base ?? dirname(__DIR__, 2)) . '/.webman-host';
  }

  public static function composerLines(): string
  {
    return "webman:^2.2\nconsole:^2.2\ncrontab:^1.0\nredis-queue:^2.1\nwegar:@dev\n";
  }

  public static function isReady(?string $dir = null): bool
  {
    $dir ??= self::path();
    return is_file($dir . '/vendor/autoload.php')
      && is_file($dir . '/' . self::VERSION_MARKER)
      && trim((string) file_get_contents($dir . '/' . self::VERSION_MARKER)) === trim(self::composerLines());
  }

  /**
   * @param callable(array<int,string>,string):array{0:int,1:string} $run
   */
  public static function setup(callable $run, ?string $dir = null): void
  {
    $dir = $dir ?? self::path();
    $repo = dirname(__DIR__, 2);
    if (self::isReady($dir)) {
      return;
    }
    if (is_dir($dir)) {
      self::rrmdir($dir);
    }
    mkdir($dir, 0777, true);

    $steps = [
      ['composer', 'create-project', 'workerman/webman', $dir, '--no-interaction', '--no-scripts'],
      ['composer', 'require', 'webman/console', '--no-interaction'],
      ['composer', 'require', 'workerman/crontab', '--no-interaction'],
      ['composer', 'require', 'webman/redis-queue', '--no-interaction'],
      ['composer', 'config', 'repositories.local', json_encode([
        'type' => 'path',
        'url' => $repo,
        'options' => ['symlink' => false],
      ], JSON_UNESCAPED_SLASHES)],
      ['composer', 'require', 'wegar/basic:@dev', '--no-interaction'],
    ];
    foreach ($steps as $step) {
      $cwd = in_array('create-project', $step, true) ? dirname($dir) : $dir;
      [$exit, $out] = $run($step, $cwd);
      if ($exit !== 0) {
        throw new RuntimeException("host setup step failed: " . implode(' ', $step) . "\n$out");
      }
    }
    // create-project installs into $dir but the first step's cwd differs; ensure marker after success
    file_put_contents($dir . '/' . self::VERSION_MARKER, self::composerLines());
    if (!is_file($dir . '/vendor/autoload.php')) {
      throw new RuntimeException('host vendor/autoload.php missing after setup');
    }
  }

  private static function rrmdir(string $dir): void
  {
    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
      $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
  }
}
