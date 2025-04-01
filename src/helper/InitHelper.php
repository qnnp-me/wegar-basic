<?php

namespace Wegar\Basic\helper;

use ReflectionClass;
use Throwable;

class InitHelper
{
  /**
   * @var string[]
   */
  static array $results = [];

  static function load(string $init_dir, $namespace = ''): void
  {
    $command_helper = new CommandHelper();
    if (!file_exists($init_dir)) {
      $command_helper->error("Init Files Not Found: $init_dir");
      return;
    }
    $base_path = dirname($init_dir, 2);
    $all_files = IOHelper::scan_files($init_dir);
    $init_functions = [];
    $command_helper->notice('Processing Init Files -> ' . str_replace(base_path(), '', $init_dir));
    foreach ($all_files as $file) {
      $class = str_replace($base_path, '', str_replace(".php", '', $file));
      $class = str_replace('/', '\\', $class);
      if ($namespace) {
        $class = $namespace . preg_replace("#.*[\\\/]([^\\\/]+)\.php#", "\${1}", $file);
      }
      if (class_exists($class) && method_exists($class, 'run')) {
        $class_ref = new ReflectionClass($class);
        if ($class_ref->hasMethod('run')) {
          $weight = $class_ref->hasProperty('weight') ? $class_ref->getDefaultProperties()['weight'] ?? 10 : 10;
          $method = $class_ref->getMethod('run');
          $function = [
            'weight' => $weight,
            'file'   => $file,
          ];
          if ($method->isStatic()) {
            $function['call'] = [$class, 'run'];
          } else {
            $function['call'] = [new $class, 'run'];
          }
          $init_functions[] = $function;
        }
      }
    }
    usort($init_functions, function ($a, $b) {
      return $a['weight'] <=> $b['weight'];
    });
    foreach ($init_functions as $function) {
      try {
        $the_file = str_replace(base_path(), '', $function['file']);
        $command_helper->info("Executing Init File: $the_file");
        call_user_func($function['call']);
      } catch (Throwable $th) {
        $command_helper->error("Executing Init File Error: {$th->getMessage()}\n{$th->getTraceAsString()}");
      }
    }
    if (self::$results) {
      $command_helper->notice(self::$results);
    }
    $command_helper->info('Init Finished -> ' . str_replace(base_path(), '', $init_dir));
  }
}