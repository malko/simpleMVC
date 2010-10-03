<?php
/**
* dummy jsPlugin that load jquery-ui usefull for jsPlugins that require jquery-ui
* @class jqueryui_viewHelper
*/
class button_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles = array(
		'js/ui.button.min.js',
		'js/ui-button.css'
	);
	public $requiredPlugins = array(
		'jqueryui'
	);
	/**
	* possible options:
	* priority: (string) primary|secondary
	* disabled: (bool)
	*/
	function button($selector=".ui-button",array $options=null){
		$this->_js_scriptOnce("$('$selector').button(".(is_array($options)?json_encode($options):'').");");
	}
}
