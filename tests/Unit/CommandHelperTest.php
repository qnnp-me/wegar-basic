<?php

namespace Tests\Unit;

use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Console\Input\StringInput;
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

  private function withInput(string $raw): void
  {
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $raw);
    rewind($stream);
    $input = new StringInput($raw);
    $input->setStream($stream);
    (new ReflectionProperty(CommandHelper::class, 'input'))->setValue($this->helper, $input);
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

  public function testNoticeWarningErrorFailedEmit(): void
  {
    foreach (['notice', 'warning', 'error', 'failed'] as $m) {
      $this->helper->{$m}('m-' . $m);
      $this->assertStringContainsString('m-' . $m, $this->output->fetch());
    }
  }

  public function testColorAndBgColorWrapText(): void
  {
    $color = new ConsoleColor();
    $color->setForceStyle(true);
    (new ReflectionProperty(CommandHelper::class, 'consoleColor'))->setValue($this->helper, $color);

    $colored = $this->helper->color('txt', 231, 240);
    $this->assertStringContainsString('txt', $colored);
    $this->assertMatchesRegularExpression('/\x1b\[[0-9;]+m/', $colored);

    $bg = $this->helper->bgColor('txt', 240);
    $this->assertStringContainsString('txt', $bg);
    $this->assertMatchesRegularExpression('/\x1b\[[0-9;]+m/', $bg);
  }

  public function testWriteIterableLines(): void
  {
    $this->helper->write(['one', 'two']);
    $out = $this->output->fetch();
    $this->assertStringContainsString('one', $out);
    $this->assertStringContainsString('two', $out);
  }

  public function testConfirmReturnsTrueOnYes(): void
  {
    $this->withInput("y\n");
    $this->assertTrue($this->helper->confirm('ok?'));
  }

  public function testConfirmReturnsFalseOnNo(): void
  {
    $this->withInput("n\n");
    $this->assertFalse($this->helper->confirm('ok?', true));
  }

  public function testSelectReturnsChoice(): void
  {
    $this->withInput("b\n");
    $this->assertSame('b', $this->helper->select('pick', ['a', 'b']));
  }

  public function testInputReturnsValue(): void
  {
    $this->withInput("hello\n");
    $this->assertSame('hello', $this->helper->input('name'));
  }

  public function testInputUsesDefaultWhenEmpty(): void
  {
    $this->withInput("\n");
    $this->assertSame('def', $this->helper->input('name', 'def'));
  }

  public function testAlertDoesNotThrow(): void
  {
    $this->withInput("\n");
    $this->helper->alert('watch out');
    $this->assertStringContainsString('watch out', $this->output->fetch());
  }
}
