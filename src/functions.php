<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use support\Response;
use Wegar\Basic\helper\SessionHelper;

if (!function_exists('json_error')) {
  function json_error(string $msg, int $code = 500, $data = null): Response
  {
    $debug = env('APP_DEBUG');
    $result = [
      'code' => $code,
      'msg'  => $msg,
    ];
    if ($data) {
      $result['data'] = $data;
    }
    if ($debug) {
      $result['debug'] = [
        'data'   => request()->all(),
        'header' => request()->header(),
      ];
    }
    return json($result)->withStatus(($code >= 100 && $code < 600) ? $code : 500);
  }
}
if (!function_exists('json_success')) {
  function json_success(mixed $data = null, array $extra = []): Response
  {
    if ($data instanceof LengthAwarePaginator) {
      return json([
        'data'  => $data->items(),
        'count' => $data->total(),
        ...$extra,
      ]);
    }
    return json([
      'data' => $data,
      ...$extra,
    ]);
  }
}
if (!function_exists('ss')) {
  function ss(): SessionHelper
  {
    return SessionHelper::getInstance();
  }
}

