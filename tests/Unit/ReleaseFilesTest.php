<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wegar\Basic\Init\ReleaseFiles;

class ReleaseFilesTest extends TestCase
{
  public function testMergeReleaseListMarksHostEntriesOverwriteTrue(): void
  {
    $list = ReleaseFiles::mergeReleaseList(['sentinel.txt' => '/tmp/out'], []);
    $this->assertSame(['to' => '/tmp/out', 'overwrite' => true], $list['sentinel.txt']);
  }

  public function testMergeReleaseListMarksPluginDefaultsNoOverwrite(): void
  {
    $list = ReleaseFiles::mergeReleaseList([], ['database/migrations' => '/host/db/migrations']);
    $this->assertSame(['to' => '/host/db/migrations', 'overwrite' => false], $list['database/migrations']);
  }

  public function testHostEntryWinsOverPluginDefaultForSameSource(): void
  {
    $list = ReleaseFiles::mergeReleaseList(
      ['database/migrations' => '/custom/migrations'],
      ['database/migrations' => '/default/migrations'],
    );
    $this->assertSame(['to' => '/custom/migrations', 'overwrite' => true], $list['database/migrations']);
  }
}
