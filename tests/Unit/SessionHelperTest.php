<?php

namespace Tests\Unit;

use config\plugin\wegar\basic\helper\SessionHelper;
use Exception;
use PHPUnit\Framework\TestCase;

class SessionHelperTest extends TestCase
{
  public function testGetInstanceIsSingleton(): void
  {
    $a = SessionHelper::getInstance();
    $this->assertInstanceOf(SessionHelper::class, $a);
    $this->assertSame($a, SessionHelper::getInstance());
  }

  public function testUnmanagedSessionThrows(): void
  {
    $this->expectException(Exception::class);
    SessionHelper::getInstance()->unknownGet();
  }

  public function testUnsupportedMethodThrows(): void
  {
    $this->expectException(Exception::class);
    SessionHelper::getInstance()->some_session_nameFoo();
  }
}