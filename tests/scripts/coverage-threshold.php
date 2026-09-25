<?php

require __DIR__ . '/../../vendor/autoload.php';

$clover = $argv[1] ?? 'build/coverage/clover.xml';
$min = (float) ($argv[2] ?? 85);

exit(\Tests\Tools\CoverageThreshold::main($clover, $min));
