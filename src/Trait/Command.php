<?php

namespace Wegar\Basic\Trait;

use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

trait Command
{
  protected InputInterface $input;
  protected OutputInterface $output;
  protected ConsoleColor $consoleColor;

  public function __construct()
  {
    // @phpstan-ignore function.alreadyNarrowedType (防御: trait 也可能被非 Command 类误用)
    if (!is_subclass_of(static::class, \Symfony\Component\Console\Command\Command::class)) {
      throw new \Exception('Command must be subclass of Symfony\Component\Console\Command\Command');
    }
    $this->consoleColor = new ConsoleColor();
    parent::__construct();
  }

  protected function success(string|iterable $messages): void
  {
    $this->writeln($this->addPrefix($messages, '💐'), tag: "Success", back: 70);
  }

  protected function writeln(string|iterable $messages, int $options = 0, ?string $tag = null, int $front = 231, int $back = 240): void
  {
    $this->write($messages, true, $options, $tag, $front, $back);
  }

  protected function write(string|iterable $messages, bool $newline = false, int $options = 0, ?string $tag = null, $front = 231, $back = 240): void
  {
    $indent = '';
    if ($tag) {
      $tag = str_pad($tag, 10);
      $indent = $this->color(str_repeat(' ', strlen($tag) + 2), $front, $back) . ' ';
      $tag = $this->color(" $tag ", $front, $back);
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

  protected function color($str, int $front, ?int $back = null): string
  {
    $str = $this->consoleColor->apply("color_$front", $str);
    if ($back) $str = $this->bgColor($str, $back);
    return $str;
  }

  protected function bgColor($str, int $color): string
  {
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

  protected function info(string|iterable $messages): void
  {
    $this->writeln($this->addPrefix($messages, '🔖'), tag: "Info   ", back: 45);
  }

  protected function failed(string|iterable $messages): void
  {
    $this->writeln($this->addPrefix($messages, '💔'), tag: "Failed ", back: 160);
  }

  protected function error(string|iterable $messages): void
  {
    $this->writeln($this->addPrefix($messages, '🐞'), tag: "Error  ", back: 160);
  }

  protected function notice(string|iterable $messages): void
  {
    $this->writeln($this->addPrefix($messages, '💬'), tag: "Notice ", back: 40);
  }

  protected function alert(string $messages): void
  {
    /** @var QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $this->write($this->color("⚠️ $messages", 160, 231), tag: "Alert", back: 160);
    $this->write(chr(7));
    $question = new ConfirmationQuestion(
      " ⏎ : ",
    );
    $helper->ask($this->input, $this->output, $question);
  }

  protected function confirm(string $question, bool $default = false, $trueAnswerRegex = '/^y/ i'): bool
  {
    /** @var QuestionHelper $helper */
    $helper = $this->getHelper('question');
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

  protected function input(
    string $messages,
           $default = '',
           $required = false,
           $requiredMessage = '不能为空',
           $hiding = false,
  ): string
  {
    /** @var QuestionHelper $helper */
    $helper = $this->getHelper('question');
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

  protected function warning(string|iterable $messages): void
  {
    $this->writeln($this->addPrefix($messages, '🚨'), tag: "Warning", back: 220);
  }

  protected function select(
    string $question,
    array  $choices,
           $default = null,
  ): string
  {
    /** @var QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $this->write("📋 $question", tag: "Select ", back: 45);
    $question = new ChoiceQuestion(
      ' ⭥ ⏎ : ',
      $choices,
      $default
    );

    return $helper->ask($this->input, $this->output, $question);
  }
}
