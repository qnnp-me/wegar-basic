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

  public function testIncludeExcludeCombination(): void
  {
    // Per IOHelper.php:77-81 quirk:
    //  - include=T, exclude=T -> skip
    //  - include=T, exclude=F -> yield
    //  - include=F, exclude=T -> yield (odd case)
    //  - include=F, exclude=F -> skip
    // So when include='.php' and exclude='.log':
    //   file.php: include=T (matches .php), exclude=F -> yield
    //   file.js:  include=F (no .php), exclude=F -> skip
    //   file.log: include=F, exclude=T -> yield (per quirky logic)
    //   file.txt: include=F, exclude=F -> skip
    $files = iterator_to_array(IOHelper::scan_files($this->tmpDir, include: '.php', exclude: '.log'), false);
    sort($files);
    $this->assertSame([
      $this->tmpDir . '/a.php',
      $this->tmpDir . '/sub/d.php',
      $this->tmpDir . '/sub/deep/f.php',
      $this->tmpDir . '/sub/deep/g.log',
    ], $files);
  }
}