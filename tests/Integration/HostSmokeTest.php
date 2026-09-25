<?php

namespace Tests\Integration;

class HostSmokeTest extends HostTestCase
{
  public function testHostIsBootstrappableAndPluginCommandsRegistered(): void
  {
    [$exit, $out] = $this->runHost(['php', 'webman', 'list']);
    $this->assertSame(0, $exit, $out);
    $this->assertStringContainsString('wegar:basic:update', $out);
    $this->assertStringContainsString('phinx', $out);
  }
}
