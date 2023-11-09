<?php

const DIRS = [
	__DIR__ . '/src',
	__DIR__ . '/tests',
];

$finder = (new \PhpCsFixer\Finder())
	->in(DIRS)
	->name('/\.phpt?$/');

return (new PhpCsFixer\Config())
    ->setRules([
        'no_whitespace_in_blank_line' => true,
		'no_closing_tag' => true,
		'ternary_to_null_coalescing' => true,
		'ternary_operator_spaces' => true,
		'operator_linebreak' => [
			'position' => 'beginning',
		],
		'unary_operator_spaces' => true,
		'binary_operator_spaces' => true,
		'new_with_parentheses' => true,
		'no_space_around_double_colon' => true,
		'concat_space' => [
			'spacing' => 'one',
		],
		'object_operator_without_whitespace' => true,
		'clean_namespace' => true,
        'array_indentation' => true,
        'no_spaces_around_offset' => true,
		'array_syntax' => true,
		'list_syntax' => true,
		'attribute_empty_parentheses' => [
			'use_parentheses' => true,
		],
		'blank_line_after_namespace' => true,
		'single_space_around_construct' => true,
		'yoda_style' => [
			'equal' => false,
			'identical' => false,
			'less_and_greater' => false,
		],
		'elseif' => true,
		'no_superfluous_elseif' => true,
		'include' => true,
		'explicit_indirect_variable' => true,
		'declare_parentheses' => true,
		'declare_equal_normalize' => true,
		'combine_consecutive_unsets' => true,
		'single_line_after_imports' => true,
		'single_import_per_statement' => true,
		'no_unused_imports' => true,
		'no_unneeded_import_alias' => true,
		'no_leading_import_slash' => true,
		'global_namespace_import' => [
			'import_classes' => false,
			'import_constants' => false,
			'import_functions' => false,
		],
		'fully_qualified_strict_types' => true,
		'no_spaces_after_function_name' => true,
		'nullable_type_declaration_for_default_null_value' => true,
		'method_argument_space' => true,
		'lambda_not_used_import' => true,
		'function_declaration' => [
			'closure_fn_spacing' => 'none',
			'closure_function_spacing' => 'none',
		],
		'trailing_comma_in_multiline' => [
			'elements' => [
				'arguments',
				'arrays',
				'match',
				'parameters',
			],
		],
		'switch_case_space' => true,
		'switch_continue_to_break' => true,
		'switch_case_semicolon_to_colon' => true,
		'no_useless_else' => true,
		'braces_position' => [
			'allow_single_line_anonymous_functions' => true,
			'allow_single_line_empty_anonymous_classes' => true,
			'anonymous_classes_opening_brace' => 'same_line',
			'anonymous_functions_opening_brace' => 'same_line',
			'classes_opening_brace' => 'same_line',
			'control_structures_opening_brace' => 'same_line',
			'functions_opening_brace' => 'same_line',
		],
		'no_short_bool_cast' => true,
		'lowercase_cast' => true,
		'cast_spaces' => true,
		'lowercase_keywords' => true,
		'lowercase_static_reference' => true,
		'magic_constant_casing' => true,
		'magic_method_casing' => true,
		'native_function_casing' => true,
		'integer_literal_case' => true,
		'control_structure_braces' => true,
		'control_structure_continuation_position' => [
			'position' => 'same_line',
		],
		'no_multiple_statements_per_line' => true,
		'statement_indentation' => true,
		'no_multiline_whitespace_around_double_arrow' => true,
		'normalize_index_brace' => true,
		'octal_notation' => true,
        'indentation_type' => true,
		'visibility_required' => true,
		'whitespace_after_comma_in_array' => true,
		'types_spaces' => [
			'space' => 'none',
		],
		'single_line_empty_body' => true,
		'method_chaining_indentation' => true,
		'single_blank_line_at_eof' => true,
		'spaces_inside_parentheses' => [
			'space' => 'none',
		],
		'type_declaration_spaces' => true,
		'compact_nullable_type_declaration' => true,
		'space_after_semicolon' => [
			'remove_in_empty_for_expressions' => true,
		],
		'no_trailing_comma_in_singleline' => true,
		'encoding' => true,
		'no_singleline_whitespace_before_semicolons' => true,
		'no_empty_statement' => true,
		'no_useless_return' => true,
		'return_type_declaration' => true,
		'multiline_whitespace_before_semicolons' => true,
		'multiline_comment_opening_closing' => true,
		'no_trailing_whitespace_in_comment' => true,
		'single_line_comment_spacing' => true,
		'single_line_comment_style' => true,
		'phpdoc_var_without_name' => true,
		'phpdoc_var_annotation_correct_order' => true,
		'phpdoc_trim' => true,
		'phpdoc_trim_consecutive_blank_line_separation' => true,
		'phpdoc_summary' => true,
		'phpdoc_to_comment' => [
			'ignored_tags' => [
				// To allow "/** @var SomeType $someVar */" annotations over
				// variables for IDEs. PHP Intelephense (vscode extension), for
				// example, wouldn't recognize "// @var SomeType $someVar"
				// properly.
				'var',
			],
		],
		'phpdoc_single_line_var_spacing' => true,
		'phpdoc_scalar' => true,
		'phpdoc_param_order' => true,
		'phpdoc_order' => true,
		'phpdoc_indent' => true,
		'no_superfluous_phpdoc_tags' => true,
		'no_empty_phpdoc' => true,
		'no_blank_lines_after_phpdoc' => true,
        'align_multiline_comment' => [
			'comment_type' => 'all_multiline',
		],
    ])
    ->setIndent("\t")
    ->setLineEnding("\n")
	->setFinder($finder);
