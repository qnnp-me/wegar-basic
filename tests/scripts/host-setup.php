<?php

require __DIR__ . '/../../vendor/autoload.php';

$run = function (array $cmd, string $cwd): array {
  $descriptors = [1 => ['pipe', 'w'], 2 => ['redirect', 1]];
  $proc = proc_open($cmd, $descriptors, $pipes, $cwd);
  if (!is_resource($proc)) {
    return [1, 'proc_open failed'];
  }
  $out = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  return [proc_close($proc), $out];
};

try {
  \Tests\Tools\HostApp::setup($run);
  echo "Host ready at " . \Tests\Tools\HostApp::path() . "\n";
} catch (\Throwable $e) {
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}
