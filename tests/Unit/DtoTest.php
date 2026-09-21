<?php

namespace Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wegar\Basic\Helper\DTO;

class DtoTest extends TestCase
{
  public function testMagicGetReturnsValue(): void
  {
    $dto = new DTO(['name' => 'alice', 'age' => 30]);
    $this->assertSame('alice', $dto->name);
    $this->assertSame(30, $dto->age);
  }

  public function testArrayAccessRead(): void
  {
    $dto = new DTO(['name' => 'alice']);
    $this->assertTrue(isset($dto['name']));
    $this->assertSame('alice', $dto['name']);
    $this->assertFalse(isset($dto['missing']));
    $this->assertNull($dto['missing']);
  }

  public function testNestedAssocReturnsDto(): void
  {
    $dto = new DTO([
      'user' => ['name' => 'alice', 'meta' => ['role' => 'admin']],
      'flat'  => [1, 2, 3],
    ]);
    $this->assertInstanceOf(DTO::class, $dto->user);
    $this->assertInstanceOf(DTO::class, $dto->user->meta);
    $this->assertSame('alice', $dto->user->name);
    $this->assertSame('admin', $dto->user->meta->role);
    // numeric list should stay a list, not become a DTO
    $this->assertSame([1, 2, 3], $dto->flat);
  }

  public function testValueReturnsRawArray(): void
  {
    $data = ['a' => 1, 'b' => 2];
    $dto = new DTO($data);
    $this->assertSame($data, $dto->value());
  }

  public function testJsonSerializeReturnsRawArray(): void
  {
    $data = ['a' => 1, 'nested' => ['x' => 1]];
    $dto = new DTO($data);
    $this->assertSame($data, $dto->jsonSerialize());
    $this->assertSame('{"a":1,"nested":{"x":1}}', json_encode($dto));
  }

  public function testOffsetSetThrows(): void
  {
    $dto = new DTO(['a' => 1]);
    $this->expectException(LogicException::class);
    $dto['a'] = 2;
  }

  public function testOffsetUnsetThrows(): void
  {
    $dto = new DTO(['a' => 1]);
    $this->expectException(LogicException::class);
    unset($dto['a']);
  }
}