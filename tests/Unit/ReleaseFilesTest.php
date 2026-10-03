<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wegar\Basic\Init\ReleaseFiles;

class ReleaseFilesTest extends TestCase
{
  public function testStringEntryDefaultsToOverwrite(): void
  {
    $list = ReleaseFiles::mergeReleaseList(['sentinel.txt' => '/out'], []);
    $this->assertSame(['to' => '/out', 'overwrite' => true], $list['sentinel.txt']);
  }

  public function testArrayEntryCanDisableOverwrite(): void
  {
    $list = ReleaseFiles::mergeReleaseList(
      ['.env.example' => ['to' => '/out', 'overwrite' => false]],
      [],
    );
    $this->assertSame(['to' => '/out', 'overwrite' => false], $list['.env.example']);
  }

  public function testArrayEntryWithoutOverwriteDefaultsToOverwrite(): void
  {
    $list = ReleaseFiles::mergeReleaseList(['a.txt' => ['to' => '/out']], []);
    $this->assertSame(['to' => '/out', 'overwrite' => true], $list['a.txt']);
  }

  public function testPluginDefaultsOverwriteByDefault(): void
  {
    $list = ReleaseFiles::mergeReleaseList([], ['database/' => '/runtime/phinx']);
    $this->assertSame(['to' => '/runtime/phinx', 'overwrite' => true], $list['database/']);
  }

  public function testHostEntryWinsForSameSource(): void
  {
    $list = ReleaseFiles::mergeReleaseList(
      ['database/' => ['to' => '/host/phinx', 'overwrite' => false]],
      ['database/' => '/default/phinx'],
    );
    $this->assertSame(['to' => '/host/phinx', 'overwrite' => false], $list['database/']);
  }
}
