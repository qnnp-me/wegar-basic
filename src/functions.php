<?php

use config\plugin\wegar\basic\helper\SessionHelper;
use support\Response;

if (!function_exists('json_error')) {
  function json_error(string $msg, int $code = 500, $data = null, int $options = JSON_UNESCAPED_UNICODE): Response
  {
    return \Wegar\Basic\json_error($msg, $code, $data, $options);
  }
}
if (!function_exists('json_success')) {
  function json_success(mixed $data = null, array $extra = [], int $options = JSON_UNESCAPED_UNICODE): Response
  {
    return \Wegar\Basic\json_success($data, $extra, $options);
  }
}
if (!function_exists('ss')) {
  function ss(): SessionHelper
  {
    return \Wegar\Basic\ss();
  }
}

if (!function_exists('env')) {
  function env($key, $default = null)
  {
    return \Wegar\Basic\env($key, $default);
  }
}
if (!function_exists('getAllFiles')) {
  function getAllFiles($abs_path): array
  {
    return \Wegar\Basic\getAllFiles($abs_path);
  }
}
