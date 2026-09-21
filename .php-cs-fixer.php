<?php

$finder = PhpCsFixer\Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tests']);
return (new PhpCsFixer\Config())
  ->setRiskyAllowed(false)
  ->setRules([
    'array_syntax' => ['syntax' => 'short'],
    'ordered_imports' => true,
    'no_unused_imports' => true,
    'blank_line_after_opening_tag' => true,
    'trailing_comma_in_multiline' => true,
  ])
  ->setFinder($finder);