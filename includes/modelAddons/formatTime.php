<?php
/**
* format time fields to a readable string
*
*/
class formatTimeModelAddon extends modelAddon{

	static protected $_internals=array();
	static public $locals = array(
		'hours'  => '',
		'h'  => '',
		'minutes'=> '',
		'min'=> '',
		'seconds'=> '',
		'sec'=> '',
	);
	/**
	* constructor will set overloaded methods
	*/
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		#- get all sql time fields
		if(! isset(self::$_internals[$this->modelName]) ){
			$datasDefs = abstractModel::_getModelStaticProp($this->modelName,'datasDefs');
			self::$_internals[$this->modelName] = array();
			foreach( $datasDefs as $k=>$v){
				if( preg_match('!^(date)?time$!',$v['Type'])){
					#- ~ check that there's no format key already set
					if( isset($datasDefs["format$k"]) || $this->modelInstance->_methodExists("getFormat$k") )
						continue;
					$k = ucFirst($k);
					self::$_internals[$this->modelName][] = "getFormat$k";
					self::$_internals[$this->modelName][] = "getFullFormat$k";
				}
			}
		}
		$this->overloadedModelMethods = self::$_internals[$this->modelName];
		if( empty(self::$locals['hours']) )
			self::_setLocales();

	}
	function __call($m,$a=null){
		if(! preg_match('!^(?:get((?:Full)?Format))(.*)$!i',$m,$match) )
			return false;
		if( false===($field = abstractModel::_cleanKey($this->modelName,'datas',$match[2])) )
			return false;
		if( !empty($a)){
			array_unshift($a,$this->modelInstance->{$field});
		}else{
			$a = array($this->modelInstance->{$field},false,false);
		}
		return call_user_func_array(array(__class__,$match[1].'Time'),$a);
	}

	static function _setLocales($lang=null,$dic=null){
		if( class_exists('langManager',false)){
			foreach(self::$locals as $k=>$v)
				self::$locals[$k] = langManager::msg($k,null,$dic,$lang);
		}
	}

	static function formatTime($time,$shortFormat=false,$noSeconds=false){
		if(! preg_match('!^\s*(\d\d):(\d\d)(?::(\d\d))?\s*$!',$time,$t) )
			return $time;
		array_shift($t);
		$t=array_map('intval',$t);
		if( $shortFormat){
			$h = $t[0]>1?self::$locals['h']:self::_single(self::$locals['h']);
			$m = $t[1]>1?self::$locals['min']:self::_single(self::$locals['min']);
			$s = $t[2]>1?self::$locals['sec']:self::_single(self::$locals['sec']);
		}else{
			$h = ' '.($t[0]>1?self::$locals['hours']:self::_single(self::$locals['hours']));
			$m = ' '.($t[1]>1?self::$locals['minutes']:self::_single(self::$locals['minutes']));
			$s = ' '.($t[2]>1?self::$locals['seconds']:self::_single(self::$locals['seconds']));
		}
		$h = $t[0]?"$t[0]$h":'';
		$m = ($t[1] || ($noSeconds && !$h) || ($h && !$noSeconds))?($h?' ':'').sprintf('%02d',$t[1]).$m:'';
		$s = ($t[2] && !$noSeconds)?(($h||$m)?' ':'').sprintf('%02d',$t[2]).$s:'';
		return "$h$m$s";
	}

	static function fullFormatTime($time,$shortFormat=false,$noSeconds=false){
		if(! preg_match('!^\s*(\d\d):(\d\d)(?::(\d\d))?\s*$!',$time,$t) )
			return $time;
		array_shift($t);
		$t=array_map('intval',$t);
		if( $shortFormat){
			$h = $t[0]>1?self::$locals['h']:self::_single(self::$locals['h']);
			$m = $t[1]>1?self::$locals['min']:self::_single(self::$locals['min']);
			$s = $t[2]>1?self::$locals['sec']:self::_single(self::$locals['sec']);
		}else{
			$h = ' '.($t[0]>1?self::$locals['hours']:self::_single(self::$locals['hours']));
			$m = ' '.($t[1]>1?self::$locals['minutes']:self::_single(self::$locals['minutes']));
			$s = ' '.($t[2]>1?self::$locals['seconds']:self::_single(self::$locals['seconds']));
		}
		return "$t[0]$h ".sprintf("%02d$m",$t[1]).($noSeconds?'':sprintf(" %02d$s",$t[2]));
	}

	private static function _single($str){
		return preg_replace('![sx]$!','',$str);
	}
}