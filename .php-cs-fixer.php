<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/bin']);
$config = new Config();

return $config->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_after_opening_tag' => false,
    ])
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
