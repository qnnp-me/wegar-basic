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
    try {
      if (is_phar()) {
        if (!$phar) {
          $phar = new Phar(Phar::running());
        }
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
    } catch (\Throwable $e) {
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
      if (self::match_path($include, $exclude, basename($path))) {
        yield $path;
      }
    } else {
      $items = is_dir($path) ? scandir($path) : [];
      foreach ($items as $item) {
        if (in_array($item, ['.', '..'])) continue;
        $item_path = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($item_path)) {
          yield from static::scan_files($item_path, $include, $exclude);
        } elseif (self::match_path($include, $exclude, $item)) {
          yield $item_path;
        }
      }
    }
  }

  /**
   * 条目名是否通过 include（白名单，空则全通过）且未命中 exclude（黑名单）。
   * 目录条目用文件/目录名，单文件路径用 basename，语义一致。
   *
   * @param string[] $include
   * @param string[] $exclude
   */
  private static function match_path(array $include, array $exclude, string $item): bool
  {
    return self::match_patterns($include, $item, empty($include))
      && !self::match_patterns($exclude, $item, false);
  }

  /**
   * @param string[] $patterns
   */
  private static function match_patterns(array $patterns, string $item, bool $default): bool
  {
    foreach ($patterns as $pattern) {
      $is_preg = preg_match('~^([/#\~]).+([/#\~])$~', $pattern);
      if ($is_preg && preg_match($pattern, $item)) {
        return true;
      }
      $is_pan = !$is_preg && (str_starts_with($pattern, '*') || str_starts_with($pattern, '.'));
      if ($is_pan && str_ends_with($item, str_replace('*', '', $pattern))) {
        return true;
      }
      if ($pattern == $item) {
        return true;
      }
    }
    return $default;
  }
}