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
		$this->rules[$parentForm][$inputName] = $options;
	}

	function form(array $options,$formIdentifier='form'){
		if( !empty($options['rules'])){
			foreach($options['rules'] as $k=>$v){
				if( !empty($v['help']))
					$options['rules'][$k]['help'] = langManager::msg($v['help'],null,$this->view->_langManagerDicName);
			}
		}
		$this->_js_script("$('$formIdentifier').validable(".json_encode($options).");");
	}
	function _onGetPending(){
		if( empty($this->rules))
			return;
		foreach($his->rules as $parentForm=>$rules){
			$this->_js_script("$('$parentForm').validable({rules:".json_encode($rules)."})");
		}
		$this->rules = array();
	}

}