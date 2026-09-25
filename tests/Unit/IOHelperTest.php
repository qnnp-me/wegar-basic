<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wegar\Basic\Helper\IOHelper;

class IOHelperTest extends TestCase
{
  public function testReleaseIsNoopOutsidePharAndDoesNotThrow(): void
  {
    $to = sys_get_temp_dir() . '/wegar-release-' . uniqid();
    // 非 phar 上下文：release() 应为 no-op，且不得因 `new Phar(Phar::running())` 抛出
    IOHelper::release('.env.example', $to);
    $this->assertDirectoryDoesNotExist($to);
  }
}
