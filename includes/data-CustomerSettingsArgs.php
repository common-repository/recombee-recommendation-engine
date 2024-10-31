<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/** 
* available values for 'property_name->queryBuilder->dbData->ReQL_funcs' see RecombeeReRecommsWidget->extract_functions
**/
$qb_opt_group_meta_position = 2;

$customer_prop = array(
	'wpStatus'			=> array(
		'view_name'		=> __('User Registration Status *', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'string',
		'canBeOfType'	=> array('string'),
		'innerType'		=> 'wp_meta',
		'dataGetterClb'	=> 'get_userdata',
		'dataInitGuest' => 'init_sync_guest',
		'typeConvClb'	=> 'strval',
		'builtin'		=> true,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('User Registration Status', 'recombee-recommendation-engine'),
			'type'			=> 'string',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_user'),
			'rhs_funcs'		=> array('now','context_user'),
			'description'	=> false,
		),
	),
	'wcRole'			=> array(
		'view_name'		=> __('Shop Customer Role', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'string',
		'canBeOfType'	=> array('string'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_role',
		'dataInitGuest' => '',
		'typeConvClb'	=> 'strval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Shop Customer Role', 'recombee-recommendation-engine'),
			'type'			=> 'string',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_user'),
			'rhs_funcs'		=> array('now','context_user'),
			'description'	=> false,
		),
	),
	'wcCountry'			=> array(
		'view_name'		=> __('Customer Country', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'string',
		'canBeOfType'	=> array('string'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_billing_country',
		'dataInitGuest' => '',
		'typeConvClb'	=> 'strval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Customer Country', 'recombee-recommendation-engine'),
			'type'			=> 'string',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_user'),
			'rhs_funcs'		=> array('now','context_user'),
			'description'	=> false,
		),
	),
	'wcCity'			=> array(
		'view_name'		=> __('Customer City', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'string',
		'canBeOfType'	=> array('string'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_billing_city',
		'dataInitGuest' => '',
		'typeConvClb'	=> 'strval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Customer City', 'recombee-recommendation-engine'),
			'type'			=> 'string',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_user'),
			'rhs_funcs'		=> array('now','context_user'),
			'description'	=> false,
		),
	),
	'wcZip'				=> array(
		'view_name'		=> __('Customer ZIP', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'string',
		'canBeOfType'	=> array('string'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_billing_postcode',
		'dataInitGuest' => '',
		'typeConvClb'	=> 'strval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Customer ZIP', 'recombee-recommendation-engine'),
			'type'			=> 'string',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_user'),
			'rhs_funcs'		=> array('now','context_user'),
			'description'	=> false,
		),
	),
);