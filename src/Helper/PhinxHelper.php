<?php

namespace Wegar\Basic\Helper;

use Phinx\Console\PhinxApplication;
use Phinx\Wrapper\TextWrapper;

class PhinxHelper
{
  static private array $config = [];

  static function load(string $config_path): void
  {
    self::$config = include $config_path;
    $command_helper = new CommandHelper();
    if (!is_file($config_path)) {
      $command_helper->error("Phinx config file not found: " . str_replace(base_path(), '', $config_path));
      return;
    }
    $command_helper->notice("Loading Phinx -> " . str_replace(base_path(), '', $config_path));
    $app = new PhinxApplication();
    $wrap = new TextWrapper($app);
    $wrap->setOption('configuration', $config_path);

    self::prepare_seeds($seeds);

    $migrate_result = $wrap->getMigrate();
    $has_error = str_contains($migrate_result, 'Exception:');
    if ($has_error || str_contains($migrate_result, ' == ')) {
      $command_helper->notice('Phinx Migrate');
      $command_helper->{$has_error ? 'error' : 'info'}(explode("\n", $migrate_result));
    }

    $seed_result = $wrap->getSeed(seed: $seeds);
    $has_error = str_contains($seed_result, 'Exception:');
    if ($has_error || str_contains($seed_result, ' == ')) {
      $command_helper->notice('Phinx Seed');
      $command_helper->{$has_error ? 'error' : 'info'}(explode("\n", $seed_result));
    }
  }

  private static function prepare_seeds(&$seeds = []): void
  {
    $seed_path = self::$config['paths']['seeds'];
    if (is_dir($seed_path)) {
      foreach (scandir($seed_path) as $file) {
        if (str_ends_with($file, '.php')) {
          $class_name = substr($file, 0, -4);
          if (class_exists($class_name)) {
            $seeds[] = "$class_name";
          }
        }
      }
    }
  }
}