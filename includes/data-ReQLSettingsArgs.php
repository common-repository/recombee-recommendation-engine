<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* 'equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null' */

$reql_funcs	= array(
	'expression' => array(
		'label'		=> 'Expression',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder' 	=> 'any valid ReQL expression',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '%args%',
				'args_delim'	=> ',',
				'args_type'		=> 'any',
			),
			'reference'			=> array(
				'drop_name'		=> true,
				'exclude_for'	=> array(),
			),
		),
	),
	'context_item' => ( isset($prop_key_value['context_item']) ) ? array(
		'label'		=> 'Current product',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> true,
				'placeholder' 	=> 'product property',
				'options'		=> $prop_key_value['context_item'],
				'selected'		=> false,
				'wrapper'		=> '["%args%"]',
				'args_delim'	=> ',',
				'args_type'		=> array('string'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array('ProductsToCustomer','CustomersToCustomer'),
			),
		),
	) : false,
	'context_user' => ( isset($prop_key_value['context_user']) ) ? array(
		'label'		=> 'Current user',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> true,
				'placeholder' 	=> 'user property',
				'options'		=> $prop_key_value['context_user'],
				'selected'		=> false,
				'wrapper'		=> '["%args%"]',
				'args_delim'	=> ',',
				'args_type'		=> array('string'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array('CustomersToProduct','ProductsToProduct'),
			),
		),
	) : false,
	'earth_distance' => array(
		'label'		=> 'Earth distance',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 4,
				'select_only'	=> false,
				'placeholder' 	=> 'lat1, lon1, lat2, lon2',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> array('number'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'now' => array(
		'label'		=> 'Now',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 0,
				'select_only'	=> false,
				'placeholder' 	=> __('no args needed', 'recombee-recommendation-engine'),
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '()',
				'args_delim'	=> ',',
				'args_type'		=> false,
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'size' => array(
		'label'		=> 'Size',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder' 	=> 'value to evaluate',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '("%args%")',
				'args_delim'	=> ',',
				'args_type'		=> array('string', 'array'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'num_item_purchases' => array(
		'label' => 'Num_item_purchases',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder'	=> 'item id',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> array('string'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'map' => array(
		'label' => 'Map',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 2,
				'select_only'	=> false,
				'placeholder'	=> 'EXPRESSION, input_set',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> array('string'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'boolean' => array(
		'label' => 'Boolean',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder' 	=> 'value to convert',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> 'any',
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'number' => array(
		'label' => 'Number',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder' 	=> 'value to convert',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> array('string','boolean','timestamp','number'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'string' => array(
		'label' => 'String',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder' 	=> 'value to convert',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> array('string','boolean','timestamp','number','array'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'max' => array(
		'label' => 'Max',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder' 	=> 'value to evaluate',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> 'any',
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'min' => array(
		'label' => 'Min',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder' 	=> 'value to evaluate',
				'options'		=> false,
				'selected'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> 'any',
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
	'round' => array(
		'label' => 'Round',
		'operators' => array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
		'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
		'data'		=> array(
			'args'	=> array(
				'args_num'		=> 1,
				'select_only'	=> false,
				'placeholder' 	=> 'value to evaluate',
				'options'		=> false,
				'wrapper'		=> '(%args%)',
				'args_delim'	=> ',',
				'args_type'		=> array('number'),
			),
			'reference'			=> array(
				'drop_name'		=> false,
				'exclude_for'	=> array(),
			),
		),
	),
);