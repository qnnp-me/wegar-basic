<?php

namespace Wegar\Basic\helper;

use support\Response;
use Webman\Route;

class RouteHelper
{
  static function addPage(
    string $component_name,
    string $component_file_path,
    bool   $is_admin = false,
  ): void
  {
    $admin_prefix = config('plugin.WegarAdmin.config.admin_route_prefix', 'admin');
    $route = '/' . ($is_admin ? $admin_prefix . '/' : '') . $component_name; // /{component name} or /admin/{component name}

    if (!$is_admin) {
      Route::get(
        $route . '[{path:/?.+}]',
        fn() => static::fileResponse(implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'assets', 'index.html']))
      );
    }
    Route::get(
      "/wegar/component/" . ($is_admin ? $admin_prefix . '/' : '') . $component_name . '/index.js',
      fn() => static::fileResponse($component_file_path)
    );
    // Route::get(
    //   "/wegar/component/" . ($is_admin ? $admin_prefix . '/' : '') . $component_name . '/{file:.+}',
    //   fn(string $file) => static::fileResponse(dirname($component_file_path) . DIRECTORY_SEPARATOR . $file)
    // );
  }

  static function fileResponse(string $file_path): Response
  {
    $file_info = pathinfo($file_path);
    $content_type = response()->getMimeTypeMap()[$file_info['extension']] ?? mime_content_type($file_path);
    return response()->withFile($file_path)->withHeader('Content-Type', $content_type);
  }
}