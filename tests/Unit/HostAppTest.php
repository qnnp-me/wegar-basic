<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Tools\HostApp;

class HostAppTest extends TestCase
{
  public function testPathIsUnderWebmanHost(): void
  {
    $this->assertStringEndsWith('/.webman-host', HostApp::path());
  }

  public function testIsReadyFalseWhenMarkerMissing(): void
  {
    $this->assertFalse(HostApp::isReady(sys_get_temp_dir() . '/wegar-nope-' . uniqid()));
  }

  public function testIsReadyTrueWhenMarkerAndVendorExist(): void
  {
    $dir = sys_get_temp_dir() . '/wegar-host-' . uniqid();
    mkdir($dir . '/vendor', 0777, true);
    file_put_contents($dir . '/vendor/autoload.php', '<?php');
    file_put_contents($dir . '/' . HostApp::VERSION_MARKER, HostApp::composerLines());
    $this->assertTrue(HostApp::isReady($dir));
    unlink($dir . '/vendor/autoload.php');
    unlink($dir . '/' . HostApp::VERSION_MARKER);
    rmdir($dir . '/vendor');
    rmdir($dir);
  }

  public function testIsReadyFalseWhenMarkerMismatches(): void
  {
    $dir = sys_get_temp_dir() . '/wegar-host-' . uniqid();
    mkdir($dir . '/vendor', 0777, true);
    file_put_contents($dir . '/vendor/autoload.php', '<?php');
    file_put_contents($dir . '/' . HostApp::VERSION_MARKER, "stale-definition\n");
    $this->assertFalse(HostApp::isReady($dir));
    unlink($dir . '/vendor/autoload.php');
    unlink($dir . '/' . HostApp::VERSION_MARKER);
    rmdir($dir . '/vendor');
    rmdir($dir);
  }
}
