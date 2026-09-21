<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Wegar\Basic\Trait\Command as CommandTrait;

class TraitHost extends SymfonyCommand
{
  use CommandTrait;

  public function exposeAddPrefix(string|iterable $messages, $prefix = ''): array|string
  {
    return $this->addPrefix($messages, $prefix);
  }

  public function exposeColor($str, int $front, ?int $back = null): string
  {
    return $this->color($str, $front, $back);
  }

  public function exposeWrite(string|iterable $messages): void
  {
    $this->write($messages);
  }

  public function exposeInfo(string|iterable $messages): void
  {
    $this->info($messages);
  }

  public function exposeSuccess(string|iterable $messages): void
  {
    $this->success($messages);
  }

  public function exposeConfirm(string $question): bool
  {
    return $this->confirm($question);
  }

  public function exposeSelect(string $question, array $choices): string
  {
    return $this->select($question, $choices);
  }

  public function exposeInput(string $messages): string
  {
    return $this->input($messages);
  }

  public function io(string $input, BufferedOutput $output): void
  {
    $stream = fopen('php://memory', 'r+');
    if ($input !== '') {
      fwrite($stream, $input);
      rewind($stream);
    }
    $this->input = new StringInput('');
    $this->input->setStream($stream);
    $this->output = $output;
    $this->setHelperSet(new HelperSet([new QuestionHelper()]));
  }
}

class CommandTraitTest extends TestCase
{
  public function testAddPrefixScalar(): void
  {
    $this->assertSame('> x', (new TraitHost())->exposeAddPrefix('x', '>'));
  }

  public function testAddPrefixIterable(): void
  {
    $this->assertSame(['> a', '  b'], (new TraitHost())->exposeAddPrefix(['a', 'b'], '>'));
  }

  public function testColorContainsText(): void
  {
    $this->assertStringContainsString('x', (new TraitHost())->exposeColor('x', 231));
  }

  public function testWriteEmitsMessage(): void
  {
    $host = new TraitHost();
    $out = new BufferedOutput();
    $host->io('', $out);
    $host->exposeWrite('hello');
    $this->assertStringContainsString('hello', $out->fetch());
  }

  public function testInfoAndSuccessEmit(): void
  {
    $host = new TraitHost();
    $out = new BufferedOutput();
    $host->io('', $out);
    $host->exposeInfo('info-msg');
    $host->exposeSuccess('done-msg');
    $buffer = $out->fetch();
    $this->assertStringContainsString('info-msg', $buffer);
    $this->assertStringContainsString('done-msg', $buffer);
  }

  public function testConfirmYes(): void
  {
    $host = new TraitHost();
    $host->io('y', new BufferedOutput());
    $this->assertTrue($host->exposeConfirm('sure?'));
  }

  public function testConfirmNo(): void
  {
    $host = new TraitHost();
    $host->io('n', new BufferedOutput());
    $this->assertFalse($host->exposeConfirm('sure?'));
  }

  public function testSelectReturnsChoice(): void
  {
    $host = new TraitHost();
    $host->io('0', new BufferedOutput());
    $this->assertSame('a', $host->exposeSelect('pick', ['a', 'b']));
  }

  public function testInputReturnsValue(): void
  {
    $host = new TraitHost();
    $host->io('hello', new BufferedOutput());
    $this->assertSame('hello', $host->exposeInput('name?'));
  }
}