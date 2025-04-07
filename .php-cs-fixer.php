<?php

use ErickSkrauch\PhpCsFixer\Fixers;
use PhpCsFixer\Config;

$finder = new PhpCsFixer\Finder()->in(__DIR__);

$config = new Config()
    ->registerCustomFixers(new Fixers())
    ->setRules([
        '@PHP82Migration' => true,
        'ErickSkrauch/align_multiline_parameters' => true,
    ]);

return $config;
