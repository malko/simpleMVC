<?php
/**
* create getter for multilingual fields
* for example if you have models with name_en, name_fr, and so on as data fields
* you will be abble to point the unexistant name property that will point to the current language setting.
* @example sample multilingualModelClass
class multilingualModel extends BASE_multilingualModel{
	static protected $modelAddons = array('multilingual');
  static public $_multilingualFieldScheme = ':name_:lc';
}
*/
class multilingualModelAddon extends modelAddon{

	/**
	* format string for localized field names, placholders are:
	* - :name fieldName (the fieldName without the localised part (ex: name)
	* - :lc the langcode as return by the langmanager
	* - :Lc the langcode first letter upperCase
	* - :LC the langcode in all uppercase
	* this may be ovveride in the model that implement this addon directly.
	*/
	static public $_multilingualFieldScheme = ':name_:lc'; // sprintf will receive current lang as 1st param and fieldName as second
	static private $_internals = array();
	protected $multilingualFieldScheme = null;

	#- protected $overloadedModelMethods = array();
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		list($this->multilingualFieldScheme,$this->overloadedModelMethods) = self::$_internals[$this->modelName];
	}

	/** called only once by modelType */
	protected function _initModelType(){
		#- check all fields
		$datasFields = array_keys($this->modelInstance->datas);
		if( property_exists($this->modelName,'_multilingualFieldScheme') ){
			$fldScheme = abstractModel::_getModelStaticProp($this->modelName,'_multilingualFieldScheme');
		}else{
			$fldScheme = self::$_multilingualFieldScheme;
		}
		$this->multilingualFieldScheme = $fldScheme;
		#- each multilingual fields must at least manage default  lang
		$dfltLangScheme = '!^'.$this->schemeReplace('(.*)',langManager::getDefaultLang()).'$!';
		$overloadedModelMethods = array();
		foreach($datasFields as $f){
			if( ( $_f = match($dfltLangScheme,$f)) && ! in_array($_f,$datasFields,true) && ! method_exists($this->modelInstance,"get$_f")){
				$overloadedModelMethods[] = 'get'.ucFirst($_f);
			}
		}
		self::$_internals[$this->modelName] = array($fldScheme,$overloadedModelMethods);
	}

	private function schemeReplace($name,$lang){
		return str_replace(
			array(':name',':lc',':LC',':Lc'),
			array($name,$lang,strtoupper($lang),ucFirst($lang)),
			$this->multilingualFieldScheme
		);
	}

	function __call($m,$a=null){
		$m = preg_replace('!^get!','',$m);
		$fld = abstractModel::_cleanKey($this->modelName,'datasDefs',$this->schemeReplace($m,langManager::getCurrentLang()));
		if( false !== $fld)
			return $this->modelInstance->{$fld};
		return $this->modelInstance->{abstractModel::_cleanKey($this->modelName,'datasDefs',$this->schemeReplace($m,langManager::getDefaultLang()))};
	}

}