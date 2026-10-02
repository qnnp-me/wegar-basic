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

  /**
   * Defect A: 守护模式 `-d` 启动时 STDOUT 已成为已关闭资源；
   * PHP 8.5 下 `php-console-color` 的 `posix_isatty()` 抛 TypeError，
   * 历史实现会让 CommandHelper 构造函数把异常蔓延到 InitProcess，造成
   * worker exit 64000 被反复重启。修复后构造必须容错，且 color()/bgColor()
   * 在 consoleColor 为 null 时降级为纯文本而不抛错。
   */
  public function testColorReturnsPlainTextWhenConsoleColorIsNull(): void
  {
    (new ReflectionProperty(CommandHelper::class, 'consoleColor'))->setValue($this->helper, null);

    $this->assertSame('plain', $this->helper->color('plain', 231));
    $this->assertSame('plain-bg', $this->helper->bgColor('plain-bg', 240));
    $this->assertSame('plain-fb', $this->helper->color('plain-fb', 231, 240));
  }

  /**
   * Defect A: 守护模式 `-d` 启动时 STDOUT 已成为已关闭资源；
   * PHP 8.5 下 `php-console-color` 的 `posix_isatty()` 抛 TypeError，
   * 历史实现会让 CommandHelper 构造函数把异常蔓延到 InitProcess，造成
   * worker exit 64000 被反复重启。修复后构造必须容错，且 color()/bgColor()
   * 在 consoleColor 为 null 时降级为纯文本而不抛错。
   *
   * 这条用真子进程模拟守护模式（关闭 STDOUT 后构造 CommandHelper），若修复
   * 失效，子进程会以 TypeError 非 0 退出。
   */
  public function testConstructorSurvivesClosedStdoutInSubprocess(): void
  {
    $autoload = escapeshellarg(dirname(__DIR__, 2) . '/vendor/autoload.php');
    $script = sys_get_temp_dir() . '/wegar-defectA-' . bin2hex(random_bytes(4)) . '.php';
    file_put_contents($script, <<<PHP
<?php
require $autoload;
fclose(STDOUT);
try {
  new \\Wegar\\Basic\\Helper\\CommandHelper();
  fwrite(STDERR, "OK\\n");
  exit(0);
} catch (\\Throwable \$t) {
  fwrite(STDERR, \$t->getMessage() . "\\n");
  exit(1);
}
PHP);
    try {
      $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
      $descriptors = [
        0 => ['file', '/dev/null', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
      ];
      $proc = proc_open($cmd, $descriptors, $pipes);
      $this->assertIsResource($proc);
      $stdout = stream_get_contents($pipes[1]);
      $stderr = stream_get_contents($pipes[2]);
      fclose($pipes[1]);
      fclose($pipes[2]);
      $exit = proc_close($proc);

      $this->assertSame(0, $exit, "subprocess exited $exit; stderr=$stderr; stdout=$stdout");
      $this->assertStringContainsString('OK', $stderr);
    } finally {
      @unlink($script);
    }
  }
}
