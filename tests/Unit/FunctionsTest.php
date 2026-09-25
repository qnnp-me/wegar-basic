<?php

namespace Tests\Unit;

use config\plugin\wegar\basic\helper\SessionHelper;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
  private string $dir;

  protected function setUp(): void
  {
    $this->dir = sys_get_temp_dir() . '/wegar-fn-' . uniqid();
    mkdir($this->dir . '/nested', 0777, true);
    file_put_contents($this->dir . '/a.txt', 'a');
    file_put_contents($this->dir . '/nested/b.txt', 'b');
  }

  protected function tearDown(): void
  {
    @unlink($this->dir . '/a.txt');
    @unlink($this->dir . '/nested/b.txt');
    @rmdir($this->dir . '/nested');
    @rmdir($this->dir);
  }

  public function testGetAllFilesRecurses(): void
  {
    $files = \Wegar\Basic\getAllFiles($this->dir);
    sort($files);
    $expected = [$this->dir . '/a.txt', $this->dir . '/nested/b.txt'];
    sort($expected);
    $this->assertSame($expected, $files);
  }

  public function testGetAllFilesReturnsPathForMissingOrFile(): void
  {
    $missing = $this->dir . '/nope';
    $this->assertSame([$missing], \Wegar\Basic\getAllFiles($missing));
    $file = $this->dir . '/a.txt';
    $this->assertSame([$file], \Wegar\Basic\getAllFiles($file));
  }

  public function testGlobalWrappersDelegateToNamespaced(): void
  {
    $this->assertTrue(function_exists('getAllFiles'));
    $this->assertTrue(function_exists('json_error'));
    $this->assertTrue(function_exists('json_success'));
    $this->assertTrue(function_exists('ss'));
    $this->assertTrue(function_exists('env'));

    $a = \Wegar\Basic\getAllFiles($this->dir);
    $b = \getAllFiles($this->dir);
    sort($a);
    sort($b);
    $this->assertSame($a, $b);

    $this->assertSame(\Wegar\Basic\env('NO_SUCH_KEY', 'd'), \env('NO_SUCH_KEY', 'd'));
  }

  public function testSsReturnsManagedSessionHelperSingleton(): void
  {
    $this->assertInstanceOf(SessionHelper::class, \Wegar\Basic\ss());
    $this->assertSame(\Wegar\Basic\ss(), \Wegar\Basic\ss());
  }
}
