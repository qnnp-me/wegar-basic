<?php

namespace Wegar\Basic\Command;

use Phinx\Console\PhinxApplication;
use Phinx\Wrapper\TextWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Phinx extends Command
{
  protected static $defaultName = 'phinx';
  protected static $defaultDescription = 'Phinx Command for Phinx';

  protected function configure()
  {
    $this->ignoreValidationErrors();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    global $argv;
    unset($argv[1]);
    $argv = [...$argv];
    $app = new PhinxApplication();
    if (is_phar()) {
      $config_path = base_path('phinx.php');
      $wrap = new TextWrapper($app);
      $wrap->setOption('configuration', $config_path);
      // return $wrap->getApp()->doRun(new ArgvInput([...$argv]), $output);
      print match ($argv[1] ?? '') {
        'm', 'migrate'  => $wrap->getMigrate(),
        'r', 'rollback' => $wrap->getRollback(),
      };
      return $wrap->getExitCode();
    } else {
      return (new PhinxApplication())->doRun(new ArgvInput($argv), $output);
    }
  }
}