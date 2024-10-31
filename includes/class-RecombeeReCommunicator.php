<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests as Reqs;
use Recombee\RecommApi\Exceptions as Ex;

/**
 * Recombee API Comunicator.
 *
 * @class RecombeeComunicator
 */
class RecombeeReCommunicator {
	
	protected static $_instance		= null;
	private static $userID			= null;
	private static $RecombeeClient	= null;
	private $BatchLog				= null;
	private $blogSetting			= null;

	private function __construct($db_name, $secret_key){
		
		$recombee = RecombeeRe::instance();
		
		self::$RecombeeClient = new Client($db_name, $secret_key, 'https', array('serviceName' => 'woocommerce'));
		$this->blogSetting	= $recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
	}
	
	public function destroy(){
		self::$_instance = null;
	}
	
	public function reqsListItems($param){
		
		$default_param = array(
			'batch'					=> false,
			'count'					=> null,
			'offset'				=> null,
			'filter'				=> null,
			'returnProperties'		=> false,
			'includedProperties'	=> array(),
			'operation_name'		=> 'Missed in arguments',
			'force_log'				=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		$args = array(
				'count'				=> $param['count'],
				'filter'			=> $param['filter'],
				'offset'			=> $param['offset'],
			);
		if($param['returnProperties'] && count($param['includedProperties']) > 0){
			
			$args['returnProperties']	= $param['returnProperties'];
			$args['includedProperties'] = $param['includedProperties'];
		}
		
		if($param['batch']){
			
			$requests = new Reqs\ListItems( $args );
			return array($requests);
		}
		
		try{
			$Result = self::$RecombeeClient->send(new Reqs\ListItems( $args ));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsListItemProperties($param){
		
		$default_param = array(
			'batch'					=> false,
			'operation_name'		=> 'Missed in arguments',
			'force_log'				=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			
			$requests = new Reqs\ListItemProperties();
			return array($requests);
		}
		
		try{
			$Result = self::$RecombeeClient->send(new Reqs\ListItemProperties());
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsListUsers($param){
		
		$default_param = array(
			'batch'					=> false,
			'count'					=> null,
			'offset'				=> null,
			'filter'				=> null,
			'returnProperties'		=> false,
			'includedProperties'	=> array(),
			'operation_name'		=> 'Missed in arguments',
			'force_log'				=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		$args = array(
				'count'				=> $param['count'],
				'filter'			=> $param['filter'],
				'offset'			=> $param['offset'],
			);
		if($param['returnProperties'] && count($param['includedProperties']) > 0){
			
			$args['returnProperties']	= $param['returnProperties'];
			$args['includedProperties'] = $param['includedProperties'];
		}
		
		if($param['batch']){
			
			$requests = new Reqs\ListUsers( $args );
			return array($requests);
		}
		
		try{
			$Result = self::$RecombeeClient->send(new Reqs\ListUsers( $args ));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsListUserProperties($param){
		
		$default_param = array(
			'batch'					=> false,
			'operation_name'		=> 'Missed in arguments',
			'force_log'				=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			
			$requests = new Reqs\ListUserProperties();
			return array($requests);
		}
		
		try{
			$Result = self::$RecombeeClient->send(new Reqs\ListUserProperties());
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsAddUser($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){

				$r = new Reqs\AddUser(
					$property['userId']
				);
				array_push($requests, $r);
			}
			return $requests;
		}
		
		try{
			$Result = self::$RecombeeClient->send( new Reqs\AddUser(
				$properties['userId']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsAddItemProperty($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){

				$r = new Reqs\AddItemProperty(
					$property['name'],
					$property['type']
				);
				array_push($requests, $r);
			}
			return $requests;
		}
		
		try{
			$Result = self::$RecombeeClient->send( new Reqs\AddItemProperty(
				$properties['name'],
				$properties['type']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsAddUserProperty($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){

				$r = new Reqs\AddUserProperty(
					$property['name'],
					$property['type']
				);
				array_push($requests, $r);
			}
			return $requests;
		}
		
		try{
			$Result = self::$RecombeeClient->send( new Reqs\AddUserProperty(
				$properties['name'],
				$properties['type']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsDeleteItemProperty($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){

				$r = new Reqs\DeleteItemProperty(
					$property['name']
				);
				array_push($requests, $r);
			}
			return $requests;
		}
		
		try{
			$Result = self::$RecombeeClient->send( new Reqs\DeleteItemProperty(
				$properties['name']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsDeleteUserProperty($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){

				$r = new Reqs\DeleteUserProperty(
					$property['name']
				);
				array_push($requests, $r);
			}
			return $requests;
		}
		
		try{
			$Result = self::$RecombeeClient->send( new Reqs\DeleteUserProperty(
				$properties['name']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsSetItemValues($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'cascadeCreate' => false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){

				$r = new Reqs\SetItemValues(
						$property['id'],
						$property['properties'],
						array('cascadeCreate' => $param['cascadeCreate'])
					);
				array_push($requests, $r);
			}
			return $requests;
		}

		try{
			$Result = self::$RecombeeClient->send( new Reqs\SetItemValues(
				$properties['id'],
				$properties['properties'],
				array('cascadeCreate' => $param['cascadeCreate'])
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsSetUserValues($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'cascadeCreate' => false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){

				$r = new Reqs\SetUserValues(
						$property['id'],
						$property['properties'],
						array('cascadeCreate' => $param['cascadeCreate'])
					);
				array_push($requests, $r);
			}
			return $requests;
		}
		
		try{
			$Result = self::$RecombeeClient->send( new Reqs\SetUserValues(
				$properties['id'],
				$properties['properties'],
				array('cascadeCreate' => $param['cascadeCreate'])
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsMergeUsers($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'cascadeCreate' => false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){

				$r = new Reqs\MergeUsers(
						$property['id'],
						$property['RAUID'],
						array('cascadeCreate' => $param['cascadeCreate'])
					);
				array_push($requests, $r);
			}
			return $requests;
		}
		
		try{
			$Result = self::$RecombeeClient->send( new Reqs\MergeUsers(
				$properties['id'],
				$properties['RAUID'],
				array('cascadeCreate' => $param['cascadeCreate'])
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			if(isset($e->status_code) && $e->status_code === 404){
				$formalizedResult .= ' This means that this user did not do any interactions, so there are nothing to merge.';
			}
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsAddPurchase($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'cascadeCreate' => false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){
				foreach($property as $userPurchases){
					
				$userPurchases['properties']['cascadeCreate'] = $param['cascadeCreate'];
				$r = new Reqs\AddPurchase(
						$userPurchases['id'],
						$userPurchases['product'],
						$userPurchases['properties']
					);
				array_push($requests, $r);
				}
			}
			return $requests;
		}
		
		try{
			$properties['properties']['cascadeCreate'] = $param['cascadeCreate'];
			$Result = self::$RecombeeClient->send( new Reqs\AddPurchase(
				$properties['id'],
				$properties['product'],
				$properties['properties']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsAddDetailView($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'cascadeCreate' => false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){
				foreach($property as $userDetailView){
					
				$userDetailView['properties']['cascadeCreate'] = $param['cascadeCreate'];
				$r = new Reqs\AddDetailView(
						$userDetailView['id'],
						$userDetailView['product'],
						$userDetailView['properties']
					);
				array_push($requests, $r);
				}
			}
			return $requests;
		}
		
		try{
			$properties['properties']['cascadeCreate'] = $param['cascadeCreate'];
			$Result = self::$RecombeeClient->send( new Reqs\AddDetailView(
				$properties['id'],
				$properties['product'],
				$properties['properties']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsAddRating($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'cascadeCreate' => false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){
				foreach($property as $userRatings){
					
				$userRatings['properties']['cascadeCreate'] = $param['cascadeCreate'];
				$r = new Reqs\AddRating(
						$userRatings['id'],
						$userRatings['product'],
						$userRatings['rating'],
						$userRatings['properties']
					);
				array_push($requests, $r);
				}
			}
			return $requests;
		}
		
		try{
			$properties['properties']['cascadeCreate'] = $param['cascadeCreate'];
			$Result = self::$RecombeeClient->send( new Reqs\AddRating(
				$properties['id'],
				$properties['product'],
				$properties['rating'],
				$properties['properties']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsAddCartAddition($properties, $param){
		
		$default_param = array(
			'batch'			=> false,
			'cascadeCreate' => false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			$requests = array();

			foreach($properties as $property){
				foreach($property as $userCartAddition){
					
				$userCartAddition['properties']['cascadeCreate'] = $param['cascadeCreate'];
				$r = new Reqs\AddCartAddition(
						$userCartAddition['id'],
						$userCartAddition['product'],
						$userCartAddition['properties']
					);
				array_push($requests, $r);
				}
			}
			return $requests;
		}
		
		try{
			$properties['properties']['cascadeCreate'] = $param['cascadeCreate'];
			$Result = self::$RecombeeClient->send( new Reqs\AddCartAddition(
				$properties['id'],
				$properties['product'],
				$properties['properties']
			));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsRecommendItemsToUser($properties, $param){
		
		$default_param = array(
			'batch'				=> false,
			'operation_name'	=> 'Missed in arguments',
			'force_log'			=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		$args = array(
			'cascadeCreate'	=> $properties['cascadeCreate'],
			'diversity'		=> (double)$properties['diversity'],
			'minRelevance'	=> $properties['minRelevance'],
		);
		
		( !empty($properties['scenario']) )		? $args['scenario']		= $properties['scenario'] 				: '';
		( !empty($properties['filter']) )		? $args['filter']		= $properties['filter']					: '';
		( !empty($properties['booster']) )		? $args['booster']		= $properties['booster']				: '';
		( !empty($properties['rotationRate']) )	? $args['rotationRate']	= (double)$properties['rotationRate']	: '';
		( !empty($properties['rotationTime']) )	? $args['rotationTime']	= (double)$properties['rotationTime']	: '';
		
		if($properties['returnProperties'] && count($properties['includedProperties']) > 0){
			
			$args['returnProperties']	= $properties['returnProperties'];
			$args['includedProperties'] = $properties['includedProperties'];
		}
		
		$user_id	= ( is_user_logged_in() ) ? get_current_user_id() : RecombeeRe::instance()->get_RAUID();
		$count		= (int)$properties['count'];
		
		if($param['batch']){

			$request = new Reqs\RecommendItemsToUser($user_id, $count, $args);
			return array($request);
		}
		
		try{

			$Result = self::$RecombeeClient->send( new Reqs\RecommendItemsToUser($user_id, $count, $args));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsRecommendItemsToItem($properties, $param){
		
		$default_param = array(
			'item_id'				=> false,
			'batch'					=> false,
			'operation_name'		=> 'Missed in arguments',
			'force_log'				=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		$args = array(
			'cascadeCreate'	=> $properties['cascadeCreate'],
			'diversity'		=> (double)$properties['diversity'],
			'minRelevance'	=> $properties['minRelevance'],
		);
		
		( !empty($properties['scenario']) )		? $args['scenario']		= $properties['scenario'] 				: '';
		( !empty($properties['userImpact']) )	? $args['userImpact']	= (double)$properties['userImpact'] 	: '';
		( !empty($properties['filter']) )		? $args['filter']		= $properties['filter']					: '';
		( !empty($properties['booster']) )		? $args['booster']		= $properties['booster']				: '';
		( !empty($properties['rotationRate']) )	? $args['rotationRate']	= (double)$properties['rotationRate']	: '';
		( !empty($properties['rotationTime']) )	? $args['rotationTime']	= (double)$properties['rotationTime']	: '';
		
		if($properties['returnProperties'] && count($properties['includedProperties']) > 0){
			
			$args['returnProperties']	= $properties['returnProperties'];
			$args['includedProperties'] = $properties['includedProperties'];
		}
		
		$item_id	= $param['item_id'];
		$user_id	= ( is_user_logged_in() ) ? get_current_user_id() : RecombeeRe::instance()->get_RAUID();
		$count		= (int)$properties['count'];
		
		if($param['batch']){

			$request = new Reqs\RecommendItemsToItem($item_id, $user_id, $count, $args);
			return array($request);
		}
		
		try{

			$Result = self::$RecombeeClient->send( new Reqs\RecommendItemsToItem($item_id, $user_id, $count, $args));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			if(isset($e->status_code) && $e->status_code === 401){
				$formalizedResult .= '. Or probably ItemId did not set.';
			}
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsRecommendUsersToUser($properties, $param){
		
		$default_param = array(
			'batch'				=> false,
			'operation_name'	=> 'Missed in arguments',
			'force_log'			=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		$args = array(
			'cascadeCreate'	=> $properties['cascadeCreate'],
			'diversity'		=> (double)$properties['diversity'],
			'minRelevance'	=> $properties['minRelevance'],
		);
		
		( !empty($properties['scenario']) )		? $args['scenario']		= $properties['scenario'] 				: '';
		( !empty($properties['filter']) )		? $args['filter']		= $properties['filter']					: '';
		( !empty($properties['booster']) )		? $args['booster']		= $properties['booster']				: '';
		( !empty($properties['rotationRate']) )	? $args['rotationRate']	= (double)$properties['rotationRate']	: '';
		( !empty($properties['rotationTime']) )	? $args['rotationTime']	= (double)$properties['rotationTime']	: '';

		if($properties['returnProperties'] && count($properties['includedProperties']) > 0){
			
			$args['returnProperties']	= $properties['returnProperties'];
			$args['includedProperties'] = $properties['includedProperties'];
		}
		
		$user_id	= ( is_user_logged_in() ) ? get_current_user_id() : RecombeeRe::instance()->get_RAUID();
		$count		= (int)$properties['count'];
		
		if($param['batch']){

			$request = new Reqs\RecommendUsersToUser($user_id, $count, $args);
			return array($request);
		}
		
		try{

			$Result = self::$RecombeeClient->send( new Reqs\RecommendUsersToUser($user_id, $count, $args));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsRecommendUsersToItem($properties, $param){
		
		$default_param = array(
			'item_id'				=> false,
			'batch'					=> false,
			'operation_name'		=> 'Missed in arguments',
			'force_log'				=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		$args = array(
			'cascadeCreate'	=> $properties['cascadeCreate'],
			'diversity'		=> (double)$properties['diversity'],
		);
		
		( !empty($properties['scenario']) )		? $args['scenario']		= $properties['scenario'] 	: '';
		( !empty($properties['filter']) )		? $args['filter']		= $properties['filter']		: '';
		( !empty($properties['booster']) )		? $args['booster']		= $properties['booster']	: '';
		
		if($properties['returnProperties'] && count($properties['includedProperties']) > 0){
			
			$args['returnProperties']	= $properties['returnProperties'];
			$args['includedProperties'] = $properties['includedProperties'];
		}
		
		$item_id	= $param['item_id'];
		$count		= (int)$properties['count'];
		
		if($param['batch']){

			$request = new Reqs\RecommendUsersToItem($item_id, $count, $args);
			return array($request);
		}
		
		try{

			$Result = self::$RecombeeClient->send( new Reqs\RecommendUsersToItem($item_id, $count, $args));
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			if(isset($e->status_code) && $e->status_code === 401){
				$formalizedResult .= '. Or probably ItemId did not set.';
			}
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsResetDataBase($param){
		
		$default_param = array(
			'batch'			=> false,
			'operation_name'=> 'Missed in arguments',
			'force_log'		=> false,
		);
		$param = wp_parse_args( $param, $default_param );
		
		if($param['batch']){
			
			$requests = array( new Reqs\ResetDatabase());
			return $requests;
		}
		
		try{
			$Result = self::$RecombeeClient->send(new Reqs\ResetDatabase());
			return array('success' => $Result);
		}
		catch(Exception $e){
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $param['operation_name'], $formalizedResult, $param['force_log'] );
			
			$return = array(
				'errors' => $formalizedResult
			);
			
			if( method_exists($e, 'getType') ){
				$return['exception_type'] = $e->getType();
			}
			return $return;
		}
	}
	
	public function reqsExecuteBatch($operation_name, $requests, $force_log = false, $return_clear_response = false){
		
		$BatchStack = array();
		( $this->blogSetting['distinct_recomms'] == 1 ) ? $distinctRecomms = true : $distinctRecomms = false;
		
		foreach($requests as $request){
			
			$request['param']['batch'] = true;
			
			if( isset($request['properties']) ){
				
				$r = call_user_func( array($this, $request['method']), $request['properties'], $request['param'] );
			}
			else{
				$r = call_user_func( array($this, $request['method']), $request['param'] );
			}
			$BatchStack = array_merge($BatchStack, $r);	
		}

		try{
			$Result = self::$RecombeeClient->send(new Reqs\Batch($BatchStack, array('distinctRecomms' => $distinctRecomms) ));
			
			if($return_clear_response){
				$this->BatchLog['reqsExecuteBatch'] = $Result;
			}
			else{
				$this->BatchLog['reqsExecuteBatch']['success'][] = $Result;
			}			
			
			foreach($Result as $index => $data){
				if((int)$data['code'] !== 200 && (int)$data['code'] !== 201){
					$this->BatchLog['reqsExecuteBatch']['errors'][] = $data;
					if(is_array($data['json'])){
						$e = array(
							'code'		=> $data['code'],
							'message'	=> $data['json']['message'],
							'error'		=> $data['json']['error'],
						);
					}
					else{
						$e = array(
							'code'		=> $data['code'],
							'message'	=> $data['json'],
						);
					}
					$formalizedResult = $this->formalizeException($e);
					preg_match("/[^\\\]*$/", get_class($BatchStack[$index]), $matches, null, 0);
					$this->logRequestErr( 'Batch: ' . $matches[0], $formalizedResult, $force_log );
					
					if($return_clear_response){
						$this->BatchLog['reqsExecuteBatch'] = $Result;
					}
				}
			}
		}
		catch(Exception $e){
			
			$formalizedResult = $this->formalizeException($e);
			$this->logRequestErr( $operation_name, $formalizedResult, $force_log );
			
			if($return_clear_response){
				$this->BatchLog['reqsExecuteBatch'] = $e;
			}
			else{
				$this->BatchLog['reqsExecuteBatch']['errors'][] = $formalizedResult;
			}
		}

		return $this->BatchLog;
	}
	
	public function formalizeException($exception){
		
		$text = '';
		
		if($exception instanceof Exception){
			
			$message = $exception->getMessage();
			$is_json = json_decode($message);
			
			if(!is_null($is_json)){
				$message = $is_json;
				foreach($message as $key => $info){
					$text .= $key . ': ' . $info . ' -> ';
				}
			}
			else{
				$text .= $message;
			}
		}
		else if(is_array($exception)){
			foreach($exception as $code => $message){
				$text .= $code . ': ' . $message . ' -> ';
			}
		}
		return trim($text, '-> ');
	}
	
	public function logRequestErr($operation_name, $string, $force = false){
		
		if( $this->blogSetting['log_requests_err'] == 1 || $force === true ){
			
			@mkdir( RRE_ABSPATH . '/log');
			$text = date('d.m.Y H:i:s', time()) . ' -> ' . $operation_name . ' -> ' . $string;
			error_log($text . "\n", 3, RRE_ABSPATH . '/log/requests-errors.log');
		}
	}
		
	public static function instance($db_name, $secret_key) {

		if ( null == self::$_instance ){
			
			self::$_instance = new self($db_name, $secret_key);
		}

		return self::$_instance;
	}
}