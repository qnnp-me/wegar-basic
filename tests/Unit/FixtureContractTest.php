<?php

namespace Tests\Unit;

use Attribute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Fixtures\Init\Alpha;
use Tests\Fixtures\Init\Beta;
use Wegar\Basic\Abstract\InitAbstract;
use Wegar\Basic\Attribute\CronRule;

class FixtureContractTest extends TestCase
{
  public function testInitFixturesExtendRealBase(): void
  {
    $this->assertTrue(is_subclass_of(Alpha::class, InitAbstract::class));
    $this->assertTrue(is_subclass_of(Beta::class, InitAbstract::class));
  }

  public function testInitFixturesDeclareRealWeightProperty(): void
  {
    $this->assertSame(20, (new ReflectionClass(Alpha::class))->getDefaultProperties()['weight']);
    $this->assertSame(5, (new ReflectionClass(Beta::class))->getDefaultProperties()['weight']);
  }

  public function testCronRuleIsMethodTargetRepeatableAttribute(): void
  {
    $attrs = (new ReflectionClass(CronRule::class))->getAttributes(Attribute::class);
    $this->assertCount(1, $attrs);
    /** @var Attribute $attr */
    $attr = $attrs[0]->newInstance();
    $this->assertSame(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE, $attr->flags);
    $this->assertInstanceOf(CronRule::class, new CronRule('* * * * * *'));
  }
}
