<?php 
/**
* modelAddon to extend old fashioned models $filters property.
* example:
* class model extends BASE_model{
* 	static $filters=array(
* 		'fieldName'=>array(
*				'filter1',
*				array('filter2'[,$params][,'error message'])
*			)
*			'__msg__'=>array(
*				'fieldName'=>'default error message for the field'
* 	);
* }
* 
* 
* @class filtersModelAddon
* @since 2011-04
* @author Jonathan Gotti <jgotti at jgotti dot net>
* @license lgpl / mmit
* @changelog
*            - 2011-05-10 - total rewrite of filtersModelAddons seems more logical this way
*/
class filtersModelAddon extends modelAddon{
	
	static private $internal;
	static public $defaultSalt = 'salt';
	static private $registered = array(
		'uppercase'=>array('strtoupper')
		,'lowercase'=>array('strtolower')
	);
	
	function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		if( isset(self::$internal[$this->modelName])){
			$this->overloadedModelMethods=self::$internal[$this->modelName];
		}
	}
	protected function _initModelType(){
		self::_initFilters();
		#- get the filters property and override all fields concerned
		$filters = array_keys(abstractModel::_getModelStaticProp($this->modelName, 'filters'));
		foreach($filters as $f){
			if( $f === '__msg__')
				continue;
			self::$internal[$this->modelName][]='filter'.(is_array($f)?$f[0]:$f);
		}
	}
	static private function _initFilters(){
		static $initialized;
		if( $initialized )
			return;
		$methods = get_class_methods(__class__);
		foreach($methods as $f){
			if(substr($f,0,1)==='_' || method_exists('modelAddon', $f) || $f==='register'){
				continue;
			}
			self::$registered[$f] = array(array(__class__,$f),null);
		}
		$initialized=true;
	}
	/**
	* register filter at runtime.
	* @param string $filterName
	* @param callback $filterCb callable callback
	* @param string $filterMsg to append to the filterMsg stack on failure
	*/
	static public function register($filterName,$filterCb,$filterMsg=null){
		self::_initFilters();
		self::$registered[$filterName] = array(
			strpos($filterCb,'::')?explode('::',filterCb):$filterCb,
			$filterMsg
		);
	}
	
	public function __call($m,$a){
		if(! preg_match('!^filter(.*)$!',$m,$matches)){
			return;
		}
		$key = abstractModel::_cleanKey($this->modelName,'hasOne|hasMany|datas',$matches[1]);
		#- get filters from model
		$mfilters = (array) abstractModel::_getModelStaticProp($this->modelName, 'filters');
		$_a = $a = array_shift($a);
		if(! isset($mfilters[$key])){
			return $a;
		}
		$filters = $mfilters[$key];
		foreach( $filters as $filter){
			if( is_string($filter) ){
				$fName = $filter; $args = array(); $msg=null;
			}else{
				$fName = isset($filter[0])?$filter[0]:null;
				$args = isset($filter[1])?(array) $filter[1]:array();
				$msg = isset($filter[2])?$filter[2]:array();
			}
			$func = isset(self::$registered[$fName])?self::$registered[$fName][0]:(strpos($fName,'::')?explode('::',$fName,2):$fName);
			if(! is_callable($func))
				throw new BadFunctionCallException(__class__." unknown filter '$f'");
			array_unshift($args,$a);
			$a = call_user_func_array($func,$args);
			if( false===$a){
				if(! $msg ){
					if( isset($mfilters['__msg__'][$key]) ){
						$msg = $mfilters['__msg__'][$key];
					}else{
						$msg = isset(self::$registered[$fName],self::$registered[$fName][1]) ?
							self::$registered[$fName][1]
							: '%4$s: invalid %1$s::%2$s value %3$s'
						;
					}
				}
				$this->modelInstance->appendFilterMsg($msg,array($this->modelName,$key,$_a,$fName));
				return false;
			}
		}
		return $a;
	}
	
	//** common filters **//
	static public function minlength($v,$min){
		return strlen($v)<$min?false:$v;
	}
	static public function maxlength($v,$max){
		return strlen($v)>$max?false:$v;
	}
	static public function match($v,$exp){
		return preg_match($exp,$v)?$v:false;
	}
	static public function dontmatch($v,$exp){
		return preg_match($exp,$v)?false:$v;
	}
	static public function md5($v,$salt=null){
		return hash_hmac('md5',$v,$salt===null?self::$defaultSalt:$salt);
	}
	static function mail($v){
		return easymail::check_address($v)?$v:false;
	}
	static function ucfirst($v){
		return strtolower(substr($v,0,1)).substr($v,1);
	}
}