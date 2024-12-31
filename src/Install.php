<?php

namespace Wegar\Basic;

class Install
{
  const WEBMAN_PLUGIN = true;

  protected static array $pathRelation = [
    'config/plugin/wegar/basic' => 'config/plugin/wegar/basic',
  ];

  public static function install(): void
  {
    static::installByRelation();
  }

  public static function uninstall(): void
  {
    self::uninstallByRelation();
  }

  public static function installByRelation(): void
  {
    foreach (static::$pathRelation as $source => $dest) {
      if ($pos = strrpos($dest, '/')) {
        $parent_dir = base_path() . '/' . substr($dest, 0, $pos);
        if (!is_dir($parent_dir)) {
          mkdir($parent_dir, 0777, true);
        }
      }
      //symlink(__DIR__ . "/$source", base_path()."/$dest");
      copy_dir(__DIR__ . "/$source", base_path() . "/$dest");
      echo "Create $dest
";
    }
  }

  public static function uninstallByRelation(): void
  {
    foreach (static::$pathRelation as $source => $dest) {
      $path = base_path() . "/$dest";
      if (!is_dir($path) && !is_file($path)) {
        continue;
      }
      echo "Remove $dest
";
      if (is_file($path) || is_link($path)) {
        unlink($path);
        continue;
      }
      remove_dir($path);
    }
  }

}