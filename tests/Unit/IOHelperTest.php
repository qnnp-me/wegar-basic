<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wegar\Basic\Helper\IOHelper;

class IOHelperTest extends TestCase
{
  public function testReleaseIsNoopOutsidePhar(): void
  {
    $to = sys_get_temp_dir() . '/wegar-release-' . bin2hex(random_bytes(4));
    mkdir($to, 0777, true);
    file_put_contents($to . '/keep.txt', 'keep');
    try {
      IOHelper::release('.env.example', $to);          // 非 phar：no-op
      $this->assertFileExists($to . '/keep.txt');      // 未清理目标目录
      $this->assertFileDoesNotExist($to . '/.env.example'); // 未释放任何文件
    } finally {
      @unlink($to . '/keep.txt');
      @rmdir($to);
    }
  }
}
