<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class RecombeeReShortcodes {
	
	public static $getRecommendsLegacySet = array();
	
	public static function init(){
		
		self::$getRecommendsLegacySet = RecombeeRe::instance()->recommsDefault;
		
		$shortcodes = array(
			'RecombeeRecommendations' => __CLASS__ . '::getRecommends',
		);
		
		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}		
	}

	public static function getRecommends( $atts ){
		
		add_filter( 'shortcode_atts_RecombeeRecommendations', array(__CLASS__, 'recombee_recommends_atts_filter'), 10, 4 );

		$atts		= self::checkAttsRegister($atts);
		$user_atts	= $atts;
		$atts		= shortcode_atts( self::$getRecommendsLegacySet, $atts, 'RecombeeRecommendations' );
		
		$atts = RecombeeRe::instance()->adjust_recommendations_columns($atts, $user_atts);
		
		ob_start();
		the_widget('RecombeeReRecommsWidget', $atts);
		return ob_get_clean();
	}
	
	public static function recombee_recommends_atts_filter( $out, $pairs, $atts, $shortcode ) {
		
		$out['_illegal_params'] = array_diff_key($atts, $out);
		remove_filter( 'shortcode_atts_RecombeeRecommendations', array(__CLASS__, 'recombee_recommends_atts_filter'));
		return $out;
	}
	
	private static function checkAttsRegister( $atts ){
		
		if(isset($atts['filter'])){
			$atts['filter_reql'] = $atts['filter'];
			unset( $atts['filter'] );
		}
		if(isset($atts['booster'])){
			$atts['booster_reql'] = $atts['booster'] . '#%shortcoded%#';
			unset( $atts['booster'] );
		}
		if(isset($atts['suppress'])){
			
			$suppress_types		= array();
			$suppress_objects	= array();
			
			/* 
			*  get valid suppress types from
			*  string and replace them will null
			*/
			$atts['suppress'] = preg_replace_callback(
				'/include|exclude|off/mi',
				function ($matches) use(&$suppress_types){
					
					$suppress_types[] = $matches[0];
					return null;
				},
				$atts['suppress']
			);
			
			/* 
			*  get valid suppress objects from
			*  string and replace them will null
			*/
			$atts['suppress'] = preg_replace_callback(
				'/posts|terms/mi',
				function ($matches) use(&$suppress_objects){
					
					$suppress_objects[] = $matches[0];
					return null;
				},
				$atts['suppress']
			);
			
			/*
			*  replace evrything, besides ",",
			*  inside the string with null
			*/
			$atts['suppress'] = preg_replace('/(?!,)[\s|\D]/mi', null, $atts['suppress']);
			
			$suppress_posts = explode(',', $atts['suppress']);
			$suppress_types = array_unique($suppress_types);
			
			/* put current suppressLogic to widget atts */
			if( count($suppress_types) > 0 ){
				$atts['suppressLogic'] = strtolower($suppress_types[0]);
			}
			else{
				$atts['suppressLogic'] = self::$getRecommendsLegacySet['suppressLogic'];
			}
			
			/* put current suppressSubject to widget atts */
			if( count($suppress_objects) > 0 ){
				$atts['suppressSubject'] = strtolower($suppress_objects[0]);
			}
			else{
				$atts['suppressSubject'] = self::$getRecommendsLegacySet['suppressSubject'];
			}
			
			/* put current suppressPosts to widget atts */
			if( count($suppress_posts) > 0 ){
				$atts['suppressPosts'] = $suppress_posts;
			}
			else{
				$atts['suppressPosts'] = self::$getRecommendsLegacySet['suppressPosts'];
			}
			
			unset($atts['suppress']);
		}
		
		foreach($atts as $param_name => $param_value){
			
			$atts[$param_name] = str_replace("&lt;", "<", $atts[$param_name]);
			$atts[$param_name] = str_replace("&gt;", ">", $atts[$param_name]);
			$atts[$param_name] = str_replace("`", "\"", $atts[$param_name]);
			$atts[$param_name] = str_replace("{", "[", $atts[$param_name]);
			$atts[$param_name] = str_replace("}", "]", $atts[$param_name]);
			
			foreach(self::$getRecommendsLegacySet as $set_item => $set_value){
				
				preg_match('/^'.$set_item.'$/i', $param_name, $match, null, 0);
				
				if( count($match) === 1 && $match[0] !== $set_item ){
					$atts[$set_item] = $param_value;
					unset( $atts[$param_name] );
					break;
				}
			}
		}
		
		return $atts;
	}
}