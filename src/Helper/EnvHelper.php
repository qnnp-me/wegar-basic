<?php

namespace Wegar\Basic\Helper;

/**
 * `.env` 优化命令的纯逻辑：从配置数组中挑出适合走 `.env` 的连接/凭据叶子键，
 * 并把配置文件源码里的字面量改写为 `env('KEY', 原值)`。无 I/O、可单测。
 */
class EnvHelper
{
  /** 只收集连接/凭据类叶子键；命中（子串）才认为适合走 .env */
  private const KEY_PATTERN = '/(?:pass(?:word)?|secret|token|salt|host|port|database|dbname|username|user|dsn|url|endpoint|addr)/i';

  /**
   * @param array<string, mixed> $config 已 require 的配置数组
   * @param string $file 配置文件名（不含扩展名），用于生成 ENV key
   * @return array<int, array{path: string[], envKey: string, value: scalar}>
   */
  public static function candidates(array $config, string $file): array
  {
    $out = [];
    self::walk($config, [], $file, $out);
    return $out;
  }

  public static function envKey(string $file, array $path): string
  {
    $raw = implode('_', array_merge([$file], $path));
    return strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $raw));
  }

  /**
   * 把源码中命中候选路径的标量字面量改写为 env('KEY', 原值)。
   * 已是 env(...) 的行不会再次改写（幂等）。
   *
   * @param array<int, array{path: string[], envKey: string, value: scalar}> $candidates
   * @return array{source: string, changed: int}
   */
  public static function rewrite(string $source, array $candidates): array
  {
    $byPath = [];
    foreach ($candidates as $candidate) {
      $byPath[implode("\0", $candidate['path'])] = $candidate['envKey'];
    }

    if ($byPath === []) {
      return ['source' => $source, 'changed' => 0];
    }

    $trailingNewline = str_ends_with($source, "\n");
    $lines = preg_split('/\r\n|\r|\n/', $source);
    $stack = [];
    $changed = 0;

    foreach ($lines as $i => $line) {
      // 进入 `'key' => [` 形式的数组
      if (preg_match('/^\s*([\'"])([^\'"]+)\1\s*=>\s*\[\s*$/', $line, $m)) {
        $stack[] = $m[2];
        continue;
      }
      // 结束一层数组
      if (preg_match('/^\s*\],?\s*$/', $line)) {
        array_pop($stack);
        continue;
      }
      // `'key' => 字面量,` 形式的赋值
      if (preg_match('/^(\s*)([\'"])([^\'"]+)\2\s*=>\s*(.+?)(,?)\s*$/', $line, $m)) {
        $path = array_merge($stack, [$m[3]]);
        $id = implode("\0", $path);
        if (isset($byPath[$id]) && self::isPlainScalar(trim($m[4]))) {
          $lines[$i] = $m[1] . "'{$m[3]}' => env('{$byPath[$id]}', " . trim($m[4]) . ")" . $m[5];
          $changed++;
        }
      }
    }

    $result = implode("\n", $lines);
    if ($trailingNewline && !str_ends_with($result, "\n")) {
      $result .= "\n";
    }

    return ['source' => $result, 'changed' => $changed];
  }

  private static function isPlainScalar(string $value): bool
  {
    return (bool)preg_match('/^(?:\'[^\']*\'|"[^"]*"|-?\d+(?:\.\d+)?|true|false)$/i', $value);
  }

  /**
   * @param array<string, mixed> $config
   * @param string[] $path
   * @param array<int, array{path: string[], envKey: string, value: scalar}> $out
   */
  private static function walk(array $config, array $path, string $file, array &$out): void
  {
    foreach ($config as $key => $value) {
      $current = array_merge($path, [(string)$key]);
      if (is_array($value)) {
        self::walk($value, $current, $file, $out);
        continue;
      }
      if (!is_scalar($value) || !preg_match(self::KEY_PATTERN, (string)$key)) {
        continue;
      }
      $out[] = [
        'path' => $current,
        'envKey' => self::envKey($file, $current),
        'value' => $value,
      ];
    }
  }
}
