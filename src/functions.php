<?php

use config\plugin\wegar\basic\helper\SessionHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use support\Response;

if (!function_exists('json_error')) {
  function json_error(string $msg, int $code = 500, $data = null, int $options = JSON_UNESCAPED_UNICODE): Response
  {
    $result = [
      'code' => $code,
      'msg'  => $msg,
    ];
    if ($data) {
      $result['data'] = $data;
    }
    if (env('DEBUG', false)) {
      $result['debug'] = [
        'data'      => request()->all(),
        'header'    => request()->header(),
        'rawBuffer' => request()->rawBuffer()
      ];
    }
    $response = json($result, $options);
    if (config('app.error_with_status', false)) {
      return $response->withStatus(($code >= 100 && $code < 600) ? $code : 500);
    }
    return $response;
  }
}
if (!function_exists('json_success')) {
  function json_success(mixed $data = null, array $extra = [], int $options = JSON_UNESCAPED_UNICODE): Response
  {
    if ($data instanceof LengthAwarePaginator) {
      return json([
        'data'  => $data->items(),
        'count' => $data->total(),
        ...$extra,
      ], $options);
    }
    return json([
      'data' => $data,
      ...$extra,
    ], $options);
  }
}
if (!function_exists('ss')) {
  function ss(): SessionHelper
  {
    return SessionHelper::getInstance();
  }
}

if (!function_exists('env')) {
  function env($key, $default = null)
  {
    $value = getenv($key);
    $value = $value === false ? $default : $value;
    if ($value && preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
      $value = $matches[2];
    }
    $value = match ($value) {
      'true', '(true)'   => true,
      'false', '(false)' => false,
      'empty', '(empty)' => '',
      'null', '(null)'   => null,
      default            => $value,
    };


    return $value;
  }
}