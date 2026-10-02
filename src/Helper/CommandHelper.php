<?php

namespace Wegar\Basic\Helper;

use Monolog\Logger;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CommandHelper
{
  protected InputInterface $input;
  protected OutputInterface $output;
  protected ?ConsoleColor $consoleColor = null;

  /**
   * 静默模式：为 true 时所有输出被抑制（如测试中调用初始化脚本时）
   */
  public static bool $quiet = false;

  /**
   * 错误持久化落点覆盖（测试/宿主可注入）；为 null 时走 webman 的
   * support\Log，再兜底 error_log。
   *
   * @var null|callable(string):void
   */
  public static $errorSink = null;

  function __construct()
  {
    /**
     * php-console-color 在 STDOUT 已关闭的守护进程（`-d`）下会让
     * `posix_isatty()` 抛 TypeError；PHP 8.5 的 `@` 压不住 Error，
     * 会直接蔓延到 InitProcess 导致 worker exit 64000 被反复重启。
     * 这里把它降级为可空，让 color()/bgColor() 在 null 时返回纯文本。
     */
    try {
      $this->consoleColor = new ConsoleColor();
    } catch (\Throwable) {
      $this->consoleColor = null;
    }
    /**
     * 同理：守护进程下 Symfony 的 ConsoleOutput 也可能因 STDOUT/STDERR
     * 不是有效流而抛 InvalidArgumentException。降级为 NullOutput 让
     * write() 不抛错；下游 log 落库仍由 Monolog 负责（不经过这里）。
     */
    try {
      $this->output = new ConsoleOutput();
    } catch (\Throwable) {
      $this->output = new \Symfony\Component\Console\Output\NullOutput();
    }
    $this->input = new StringInput('');
  }

  function color($str, int $front, ?int $back = null): string
  {
    if ($this->consoleColor === null) {
      return $str;
    }
    $str = $this->consoleColor->apply("color_$front", $str);
    if ($back) $str = $this->bgColor($str, $back);
    return $str;
  }

  function bgColor($str, int $color): string
  {
    if ($this->consoleColor === null) {
      return $str;
    }
    return $this->consoleColor->apply("bg_color_$color", $str);
  }

  protected function addPrefix(string|iterable $messages, $prefix = ''): array|string
  {
    if (is_iterable($messages)) {
      $list = [];
      $fix = strlen($prefix) > 2 ? 1 : 0;
      $indent = str_repeat(' ', mb_strlen($prefix) + $fix);
      foreach ($messages as $index => $message) {
        $list[] = ($index ? $indent : $prefix) . " $message";
      }
      return $list;
    }
    return "$prefix $messages";
  }


  function info(string|iterable $messages): void
  {
    if (Logger::INFO < config('log.default.handlers.0.constructor.2', Logger::DEBUG)) return;
    $this->writeln($this->addPrefix($messages, '🔖'), tag: "Info   ", back: 244);
  }

  function notice(string|iterable $messages): void
  {
    if (Logger::NOTICE < config('log.default.handlers.0.constructor.2', Logger::DEBUG)) return;
    $this->writeln($this->addPrefix($messages, '💬'), tag: "Notice ", back: 45);
  }

  function warning(string|iterable $messages): void
  {
    if (Logger::WARNING < config('log.default.handlers.0.constructor.2', Logger::DEBUG)) return;
    $this->writeln($this->addPrefix($messages, '🚨'), tag: "Warning", back: 220);
  }

  function error(string|iterable $messages): void
  {
    if (Logger::ERROR < config('log.default.handlers.0.constructor.2', Logger::DEBUG)) return;
    if ($messages instanceof \Traversable) $messages = iterator_to_array($messages, false);
    $this->persistError($messages, 'error');
    $this->writeln($this->addPrefix($messages, '🐞'), tag: "Error  ", back: 160);
  }

  function failed(string|iterable $messages): void
  {
    if (Logger::CRITICAL < config('log.default.handlers.0.constructor.2', Logger::DEBUG)) return;
    if ($messages instanceof \Traversable) $messages = iterator_to_array($messages, false);
    $this->persistError($messages, 'critical');
    $this->writeln($this->addPrefix($messages, '💔'), tag: "Failed ", back: 160);
  }

  /**
   * 守护进程 `-d` 下 Workerman 会 fclose(STDOUT)/fclose(STDERR)，ConsoleOutput
   * 构造失败退化为 NullOutput，错误只写 console 就彻底丢失。此时改落可持久化
   * 的日志：优先 webman 的 support\Log（Monolog 落文件），兜底 error_log；
   * 控制台可用时不重复落盘，quiet 模式（测试/显式静默）不落盘。
   *
   * 任何落点（含宿主注入的 $errorSink）抛错都被吞掉，持久化失败绝不能影响
   * 主流程。
   */
  private function persistError(string|iterable $messages, string $level): void
  {
    if (self::$quiet) {
      return;
    }
    if (!$this->output instanceof \Symfony\Component\Console\Output\NullOutput) {
      return;
    }
    $lines = is_array($messages) ? $messages : (is_string($messages) ? [$messages] : iterator_to_array($messages, false));
    $text = '[wegar.basic] ' . implode(PHP_EOL, $lines);
    try {
      if (self::$errorSink !== null) {
        (self::$errorSink)($text);
        return;
      }
      if (class_exists(\support\Log::class)) {
        if ($level === 'critical') {
          \support\Log::critical($text);
        } else {
          \support\Log::error($text);
        }
        return;
      }
      error_log($text);
    } catch (\Throwable) {
      // 持久化失败绝不能影响主流程
    }
  }

  protected function writeln(string|iterable $messages, int $options = 0, ?string $tag = null, int $front = 231, int $back = 240): void
  {
    $this->write($messages, true, $options, $tag, $front, $back);
  }

  function write(string|iterable $messages, bool $newline = false, int $options = 0, ?string $tag = null, $front = 231, $back = 240): void
  {
    if (self::$quiet) {
      return;
    }
    $indent = '';
    if ($tag) {
      $now = date('Y-m-d H:i:s');
      $tag = str_pad($tag, 8);
      $indent = $this->color(str_repeat(' ', strlen($tag) + 22), $front, $back) . ' ';
      $tag = $this->color(" $now $tag ", $front, $back);
    }
    if (is_iterable($messages)) {
      $list = [];
      foreach ($messages as $index => $message) {
        $list[] = ($index ? $indent : "$tag ") . $message;
      }
      $messages = $list;
    } else if ($tag) {
      $messages = "$tag $messages";
    }
    $this->output->write($messages, $newline, $options);
  }

  /**
   * 将会阻断输入，直到用户输入回车
   */
  function alert(string $messages): void
  {
    $helper = new QuestionHelper();
    $this->write($this->color("⚠️ $messages", 160, 231), tag: "Alert", back: 160);
    $this->write(chr(7));
    $question = new ConfirmationQuestion(
      " ⏎ : ",
    );
    $helper->ask($this->input, $this->output, $question);
  }

  function success(string|iterable $messages): void
  {
    $this->writeln($this->addPrefix($messages, '💐'), tag: "Success", back: 70);
  }

  function confirm(string $question, bool $default = false, $trueAnswerRegex = '/^y/ i'): bool
  {
    $helper = new QuestionHelper();
    $this->write(chr(7));
    $this->write("📝 $question", tag: "Confirm", back: 45);
    $q = $default ? 'Y/n' : 'y/N';
    $question = new ConfirmationQuestion(
      " $q : ",
      $default,
      $trueAnswerRegex
    );
    return $helper->ask($this->input, $this->output, $question);
  }

  function input(
    string $messages,
           $default = '',
           $required = false,
           $requiredMessage = '不能为空',
           $hiding = false,
  ): string
  {
    $helper = new QuestionHelper();
    $this->write("📝 $messages", tag: "Input  ", back: 45);
    $default_msg = $default ? "(D: $default) " : "";
    $question = new Question(
      " $default_msg:",
      $default
    );
    if ($hiding) {
      $question->setHidden(true);
      $question->setHiddenFallback(false);
    }
    ask:
    $result = $helper->ask($this->input, $this->output, $question);
    if ($required && !$result) {
      $this->warning($requiredMessage);
      goto ask;
    }
    return $result;
  }

  function select(
    string $question,
    array  $choices,
           $default = null,
  ): string
  {
    $helper = new QuestionHelper();
    $this->write("📋 $question", tag: "Select ", back: 45);
    $question = new ChoiceQuestion(
      ' ⭥ ⏎ : ',
      $choices,
      $default
    );

    return $helper->ask($this->input, $this->output, $question);
  }
}
