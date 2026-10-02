<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wegar\Basic\Helper\IOHelper;

class ScanFilesTest extends TestCase
{
  private string $tmpDir;

  protected function setUp(): void
  {
    $this->tmpDir = sys_get_temp_dir() . '/wegar-scan-' . bin2hex(random_bytes(4));
    mkdir($this->tmpDir, 0777, true);

    mkdir($this->tmpDir . '/sub');
    mkdir($this->tmpDir . '/sub/deep');

    file_put_contents($this->tmpDir . '/a.php', '<?php');
    file_put_contents($this->tmpDir . '/b.js', '// js');
    file_put_contents($this->tmpDir . '/c.txt', 'plain');
    file_put_contents($this->tmpDir . '/sub/d.php', '<?php');
    file_put_contents($this->tmpDir . '/sub/e.js', '// js');
    file_put_contents($this->tmpDir . '/sub/deep/f.php', '<?php');
    file_put_contents($this->tmpDir . '/sub/deep/g.log', 'log');
  }

  protected function tearDown(): void
  {
    $this->rrmdir($this->tmpDir);
  }

  private function rrmdir(string $dir): void
  {
    if (!is_dir($dir)) {
      return;
    }
    foreach (scandir($dir) as $entry) {
      if ($entry === '.' || $entry === '..') continue;
      $path = $dir . '/' . $entry;
      if (is_dir($path)) {
        $this->rrmdir($path);
      } else {
        unlink($path);
      }
    }
    rmdir($dir);
  }

  public function testRecursiveScanFindsAllFiles(): void
  {
    $files = iterator_to_array(IOHelper::scan_files($this->tmpDir), false);
    $this->assertCount(7, $files);
    $this->assertContains($this->tmpDir . '/a.php', $files);
    $this->assertContains($this->tmpDir . '/sub/deep/f.php', $files);
    $this->assertContains($this->tmpDir . '/sub/deep/g.log', $files);
  }

  public function testScanSingleFileYieldsItself(): void
  {
    $files = iterator_to_array(IOHelper::scan_files($this->tmpDir . '/a.php'), false);
    $this->assertSame([$this->tmpDir . '/a.php'], $files);
  }

  public function testIncludeGlobStar(): void
  {
    $files = iterator_to_array(IOHelper::scan_files($this->tmpDir, include: '*.php'), false);
    sort($files);
    $this->assertSame([
      $this->tmpDir . '/a.php',
      $this->tmpDir . '/sub/d.php',
      $this->tmpDir . '/sub/deep/f.php',
    ], $files);
  }

  public function testIncludeExtensionDot(): void
  {
    $files = iterator_to_array(IOHelper::scan_files($this->tmpDir, include: '.php'), false);
    sort($files);
    $this->assertSame([
      $this->tmpDir . '/a.php',
      $this->tmpDir . '/sub/d.php',
      $this->tmpDir . '/sub/deep/f.php',
    ], $files);
  }

  public function testIncludeRegex(): void
  {
    $files = iterator_to_array(IOHelper::scan_files($this->tmpDir, include: '/\.js$/'), false);
    sort($files);
    $this->assertSame([
      $this->tmpDir . '/b.js',
      $this->tmpDir . '/sub/e.js',
    ], $files);
  }

  public function testExcludeTakesEffect(): void
  {
    $files = iterator_to_array(IOHelper::scan_files($this->tmpDir, include: ['.php', '.js'], exclude: '.js'), false);
    sort($files);
    $this->assertSame([
      $this->tmpDir . '/a.php',
      $this->tmpDir . '/sub/d.php',
      $this->tmpDir . '/sub/deep/f.php',
    ], $files);
  }

  /**
   * include 是白名单、exclude 是黑名单：include='.php', exclude='.log' 时
   * 只应留下 .php 文件；g.log 即便命中 exclude 也绝不能被放进来。
   */
  public function testIncludeExcludeCombination(): void
  {
    $files = iterator_to_array(IOHelper::scan_files($this->tmpDir, include: '.php', exclude: '.log'), false);
    sort($files);
    $this->assertSame([
      $this->tmpDir . '/a.php',
      $this->tmpDir . '/sub/d.php',
      $this->tmpDir . '/sub/deep/f.php',
    ], $files);
  }

  public function testIncludeExcludeCombinationBoundsBothWays(): void
  {
    // 命中 include 且命中 exclude → 排除
    $f = iterator_to_array(IOHelper::scan_files($this->tmpDir, include: '.php', exclude: 'a.php'), false);
    $this->assertNotContains($this->tmpDir . '/a.php', $f);
    $this->assertContains($this->tmpDir . '/sub/d.php', $f);

    // 仅 exclude（不传 include）→ 排除命中项，其余保留（7-2=5）
    $g = iterator_to_array(IOHelper::scan_files($this->tmpDir, exclude: '.js'), false);
    $this->assertCount(5, $g);
    $this->assertNotContains($this->tmpDir . '/b.js', $g);
    $this->assertNotContains($this->tmpDir . '/sub/e.js', $g);

    // include='.txt'、exclude='.log'：只命中 exclude 的 g.log 必须被排除
    $h = iterator_to_array(IOHelper::scan_files($this->tmpDir, include: '.txt', exclude: '.log'), false);
    $this->assertSame([$this->tmpDir . '/c.txt'], $h);
  }
}