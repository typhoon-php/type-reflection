<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$finder = Finder::create()->append([
    __FILE__,
    __DIR__ . '/src/Inheritance/TypeInheritanceResolver.php',
]);

$config = (new Config())
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/' . basename(__FILE__) . '.cache');

(new PhpCsFixerCodingStandard())->applyTo($config, [
    'strict_comparison' => false,
]);

return $config;