<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Init\Recorder;
use Wegar\Basic\Helper\InitHelper;

class InitHelperTest extends TestCase
{
  protected function setUp(): void
  {
    Recorder::reset();
    InitHelper::$results = [];
    InitHelper::$namespace = '';
    InitHelper::$relative_dir = '';
  }

  public function testWeightOrderingLowerFirst(): void
  {
    $dir = __DIR__ . '/../Fixtures/Init';
    $this->assertDirectoryExists($dir);

    ob_start();
    try {
        InitHelper::load($dir);
    } finally {
        ob_end_clean();
    }

    $this->assertSame(['Beta', 'Alpha'], Recorder::$order);
  }

  public function testNamespaceAutoDiscovery(): void
  {
    $dir = __DIR__ . '/../Fixtures/Init';

    ob_start();
    try {
        InitHelper::load($dir);
    } finally {
        ob_end_clean();
    }

    $this->assertSame('\\Tests\\Fixtures\\Init', InitHelper::$namespace);
  }
}