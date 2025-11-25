<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

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

        // Inline constructor defaults to properties
        InlineConstructorDefaultToPropertyRector::class,

        // Add void return type where no return
        AddVoidReturnTypeWhereNoReturnRector::class,

        // Typed properties from strict constructor
        TypedPropertyFromStrictConstructorRector::class,

        // Readonly properties (PHP 8.1+)
        ReadOnlyPropertyRector::class,

        // Return type from return new
        ReturnTypeFromReturnNewRector::class,
    ])
    ->withImportNames(
        importShortClasses: false,  // Don't import classes from global namespace
        removeUnusedImports: true,   // Remove unused imports
    );
