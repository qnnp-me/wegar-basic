<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Tools\HostApp;

abstract class HostTestCase extends TestCase
{
  private string $dir;

  protected function setUp(): void
  {
    parent::setUp();
    if (!HostApp::isReady()) {
      $this->markTestSkipped('Host not ready — run `composer host:setup` first.');
    }
    $short = (new \ReflectionClass($this))->getShortName();
    $this->dir = sys_get_temp_dir() . '/wegar-it-' . $short . '-' . getmypid() . '-' . uniqid();
    $this->makeHostCopy();
    $this->refreshPluginFromRepo();
  }

  protected function tearDown(): void
  {
    if (isset($this->dir) && is_dir($this->dir)) {
      $this->rrmdir($this->dir);
    }
    parent::tearDown();
  }

  protected function hostDir(): string
  {
    return $this->dir;
  }

  /**
   * @return array{0:int,1:string}
   */
  protected function runHost(array $cmd, ?string $stdin = null, int $timeoutSeconds = 120): array
  {
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]];
    $proc = proc_open($cmd, $descriptors, $pipes, $this->dir);
    if (!is_resource($proc)) {
      return [1, 'proc_open failed'];
    }
    if ($stdin !== null) {
      fwrite($pipes[0], $stdin);
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    $out = '';
    $deadline = microtime(true) + $timeoutSeconds;
    $exit = -1;
    while (true) {
      $out .= (string) stream_get_contents($pipes[1]);
      $status = proc_get_status($proc);
      if (!$status['running']) {
        $out .= (string) stream_get_contents($pipes[1]);
        $exit = $status['exitcode'];
        break;
      }
      if (microtime(true) > $deadline) {
        proc_terminate($proc, 9);
        fclose($pipes[1]);
        proc_close($proc);
        return [124, $out . "\n[runHost timeout after {$timeoutSeconds}s]"];
      }
      usleep(20000);
    }
    fclose($pipes[1]);
    proc_close($proc);
    return [$exit, $out];
  }

  /**
   * Instantiates the real InitProcess inside the host (no server start).
   * Per R8: rewrites phinx.php to a sqlite environment first, so the plugin's
   * auto-Phinx Init does not attempt the Install-generated MySQL config.
   * @return array{0:int,1:string}
   */
  protected function bootInit(): array
  {
    $this->rewritePhinxToSqlite();
    $script = $this->dir . '/wegar-boot.php';
    file_put_contents($script, <<<'PHP'
<?php
chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/support/bootstrap.php';
new \Wegar\Basic\Process\InitProcess();
echo "BOOT_OK\n";
PHP);
    return $this->runHost(['php', 'wegar-boot.php']);
  }

  /**
   * 真实拷贝全部顶层条目（含 vendor），与真实部署一致。
   * 共享 host 已经被强制配置为不带软链（`composer config repositories.local` JSON `symlink:false`），
   * 因此这里只需一次 `cp -a`；副本中已不存在任何符号链接。
   */
  private function makeHostCopy(): void
  {
    $src = HostApp::path();
    $dst = $this->dir;
    mkdir($dst, 0777, true);

    $cmd = sprintf(
      'cp -a %s/. %s/ 2>&1',
      escapeshellarg($src),
      escapeshellarg($dst)
    );
    $output = [];
    $exit = 0;
    exec($cmd, $output, $exit);

    if ($exit !== 0) {
      $entries = array_values(array_diff(scandir($src), ['.', '..']));
      foreach ($entries as $entry) {
        $from = $src . '/' . $entry;
        $to = $dst . '/' . $entry;
        if (is_dir($from)) {
          $this->copyDir($from, $to);
        } else {
          copy($from, $to);
        }
      }
    }

    $this->assertNoUnexpectedSymlinks($dst);
  }

  /**
   * 自检：cp -a 可能在副本里残留软链（如果共享 host 退化或 `composer host:setup` 配置漂移）。
   * - `vendor/wegar/basic` 是被允许的软链入口 → 直接 unlink，由 refreshPluginFromRepo 用真实文件重建。
   * - 其它任何软链 → 抛出 RuntimeException，强制 `composer host:setup` 重做宿主持久修复。
   * 扫描默认不开启 FOLLOW_SYMLINKS，遇到 symlink-to-dir 视为叶子、不下钻；只对叶子级别调用 isLink() 判断。
   */
  private function assertNoUnexpectedSymlinks(string $dst): void
  {
    $pluginPath = $dst . '/vendor/wegar/basic';
    if (is_link($pluginPath)) {
      unlink($pluginPath);
    }
    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dst, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
      if ($item->isLink()) {
        throw new \RuntimeException(sprintf(
          'Unexpected symlink in host copy at %s. Re-run `composer host:setup` to rebuild a symlink-free host.',
          $item->getPathname()
        ));
      }
    }
  }

  /**
   * 用仓库当前工作区内容刷新副本里的 wegar/basic 插件，让每次集成测试
   * 始终跑当前源代码而非共享 host 里那份已固定的快照。
   * 强制使用真实文件（不依赖任何符号链接），以兼容 `php webman build:phar`。
   */
  private function refreshPluginFromRepo(): void
  {
    $repo = dirname(__DIR__, 2);
    $pluginDir = $this->dir . '/vendor/wegar/basic';

    // CAUTION: never rrmdir() a symlink. RecursiveDirectoryIterator follows the link
    // and would iterate/delete the link target (potentially wiping the whole repo).
    if (is_link($pluginDir)) {
      unlink($pluginDir);
    } elseif (is_dir($pluginDir)) {
      $this->rrmdir($pluginDir);
    }

    if (!is_file($repo . '/composer.json')) {
      throw new \RuntimeException("refreshPluginFromRepo: missing {$repo}/composer.json");
    }
    if (!is_dir($repo . '/src')) {
      throw new \RuntimeException("refreshPluginFromRepo: missing {$repo}/src");
    }

    mkdir($pluginDir, 0777, true);
    copy($repo . '/composer.json', $pluginDir . '/composer.json');
    $this->copyDir($repo . '/src', $pluginDir . '/src');
  }

  private function rewritePhinxToSqlite(): void
  {
    $runtime = $this->dir . '/runtime';
    if (!is_dir($runtime)) {
      mkdir($runtime, 0777, true);
    }
    $db = $runtime . '/init.sqlite';
    $phinx = $this->dir . '/phinx.php';
    file_put_contents($phinx, <<<PHP
<?php
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
      'name'    => '$db',
      'suffix'  => '',
    ],
  ],
  'feature_flags' => [
    'add_timestamps_use_datetime' => true,
    'column_null_default'         => true,
    'unsigned_primary_keys'       => true,
  ],
];
PHP);
  }

  private function copyDir(string $src, string $dst): void
  {
    mkdir($dst, 0777, true);
    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
      $target = $dst . '/' . $it->getSubPathname();
      $item->isDir() ? mkdir($target, 0777, true) : copy($item->getPathname(), $target);
    }
  }

  private function rrmdir(string $dir): void
  {
    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
      if ($item->isLink()) {
        unlink($item->getPathname());
      } elseif ($item->isDir()) {
        rmdir($item->getPathname());
      } else {
        unlink($item->getPathname());
      }
    }
    rmdir($dir);
  }
}
