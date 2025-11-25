<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

// Rector configuration for Laravel 10/11 compatibility
// Note: Avoids generic types (not supported in Laravel 10 PHPDoc)
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        // Skip vendor and cache directories
        __DIR__ . '/vendor',
        __DIR__ . '/storage',
        __DIR__ . '/bootstrap/cache',

        // Skip specific rules if needed
        EncapsedStringsToSprintfRector::class,
    ])
    ->withPhpSets(
        php82: true,  // Use PHP 8.2 features
    )
    ->withPreparedSets(
        deadCode: true,           // Remove dead code
        codeQuality: true,        // Improve code quality
        codingStyle: true,        // Apply coding style improvements
        typeDeclarations: true,   // Add type declarations
        privatization: true,      // Privatize where possible
        naming: true,             // Improve naming
        instanceOf: true,         // Simplify instanceof checks
        earlyReturn: true,        // Use early returns
    )
    ->withRules([
        // Constructor promotion (PHP 8.0+)
        ClassPropertyAssignToConstructorPromotionRector::class,
    ])
    ->withImportNames(
        importShortClasses: false,  // Don't import classes from global namespace
        removeUnusedImports: true,   // Remove unused imports
    );
