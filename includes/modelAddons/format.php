<?php

class formatModelAddon extends modelAddon{

	static protected $_internals=array();

	static public $textFormatMethods = array('ToLower','ToUpper','UpperFirst');

	static public $supportedFormatMethods = array(
		'ToLower'=>'strtolower'
		,'ToUpper'=>'strtoupper'
		,'UpperFirst'=>'formatModelAddon::ucFirst' // use this instead of native ucfirst that is not utf8 compliant using mb_string
	);

	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		$this->overloadedModelMethods = self::$_internals[$this->modelName];
	}

	protected function _initModelType(){
		$datasDefs = abstractModel::_getModelStaticProp($this->modelName,'datasDefs');
		self::$_internals[$this->modelName] = array();
		foreach( $datasDefs as $k=>$v){
			if( preg_match('!char|text|blob!',$v['Type'])){
				foreach(self::$textFormatMethods as $m){
					if( isset($datasDefs["$k$m"]) ||  $this->modelInstance->_methodExists("get$k$m") ) //-- don't override already defined behaviours
						continue;
					self::$_internals[$this->modelName][] = "get$k$m";
				}
			}
		}
	}

	function __call($m,$a){
		if(! preg_match('!^get(.*?)('.implode('|',array_keys(self::$supportedFormatMethods)).')$!',$m,$matches))
			return false;
		return call_user_func(self::$supportedFormatMethods[$matches[2]],$this->modelInstance->{"get$matches[1]"}());
	}

	static function ucFirst($str){
		return strtoupper(substr($str,0,1)).substr($str,1);
	}

}