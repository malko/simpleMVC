<?php 
/**
* modelAddon to extend old fashioned models $filters property.
* example:
* class model extends BASE_model{
* 	static $filters=array(
* 		'filter1|filter2|filter3....'
* 	);
* }
* 
* 
* @class filtersModelAddon
* @since 2011-04
* @author Jonathan Gotti <jgotti at jgotti dot net>
* @license lgpl / mmit
*/
class filtersModelAddon extends modelAddon{
	
	static private $internal;
	static public $defaultSalt = 'salt';
	static private $registered = array();
	
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
			self::$internal[$this->modelName][]='filter'.$f;
		}
	}
	static private function _initFilters(){
		static $initialized;
		if( $initialized )
			return;
		$methods = get_class_methods(__class__);
		foreach($methods as $f){
			if(substr($f,0,1)==='_' || method_exists('modelAddon', $f)){
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
		
		$filters = explode('|',$mfilters[$key][0]);
		foreach($filters as $k=>$f){
			$func = isset(self::$registered[$f])?self::$registered[$f][0]:$f;
			if(! is_callable($func)){
				throw new BadFunctionCallException(__class__." unknown filter '$f'");
			}
			$args = isset($mfilters[$key][$f])?$mfilters[$key][$f]:(isset($mfilters[$key][$k+1])?$mfilters[$key][$k+1]:array());
			if( ! is_array($args) ){
				$args = array($args);
			}
			array_unshift($args,$a);
			$a = call_user_func_array($func,$args);
			if( false===$a){
				#- check for message
				$filterMsg=null;
				if( isset($mfilters[$key]['messages'])){
					if( isset($mfilters[$key]['messages'][$f]) ){
						$filterMsg=$mfilters[$key]['messages'][$f];
					}else if( isset($mfilters[$key]['messages'][$k]) ){
						$filterMsg=$mfilters[$key]['messages'][$k];
					}
				}
				if( $filterMsg===null ){
					$filterMsg = isset($mfilters['messages'][$f]) ?
						$mfilters['message'][$f]
						:( isset(self::$registered[$f]) ? self::$registered[$f][1] : null );
				}
				$this->modelInstance->appendFilterMsg(
					is_null($filterMsg)?'%4$s: invalid %1$s::%2$s value %3$s':$filterMsg,
					array($this->modelName,$key,$_a,$f)
				);
				return false;
			}
		}
		return $a;
	}
	
	//** common filters **//
	public function minlength($v,$min){
		return strlen($v)<$min?false:$v;
	}
	public function match($v,$exp){
		return preg_match($exp,$v)?$v:false;
	}
	public function dontmatch($v,$exp){
		return preg_match($exp,$v)?false:$v;
	}
	public function md5($v,$salt=null){
		return md5($v.($salt===null?self::$defaultSalt:$salt));
	}
}