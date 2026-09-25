<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Tools\CoverageThreshold;

class CoverageThresholdTest extends TestCase
{
  private const MIN = __DIR__ . '/../Fixtures/coverage/clover-min.xml';
  private const ZERO = __DIR__ . '/../Fixtures/coverage/clover-zero.xml';
  private const INVALID = __DIR__ . '/../Fixtures/coverage/clover-invalid.xml';

  /**
   * @return array{0:int,1:string,2:string}
   */
  private function invoke(string $file, float $min): array
  {
    $out = fopen('php://memory', 'r+');
    $err = fopen('php://memory', 'r+');
    $code = CoverageThreshold::main($file, $min, $out, $err);
    rewind($out);
    rewind($err);
    return [$code, (string) stream_get_contents($out), (string) stream_get_contents($err)];
  }

  public function testPercentageFromClover(): void
  {
    $this->assertSame(50.0, CoverageThreshold::percentage(self::MIN));
  }

  public function testZeroStatementsIsZeroPercent(): void
  {
    $this->assertSame(0.0, CoverageThreshold::percentage(self::ZERO));
  }

  public function testInvalidCloverThrows(): void
  {
    $this->expectException(RuntimeException::class);
    CoverageThreshold::percentage(self::INVALID);
  }

  public function testMainPassesWhenAboveThreshold(): void
  {
    [$code, $out] = $this->invoke(self::MIN, 40.0);
    $this->assertSame(0, $code);
    $this->assertStringContainsString('50.00%', $out);
  }

  public function testMainFailsWhenBelowThreshold(): void
  {
    $this->assertSame(1, $this->invoke(self::MIN, 60.0)[0]);
  }

  public function testMainReturnsTwoWhenFileMissing(): void
  {
    [$code, $out, $err] = $this->invoke(__DIR__ . '/nope.xml', 0.0);
    $this->assertSame(2, $code);
    $this->assertSame('', $out);
    $this->assertStringContainsString('clover file not found', $err);
  }
}
