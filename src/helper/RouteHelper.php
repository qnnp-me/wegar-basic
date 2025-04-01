<?php

namespace Wegar\Basic\helper;

use support\Response;
use Webman\Route;

class RouteHelper
{
  /**
   * 注册 Wegar 远程组件文件路由
   * @param string $name
   * @param string $component_file_path
   * @param string $css_file_path
   * @param string $route_prefix
   * @param string $route_suffix
   * @param bool $need_base_url
   * @return void
   */
  static function registerComponent(
    string $name,
    string $component_file_path,
    string $css_file_path = '',
    string $route_prefix = '',
    string $route_suffix = '[{path:/?.+}]',
    bool   $need_base_url = false
  ): void
  {
    if (!file_exists($component_file_path)) return;

    $route = "/{$name}";
    if ($route_prefix) {
      $route = '/' . trim($route_prefix, '/\\') . $route;
    }
    Route::get(
      "/wegar/component" . $route . '.umd.cjs',
      fn() => static::fileResponse($component_file_path)
    );
    if ($need_base_url) {
      Route::get(
        $route . $route_suffix,
        fn() => static::fileResponse(implode(DIRECTORY_SEPARATOR, [
          dirname(__DIR__),
          'assets',
          'index.html'
        ]))
      );
    }
    Route::get(
      "/wegar/component" . $route . '.css',
      fn() => $css_file_path && is_readable($css_file_path) ? static::fileResponse($css_file_path) : response()
    );
  }

  static function fileResponse(string $file_path): Response
  {
    if (!file_exists($file_path)) {
      if (request()->expectsJson()) {
        return json_error('Not Found', 404);
      }
      return not_found();
    }
    $file_info = pathinfo($file_path);
    $has_modified_response_method = method_exists(response(), 'getMimeTypeMap');
    if ($has_modified_response_method) {
      $content_type = response()->getMimeTypeMap()[$file_info['extension']] ?? mime_content_type($file_path);
    } else {
      $content_type = mime_content_type($file_path);
    }
    return response()->withFile($file_path)->withHeader('Content-Type', $content_type);
  }
}