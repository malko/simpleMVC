<?php
/**
* format time fields to a readable string
* @changelog - 2010-03-23 - make use of _initModelType() method
*                         - add support for fields containing date too
*/
class formatTimeModelAddon extends modelAddon{

	static protected $_internals=array();
	static public $locals = array(
		'hours'   => '',
		'h'       => '',
		'minutes' => '',
		'min'     => '',
		'seconds' => '',
		'sec'     => '',
		'years'   => '',
		'last year' => '',
		'months'  => '',
		'last month' => '',
		'days'    => '',
		'weeks'   => '',
		'last week' => '',
		'today'   => '',
		'yesterday' => ''
	);
	/**
	* constructor will set overloaded methods
	*/
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		$this->overloadedModelMethods = self::$_internals[$this->modelName];
		if( empty(self::$locals['hours']) )
			self::_setLocales();

	}
	/** called only once by modelType */
	protected function _initModelType(){
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
				self::$_internals[$this->modelName][] = "getElapsedFormat$k";
			}
		}
	}

	function __call($m,$a=null){
		if(! preg_match('!^(?:get((?:Full|Elapsed)?Format))(.*)$!i',$m,$match) )
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
		}else{
			foreach(self::$locals as $k=>$v)
				self::$locals[$k] = empty($v)?$k:$v;
		}
	}

	static function formatTime($time,$shortFormat=false,$noSeconds=false){
		if(! preg_match('!^(.*?)(\d\d):(\d\d)(?::(\d\d))?\s*$!',$time,$t) )
			return $time;
		array_shift($t);
		$prefix = array_shift($t);
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
		return "$prefix$h$m$s";
	}

	static function fullFormatTime($time,$shortFormat=false,$noSeconds=false){
		if(! preg_match('!^(.*?)(\d\d):(\d\d)(?::(\d\d))?\s*$!',$time,$t) )
			return $time;
		array_shift($t);
		$prefix = array_shift($t);
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
		return "$prefix$t[0]$h ".sprintf("%02d$m",$t[1]).($noSeconds?'':sprintf(" %02d$s",$t[2]));
	}


	static function elapsedFormatTime($dateTime){
		//-- split date and time:
		preg_match('!^(\d\d\d\d-\d\d-\d\d)?\s*(\d\d:\d\d(:\d\d)?)?$!',$dateTime,$m);
		list(,$date,$time) = $m;
		if( $date ){
			preg_match('!(\d\d\d\d)-(\d\d)-(\d\d)!',$date,$match);
			list(,$y,$m,$d) = array_map('intval',$match);
			if( ($tmp = date('Y')-$y)>=1 ){
				return $tmp===1? self::$locals['last year'] : $tmp.' '.self::$locals['years'];
			}else if( ($tmp = date('m')-$m) >=1 ){
				return $tmp===1? self::$locals['last month'] :$tmp.' '.self::$locals['months'];
			}else{
				$tmp = date('d')-$d;
				if( $tmp >= 7 ){
					$tmp = round($tmp/7);
					return $tmp===1?self::$locals['last week'] : $tmp.' '.self::$locals['weeks'];
				}else if( $tmp > 1){
					return $tmp.' '.self::$locals['days'];
				}else if( $tmp == 1 ){
					return self::$locals['yesterday'];
				}else if (empty($time)){
					return self::$locals['today'];
				}
			}
		}
		if( $time ){
			preg_match('!^\s*(\d\d):(\d\d):?(\d\d)?$!',$time,$match);
			list(,$h,$m,$s)  = array_map('intval',$match);
			if( ($tmp = date('H')-$h)>=1 ){
				return $tmp.' '.($tmp>1? self::$locals['hours'] : self::_single(self::$locals['hours']));
			}else if($m || $s) {
				$m = date('i') - $m;
				return $m.' '.($m>1? self::$locals['minutes'] : self::_single(self::$locals['minutes']));
			}
		}

		return $dateTime;

	}

	private static function _single($str){
		return preg_replace('![sx]$!','',$str);
	}
}