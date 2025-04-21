<?php

use Wegar\Basic\Process\InitProcess;

return [
  'init' => [
    'handler'     => InitProcess::class,
    'reloadable'  => false,
    'constructor' => [],
  ],
];