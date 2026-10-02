<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Init\Recorder;
use Tests\Fixtures\InitEdge\ThrowingRunner;
use Wegar\Basic\Helper\CommandHelper;
use Wegar\Basic\Helper\InitHelper;

class InitHelperTest extends TestCase
{
  protected function setUp(): void
  {
    Recorder::reset();
    ThrowingRunner::$thrown = 0;
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
    $this->assertSame(1, ThrowingRunner::$thrown);
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

  /**
   * Reproducer for defect B: 当 init 目录中存在非 PHP 文件（如 .gitkeep）且
   * scandir 顺序让其排在前时，prepare_init_functions() 历史上只从第一个条目
   * 推导 namespace，导致 self::$namespace 留空 → 后续类名解析失败 → 整批
   * init 类被静默跳过。修复后必须仍能正确发现并执行同名命名空间下的 init 类。
   */
  public function testNonPhpEntryDoesNotBlockNamespaceDiscovery(): void
  {
    $dir = sys_get_temp_dir() . '/wegar-init-mixed-' . bin2hex(random_bytes(4));
    mkdir($dir, 0777, true);
    // .gitkeep 排在前，模拟下游仓库的真实顺序
    file_put_contents($dir . '/.gitkeep', '');
    file_put_contents($dir . '/MixedRunner.php', <<<'PHP'
<?php
namespace Tests\Fixtures\InitMixed;

use Tests\Fixtures\Init\Recorder;
use Wegar\Basic\Abstract\InitAbstract;

class MixedRunner extends InitAbstract
{
  public int $weight = 5;
  public function run(): void
  {
    Recorder::add('Mixed');
  }
}
PHP);
    require_once $dir . '/MixedRunner.php';

    try {
      InitHelper::load($dir);
      $this->assertSame('\\Tests\\Fixtures\\InitMixed', InitHelper::$namespace);
      $this->assertContains('Mixed', Recorder::$order, 'init class must run even when non-PHP file sorts first');
    } finally {
      @unlink($dir . '/.gitkeep');
      @unlink($dir . '/MixedRunner.php');
      @rmdir($dir);
    }
  }

  /**
   * 显式传 namespace 时不受目录首条文件类型影响：调用方已声明命名空间，
   * 非 PHP 文件的存在不应干扰 init 类的发现与执行。
   *
   * 这里验证的是「显式 namespace 契约」本身，**不是** bug B 的回归守卫 ——
   * bug B（首条目为非 PHP 文件导致 namespace 推导失败）由
   * testNonPhpEntryDoesNotBlockNamespaceDiscovery 守卫。
   */
  public function testExplicitNamespaceContractUnaffectedByNonPhpEntries(): void
  {
    $dir = sys_get_temp_dir() . '/wegar-init-explicit-' . bin2hex(random_bytes(4));
    mkdir($dir, 0777, true);
    file_put_contents($dir . '/.gitkeep', '');
    file_put_contents($dir . '/ExplicitRunner.php', <<<'PHP'
<?php
namespace Tests\Fixtures\InitMixedExplicit;

use Tests\Fixtures\Init\Recorder;
use Wegar\Basic\Abstract\InitAbstract;

class ExplicitRunner extends InitAbstract
{
  public int $weight = 5;
  public function run(): void
  {
    Recorder::add('Explicit');
  }
}
PHP);
    require_once $dir . '/ExplicitRunner.php';

    try {
      InitHelper::load($dir, '\\Tests\\Fixtures\\InitMixedExplicit');
      $this->assertContains('Explicit', Recorder::$order);
    } finally {
      @unlink($dir . '/.gitkeep');
      @unlink($dir . '/ExplicitRunner.php');
      @rmdir($dir);
    }
  }
}