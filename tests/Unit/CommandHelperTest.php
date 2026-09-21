<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Console\Output\BufferedOutput;
use Wegar\Basic\Helper\CommandHelper;

class CommandHelperTest extends TestCase
{
  private CommandHelper $helper;
  private BufferedOutput $output;

  protected function setUp(): void
  {
    CommandHelper::$quiet = false;
    $this->helper = new CommandHelper();
    $this->output = new BufferedOutput();
    (new ReflectionProperty(CommandHelper::class, 'output'))->setValue($this->helper, $this->output);
  }

  protected function tearDown(): void
  {
    CommandHelper::$quiet = false;
  }

  public function testWriteEmitsMessage(): void
  {
    $this->helper->write('hello');
    $this->assertStringContainsString('hello', $this->output->fetch());
  }

  public function testQuietSuppressesWrite(): void
  {
    CommandHelper::$quiet = true;
    $this->helper->write('hello');
    $this->assertSame('', $this->output->fetch());
  }

  public function testInfoEmitsMessage(): void
  {
    $this->helper->info('info-msg');
    $this->assertStringContainsString('info-msg', $this->output->fetch());
  }

  public function testQuietSuppressesInfo(): void
  {
    CommandHelper::$quiet = true;
    $this->helper->info('info-msg');
    $this->assertSame('', $this->output->fetch());
  }

  public function testSuccessEmitsMessage(): void
  {
    $this->helper->success('done');
    $this->assertStringContainsString('done', $this->output->fetch());
  }
}