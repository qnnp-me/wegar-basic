<?php

namespace Tests\Tools;

use RuntimeException;
use SimpleXMLElement;

class CoverageThreshold
{
  public static function percentage(string $cloverFile): float
  {
    if (!is_file($cloverFile)) {
      throw new RuntimeException("clover file not found: $cloverFile");
    }
    $previous = libxml_use_internal_errors(true);
    $xml = simplexml_load_file($cloverFile);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$xml instanceof SimpleXMLElement) {
      throw new RuntimeException("invalid clover xml: $cloverFile");
    }
    $metrics = $xml->project->metrics;
    $covered = (int) $metrics['coveredstatements'];
    $total = (int) $metrics['statements'];
    return $total > 0 ? $covered / $total * 100 : 0.0;
  }

  /**
   * @param resource|null $out
   * @param resource|null $err
   */
  public static function main(string $cloverFile, float $minPercent, $out = null, $err = null): int
  {
    $out ??= STDOUT;
    $err ??= STDERR;
    if (!is_file($cloverFile)) {
      fwrite($err, "clover file not found: $cloverFile\n");
      return 2;
    }
    $pct = self::percentage($cloverFile);
    fwrite($out, sprintf("Line coverage: %.2f%% (min %.2f%%)\n", $pct, $minPercent));
    return $pct + 1e-9 >= $minPercent ? 0 : 1;
  }
}
