<?php declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$config = (new Config('mock-server'))
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setLineEnding("\n")
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@PSR12' => true,
        '@PER-CS' => true,
        'blank_line_after_opening_tag' => false,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_lines_before_namespace' => true,
        'cast_spaces' => ['space' => 'none'],
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        'function_to_constant' => true,
        'line_ending' => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_invocation' => ['include' => ['@internal'], 'scope' => 'namespaced'],
        'no_empty_phpdoc' => true,
        'no_extra_blank_lines' => ['tokens' => ['extra']],
        'no_superfluous_phpdoc_tags' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'non_printable_character' => true,
        'normalize_index_brace' => true,
        'nullable_type_declaration' => ['syntax' => 'union'],
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_imports' => ['imports_order' => ['class', 'const', 'function']],
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => true,
        'php_unit_fqcn_annotation' => true,
        'phpdoc_summary' => true,
        'phpdoc_types' => true,
        'psr_autoloading' => ['dir' => __DIR__ . '/src'],
        'return_type_declaration' => ['space_before' => 'none'],
        'short_scalar_cast' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_functions' => false,
            'import_constants' => false,
        ],
    ]);

$config->getFinder()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/test')
    ->in(__DIR__ . '/migrates');

$config->setCacheFile(__DIR__ . '/.cache/.php_cs.cache');

return $config;