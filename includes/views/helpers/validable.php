<?php
class validable_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles   = array(
		'js/jqueryPlugins/jqueryValidable/jquery.validable.css',
		'js/jqueryPlugins/jqueryValidable/jquery.validable.js'
	);
	public $requiredPlugins = array('jqueryui');

	private $rules = array();

	function validable($inputName,array $options,$parentForm='form'){
		if( !empty($options['help']))
			$options['help'] = langManager::msg($options['help'],null,$this->view->_langManagerDicName);
		if( isset($this->rules[$parentForm]) && isset($this->rules[$parentForm]['rules'][$inputName]))
			$this->rules[$parentForm]['rules'][$inputName] = array_merge($this->rules[$parentForm]['rules'][$inputName],$options);
		else
			$this->rules[$parentForm]['rules'][$inputName] = $options;
	}

	function form(array $options,$formIdentifier='form'){
		if( !empty($options['rules'])){
			foreach($options['rules'] as $k=>$v){
				if( !empty($v['help']))
					$v['help'] = langManager::msg($v['help'],null,$this->view->_langManagerDicName);
				$this->validable($k,$v,$formIdentifier);
			}
			unset($options['rules']);
		}
		foreach($options as $k=>$v){
			$this->rules[$formIdentifier][$k]=$v;
		}
		//$this->_js_script("$('$formIdentifier').validable(".self::_optionString($options,1).").validable('check');");
	}
	function _onGetPending(){
		if( empty($this->rules))
			return;
		foreach($this->rules as $parentForm=>$rules){
			$this->_js_script("$('$parentForm').validable(".self::_optionString($rules,1).").validable('check');");
		}
		$this->rules = array();
	}

}