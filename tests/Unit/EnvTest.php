<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
  protected function tearDown(): void
  {
    foreach ($this->envKeys as $name) {
      putenv($name);
    }
    $this->envKeys = [];
    parent::tearDown();
  }

  /**
   * @var string[]
   */
  private array $envKeys = [];

  private function setEnv(string $name, string|false $value): void
  {
    putenv($name . ($value === false ? '' : '=' . $value));
    $this->envKeys[] = $name;
  }

  public function testTrue(): void
  {
    $this->setEnv('FOO', 'true');
    $this->assertTrue(\Wegar\Basic\env('FOO'));
  }

  public function testFalse(): void
  {
    $this->setEnv('FOO', 'false');
    $this->assertFalse(\Wegar\Basic\env('FOO'));
  }

  public function testUnsetReturnsDefault(): void
  {
    $this->assertSame('fallback', \Wegar\Basic\env('UNSET_KEY_DOES_NOT_EXIST', 'fallback'));
  }

  public function testQuotedString(): void
  {
    $this->setEnv('FOO', '"quoted"');
    $this->assertSame('quoted', \Wegar\Basic\env('FOO'));
  }

  public function testNull(): void
  {
    $this->setEnv('FOO', 'null');
    $this->assertNull(\Wegar\Basic\env('FOO'));
  }

  public function testEmpty(): void
  {
    $this->setEnv('FOO', 'empty');
    $this->assertSame('', \Wegar\Basic\env('FOO'));
  }

  public function testArrayList(): void
  {
    $this->setEnv('FOO', '[1,2]');
    $this->assertSame([1, 2], \Wegar\Basic\env('FOO'));
  }

  public function testAssociativeArray(): void
  {
    $this->setEnv('FOO', '{"a":1}');
    $this->assertSame(['a' => 1], \Wegar\Basic\env('FOO'));
  }

  public function testFloat(): void
  {
    $this->setEnv('FOO', '3.14');
    $this->assertSame(3.14, \Wegar\Basic\env('FOO'));
  }

  public function testInt(): void
  {
    $this->setEnv('FOO', '42');
    $this->assertSame(42, \Wegar\Basic\env('FOO'));
  }
}