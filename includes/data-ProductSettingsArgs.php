<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/** 
* available values for 'property_name->queryBuilder->lhs_funcs' see RecombeeReRecommsWidget->extract_functions
**/
$product_prop = array();
$qb_opt_group_meta_position = 2;
$qb_opt_group_taxonomies_position = 3;
$qb_opt_group_attributes_position = 4;
$qb_opt_group_predefined_position = 5;

$static_prop = array(
	'wcShopId'			=> array(
		'view_name'		=> __('Shop ID *', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'int',
		'canBeOfType'	=> array('integer'),
		'innerType'		=> 'wp_meta',
		'dataGetterClb'	=> 'get_current_blog_id',
		'typeConvClb'	=> 'intval',
		'builtin'		=> true,
		'optgroup'		=> __('Required', 'recombee-recommendation-engine'),
		'queryBuilder'	=> false,
	),
	'wcSku'				=> array(
		'view_name'		=> __('Product SKU', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'string',
		'canBeOfType'	=> array('string', 'empty'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_sku',
		'typeConvClb'	=> 'strval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product SKU', 'recombee-recommendation-engine'),
			'type'			=> 'string',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_item'),
			'rhs_funcs'		=> array('now','context_item'),
			'description'	=> false,
		),
	),
	'wcRegularPrice'	=> array(
		'view_name'		=> __('Product Regular Price', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'double',
		'canBeOfType'	=> array('double', 'string', 'empty'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_price',
		'typeConvClb'	=> 'doubleval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product Regular Price', 'recombee-recommendation-engine'),
			'type'			=> 'double',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'validation'	=> array(
				'min'		=> 0,
				'step'		=> 0.1,
			),
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_item'),
			'rhs_funcs'		=> array('now','context_item'),
			'description'	=> false,
		),
	),
	'wcIsInStock'		=> array(
		'view_name'		=> __('Product Is In Stock', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'boolean',
		'canBeOfType'	=> array('boolean'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'is_in_stock',
		'typeConvClb'	=> 'boolval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product Is In Stock', 'recombee-recommendation-engine'),
			'type'			=> 'boolean',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'values'		=> array(
				array('value'	=>'true',	'label'=>'Yes',	'optgroup'=> __('Native values', 'recombee-recommendation-engine')),
				array('value'	=>'false',	'label'=>'No',	'optgroup'=> __('Native values', 'recombee-recommendation-engine')),
			),
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_item'),
			'rhs_funcs'		=> array('now','context_item'),
			'description'	=> false,
		),
	),
	'wcStockQuantity'	=> array(
		'view_name'		=> __('Product Stock Quantity', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'double',
		'canBeOfType'	=> array('double', 'string', 'NULL', 'integer'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_stock_quantity',
		'typeConvClb'	=> 'doubleval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product Stock Quantity', 'recombee-recommendation-engine'),
			'type'			=> 'double',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'validation'	=> array(
				'min'		=> 0,
				'step'		=> 0.1,
			),
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_item'),
			'rhs_funcs'		=> array('now','context_item'),
			'description'	=> false,
		),
	),
	'wcManagingStock'	=> array(
		'view_name'		=> __('Product Managing Stock', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'boolean',
		'canBeOfType'	=> array('boolean','string'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'managing_stock',
		'typeConvClb'	=> 'boolval',
		'builtin'		=> false,
		'optgroup'		=> __('Meta', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product Managing Stock', 'recombee-recommendation-engine'),
			'type'			=> 'boolean',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'values'		=> array(
				array('value'	=>'true',	'label'=>'Yes',	'optgroup'=> __('Native values', 'recombee-recommendation-engine')),
				array('value'	=>'false',	'label'=>'No',	'optgroup'=> __('Native values', 'recombee-recommendation-engine')),
			),
			'optgroup'		=> sprintf( __('%s) Meta', 'recombee-recommendation-engine'), $qb_opt_group_meta_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_item'),
			'rhs_funcs'		=> array('now','context_item'),
			'description'	=> false,
		),
	),
	'wpStatus' => array(
		'view_name'		=> __('Product Status *', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'string',
		'canBeOfType'	=> array('string'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_status',
		'typeConvClb'	=> 'strval',
		'builtin'		=> true,
		'optgroup'		=> __('Required', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product Status', 'recombee-recommendation-engine'),
			'type'			=> 'string',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'values'		=> get_post_statuses(),
			'optgroup'		=> sprintf( __('%s) Predefined', 'recombee-recommendation-engine'), $qb_opt_group_predefined_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_item'),
			'rhs_funcs'		=> array('now','context_item'),
			'description'	=> false,
		),
	),
	'wcProductType' => array(
		'view_name'		=> __('Product Type *', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'string',
		'canBeOfType'	=> array('string'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_type',
		'typeConvClb'	=> 'strval',
		'builtin'		=> true,
		'optgroup'		=> __('Required', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product Type', 'recombee-recommendation-engine'),
			'type'			=> 'string',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'values'		=> wc_get_product_types(),
			'optgroup'		=> sprintf( __('%s) Predefined', 'recombee-recommendation-engine'), $qb_opt_group_predefined_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_item'),
			'rhs_funcs'		=> array('now','context_item'),
			'description'	=> false,
		),
	),
	'wcProductIsVisible' => array(
		'view_name'		=> __('Product is Visible *', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'boolean',
		'canBeOfType'	=> array('boolean','string'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'is_visible',
		'typeConvClb'	=> 'boolval',
		'builtin'		=> true,
		'optgroup'		=> __('Required', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product is Visible', 'recombee-recommendation-engine'),
			'type'			=> 'boolean',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'values'		=> array(
				array('value'	=>'true',	'label'=>'Yes',	'optgroup'=> __('Native values', 'recombee-recommendation-engine')),
				array('value'	=>'false',	'label'=>'No',	'optgroup'=> __('Native values', 'recombee-recommendation-engine')),
			),
			'optgroup'		=> sprintf( __('%s) Predefined', 'recombee-recommendation-engine'), $qb_opt_group_predefined_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('now','context_item'),
			'rhs_funcs'		=> array('now','context_item'),
			'description'	=> false,
		),
	),
	/* Change name - restricted - it uses to find parents */
 	'wcProductParentID' => array(
		'view_name'		=> __('Product Parent *', 'recombee-recommendation-engine'),
		'recombeeType'	=> 'int',
		'canBeOfType'	=> array('integer'),
		'innerType'		=> 'wc_meta',
		'dataGetterClb'	=> 'get_parent_id',
		'typeConvClb'	=> 'intval',
		'builtin'		=> true,
		'optgroup'		=> __('Required', 'recombee-recommendation-engine'),
		'queryBuilder'	=> array(
			'label'			=> __('Product Parent', 'recombee-recommendation-engine'),
			'type'			=> 'integer',
			'input'			=> 'select',
			'multiple'		=> true,
			'unique'		=> false,
			'validation'	=> array(
				'min'		=> 1,
				'step'		=> 1,
			),
			'optgroup'		=> sprintf( __('%s) Predefined', 'recombee-recommendation-engine'), $qb_opt_group_predefined_position),
			'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
			'lhs_funcs'		=> array('expression','context_item'),
			'rhs_funcs'		=> array('expression','context_item'),
			'description'	=> false,
		),
	),
);

$product_tax = get_taxonomies( array(
	'object_type'	=> array('product'),
	'public'		=> true,
	'_builtin'		=> false
), 'objects', 'and' );

if(count($product_tax) > 0 ){
	foreach($product_tax as $slug => $tax_data){
		if(isset($product_prop[ $slug ])){
			self::$doubled_prop_keys[] = $slug;
		}
		else{
			$product_prop[ $slug ] = array(
				'view_name'		=> sprintf( __('Product %s', 'recombee-recommendation-engine'), $tax_data->labels->menu_name),
				'recombeeType'	=> 'set',
				'canBeOfType'	=> array('array'),
				'innerType'		=> 'taxonomy',
				'dataGetterClb'	=> 'wp_get_post_terms',
				'typeConvClb'	=> 'json_encode',
				'taxonomy'      => array( $slug ),
				'args'			=> array(
					'orderby'       => 'id',
					'order'         => 'ASC',
					'hide_empty'    => true,
					'fields'        => 'ids',
					'hierarchical'  => false,
					'get'           => 'all',
					'update_term_meta_cache' => false,
				),
				'builtin'	=> false,
				'optgroup'	=> __('Taxonomies', 'recombee-recommendation-engine'),
				'queryBuilder'	=> array(
					'label'			=> sprintf( __('Product %s', 'recombee-recommendation-engine'), $tax_data->labels->singular_name),
					'type'			=> 'string',
					'input'			=> 'select',
					'multiple'		=> true,
					'unique'		=> false,
					'values'		=> array(
						'clb'		=> 'get_terms',
						'args'		=> array('fields'=>'id=>name'),
					),
					'optgroup'		=> sprintf( __('%s) Taxonomies', 'recombee-recommendation-engine'), $qb_opt_group_taxonomies_position),
					'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
					'lhs_funcs'		=> array('now','context_item'),
					'rhs_funcs'		=> array('now','context_item'),
					'description'	=> false,
				),
			);
		}
	}
}

if( function_exists('wc_get_attribute_taxonomies') ){
	if(isset($product_prop['product_att'])){
		self::$doubled_prop_keys[] = 'product_att';
	}
	else{
		$product_prop['product_att'] = array(
			'view_name'		=> __('Product Attributes', 'recombee-recommendation-engine'),
			'recombeeType'	=> 'set',
			'canBeOfType'	=> array('array'),
			'innerType'		=> 'taxonomy',
			'dataGetterClb'	=> 'wp_get_post_terms',
			'typeConvClb'	=> 'json_encode',
			'taxonomy'		=> wc_get_attribute_taxonomy_names(),
			'args'			=> array(
				'orderby'       => 'id',
				'order'         => 'ASC',
				'hide_empty'    => true,
				'fields'        => 'ids',
				'hierarchical'  => false,
				'get'           => 'all',
				'update_term_meta_cache' => false,
			),
			'builtin'	=> false,
			'optgroup'	=> __('Taxonomies', 'recombee-recommendation-engine'),
			'queryBuilder'	=> array(
				'label'			=> __('Product Attribute', 'recombee-recommendation-engine'),
				'type'			=> 'string',
				'input'			=> 'select',
				'multiple'		=> true,
				'unique'		=> false,
				'values'		=> array(
					'clb'		=> 'get_terms',
					'args'		=> array('fields'=>'id=>name'),
				),
				'optgroup'		=> sprintf( __('%s) Taxonomies', 'recombee-recommendation-engine'), $qb_opt_group_taxonomies_position),
				'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
				'lhs_funcs'		=> array('now','context_item'),
				'rhs_funcs'		=> array('now','context_item'),
				'description'	=> false,
			),
		);
	}
	
	$att_taxes = wc_get_attribute_taxonomies();
	if( count($att_taxes) > 0 ){
		foreach($att_taxes as $att_tax){
				
			$taxonomy = wc_get_attribute($att_tax->attribute_id);
			$prop_name = 'att_' . '::ID' . $att_tax->attribute_id . '::' . ucwords($att_tax->attribute_name);
			
			if(!$taxonomy){
				continue;
			}
			
			if(isset($product_prop[ $prop_name ])){
				self::$doubled_prop_keys[] = $prop_name;
			}
			else{
				$product_prop[ $prop_name ] = array(
					'view_name'		=> sprintf( __('Product %s', 'recombee-recommendation-engine'), ucfirst($att_tax->attribute_label)),
					'recombeeType'	=> 'set',
					'canBeOfType'	=> array('array'),
					'innerType'		=> 'attribute',
					'dataGetterClb'	=> 'wp_get_post_terms',
					'typeConvClb'	=> 'json_encode',
					'taxonomy'		=> array( $taxonomy->slug ),
					'args'			=> array(
						'orderby'       => 'id',
						'order'         => 'ASC',
						'hide_empty'    => true,
						'fields'        => 'ids',
						'hierarchical'  => false,
						'get'           => 'all',
						'update_term_meta_cache' => false,
					),
					'builtin'	=> false,
					'optgroup'	=> __('Attributes', 'recombee-recommendation-engine'),
					'queryBuilder'	=> array(
						'label'			=> sprintf( __('Product %s', 'recombee-recommendation-engine'), ucfirst($att_tax->attribute_label)),
						'type'			=> 'string',
						'input'			=> 'select',
						'multiple'		=> true,
						'unique'		=> false,
						'values'		=> array(
							'clb'		=> 'get_terms',
							'args'		=> array('fields'=>'id=>name'),
						),
						'optgroup'		=> sprintf( __('%s) Attributes', 'recombee-recommendation-engine'), $qb_opt_group_attributes_position),
						'operators'		=> array('equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','is_null','is_not_null'),
						'lhs_funcs'		=> array('now','context_item'),
						'rhs_funcs'		=> array('now','context_item'),
						'description'	=> false,
					),
				);
			}
		}
	}
}