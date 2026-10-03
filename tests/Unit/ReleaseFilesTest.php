<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wegar\Basic\Init\ReleaseFiles;

class ReleaseFilesTest extends TestCase
{
  public function testHostEntryWinsForSameSource(): void
  {
    $list = ReleaseFiles::mergeReleaseList(
      ['database/' => '/host/phinx'],
      ['database/' => '/default/phinx'],
    );

    $this->assertSame('/host/phinx', $list['database/']);
  }

  public function testPluginDefaultsFillMissingSources(): void
  {
    $list = ReleaseFiles::mergeReleaseList(
      ['sentinel.txt' => '/out'],
      ['database/' => '/runtime/phinx'],
    );

    $this->assertSame(['sentinel.txt' => '/out', 'database/' => '/runtime/phinx'], $list);
  }

  public function testEmptyInputsYieldEmptyList(): void
  {
    $this->assertSame([], ReleaseFiles::mergeReleaseList([], []));
  }
}
