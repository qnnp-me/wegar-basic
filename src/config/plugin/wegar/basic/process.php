<?php

use Wegar\Basic\process\InitProcess;

return [
  'init' => [
    'handler'     => InitProcess::class,
    'reloadable'  => false,
    'constructor' => [],
  ],
];