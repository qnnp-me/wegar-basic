<?php

namespace Wegar\Basic\Helper;

use Generator;
use Phar;

class IOHelper
{

  /**
   * @param string $from 要释放的目录或者文件路径 如 .env.example
   * @param string $to 释放到的目录
   * @param bool $overwrite
   * @return void
   */
  static function release(string $from, string $to, bool $overwrite = true): void
  {
    $command_helper = new CommandHelper();
    static $phar;
    if (!$phar) {
      $phar = new Phar(Phar::running());
    }
    try {
      if (is_phar()) {
        if (!file_exists($to)) {
          mkdir($to, recursive: true);
        }
        if (!$overwrite && file_exists($to . DIRECTORY_SEPARATOR . $from)) {
          $command_helper->warning("$to" . DIRECTORY_SEPARATOR . "$from exists, skip");
          return;
        }
        $phar->extractTo($to, $from, $overwrite);
        $command_helper->info("Release $from to $to");
      }
    } catch (\Exception $e) {
      $command_helper->error("Release $from failed -> {$e->getMessage()}");
    }
  }

  /**
   * @param string $path file or dir path
   * @param array|string $include Ex: `'*.php'` `['.php', '.js']` `['~^[A-Z].*\.php$~', '/\.js$/']`
   * @param array|string $exclude Ex: `'*.php'` `['.php', '.js']` `['~^[A-Z].*\.php$~', '/\.js$/']`
   * @return Generator
   */
  static function scan_files(string $path, array|string $include = [], array|string $exclude = []): Generator
  {
    if (!is_array($include)) $include = [$include];
    if (!is_array($exclude)) $exclude = [$exclude];
    if (is_file($path)) {
      yield $path;
    } else {
      $items = is_dir($path) ? scandir($path) : [];
      foreach ($items as $item) {
        if (in_array($item, ['.', '..'])) continue;
        $item_path = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($item_path)) {
          yield from static::scan_files($item_path, $include, $exclude);
        } else {
          $match_check = function (array $include, $item, bool $default = false) {
            foreach ($include as $i) {
              $is_preg = preg_match('~^([/#\~]).+([/#\~])$~', $i);
              if ($is_preg && preg_match($i, $item)) {
                return true;
              }
              $is_pan = !$is_preg && (str_starts_with($i, '*') || str_starts_with($i, '.'));
              if ($is_pan && str_ends_with($item, str_replace('*', '', $i))) {
                return true;
              }
              if ($i == $item) {
                return true;
              }
            }
            return $default;
          };
          $include_match = $match_check($include, $item, empty($include));
          $exclude_match = $match_check($exclude, $item, false);
          if ($include_match && $exclude_match) continue;
          if (!$include_match && !$exclude_match) continue;
          yield $item_path;
        }
      }
    }
  }
}