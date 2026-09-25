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

  private const EDGE = __DIR__ . '/../Fixtures/InitEdge';
  private const EDGE_NS = '\\Tests\\Fixtures\\InitEdge';

  public function testMissingDirectoryReturnsWithoutRunning(): void
  {
    InitHelper::load(self::EDGE . '/missing', self::EDGE_NS);
    // 缺目录时在 file_exists 早退前先记录 namespace；若 load() 是 no-op 此处会保持 setUp 的 ''。
    $this->assertSame(self::EDGE_NS, InitHelper::$namespace);
    $this->assertSame([], Recorder::$order);
  }

  public function testStaticRunIsInvokedInWeightOrder(): void
  {
    InitHelper::load(self::EDGE, self::EDGE_NS);
    $this->assertSame(['Static', 'Default'], Recorder::$order);
  }

  public function testThrowingRunIsCaught(): void
  {
    // ThrowingRunner weight=2 最先执行并抛异常；若未被 catch，load() 会中断、Recorder 为空。
    InitHelper::load(self::EDGE, self::EDGE_NS);
    $this->assertSame(['Static', 'Default'], Recorder::$order, 'throwing runner must be caught and not halt later runners');
  }

  public function testDefaultWeightApplied(): void
  {
    InitHelper::load(self::EDGE, self::EDGE_NS);
    $this->assertContains('Default', Recorder::$order);
    $this->assertSame('Default', end(Recorder::$order));
  }

  public function testNoRunClassIsSkipped(): void
  {
    InitHelper::load(self::EDGE, self::EDGE_NS);
    $this->assertNotContains('NoRunCalled', Recorder::$order);
  }

  public function testResultsAreFlushedAfterRun(): void
  {
    InitHelper::$results = ['stale'];
    InitHelper::load(self::EDGE, self::EDGE_NS);
    $this->assertSame([], InitHelper::$results);
  }
}