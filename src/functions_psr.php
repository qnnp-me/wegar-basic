<?php

namespace Wegar\Basic;

use config\plugin\wegar\basic\helper\SessionHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use support\Response;

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

function ss(): SessionHelper
{
  return SessionHelper::getInstance();
}

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
  if (is_string($value)) {
    if (
      (
        str_starts_with($value, '[') && str_ends_with($value, ']') && !filter_var(substr($value, 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
      )
      || (str_starts_with($value, '{') && str_ends_with($value, '}'))) {
      $value = json_decode($value, true);
    }
  }
  if (is_string($value) && is_numeric($value)) {
    $value = match (true) {
      str_contains($value, '.') => (float)$value,
      str_contains($value, 'e') => (float)$value,
      default                   => (int)$value,
    };
  }
  return $value;
}
