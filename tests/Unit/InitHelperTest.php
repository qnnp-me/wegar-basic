<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Init\Recorder;
use Wegar\Basic\Helper\CommandHelper;
use Wegar\Basic\Helper\InitHelper;

class InitHelperTest extends TestCase
{
  protected function setUp(): void
  {
    Recorder::reset();
    InitHelper::$results = [];
    InitHelper::$namespace = '';
    InitHelper::$relative_dir = '';
    CommandHelper::$quiet = true;
  }

  protected function tearDown(): void
  {
    CommandHelper::$quiet = false;
  }

  public function testWeightOrderingLowerFirst(): void
  {
    $dir = __DIR__ . '/../Fixtures/Init';
    $this->assertDirectoryExists($dir);

    InitHelper::load($dir);

    $this->assertSame(['Beta', 'Alpha'], Recorder::$order);
  }

  public function testNamespaceAutoDiscovery(): void
  {
    $dir = __DIR__ . '/../Fixtures/Init';

    InitHelper::load($dir);

    $this->assertSame('\\Tests\\Fixtures\\Init', InitHelper::$namespace);
  }
}