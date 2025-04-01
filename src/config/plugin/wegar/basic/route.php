<?php

use Webman\Route;
use Wegar\Basic\helper\RouteHelper;

Route::get('/wegar/basic/[{path:.+}]', fn($path) => RouteHelper::fileResponse(base_path() . '/vendor/wegar/basic/src/assets/' . $path));

if (Route::getFallback() === null) {
  Route::fallback(function () {
    if (request()->expectsJson()) {
      return json_error('Not Found', 404);
    }
    return preg_match("#\.[^.]+$#", request()->path())
      ? not_found()
      : RouteHelper::fileResponse(base_path() . '/vendor/wegar/basic/src/assets/index.html');
  }
  );
}