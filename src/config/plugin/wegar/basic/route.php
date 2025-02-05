<?php

use Webman\Route;
use Wegar\Basic\helper\RouteHelper;

Route::get(
  '/wegar/basic/[{path:.+}]',
  fn(string $path) => RouteHelper::fileResponse(
    base_path() . '/vendor/wegar/basic/src/assets/' . $path
  )
);