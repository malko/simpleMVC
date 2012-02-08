<?php
/**
*@changelog
*           - 2010-03-30 - use new validable formGetState event to generate a notification
*           - 2010-02-18 - make use of jquerytoolkit version of the plugin
*/
class validable_viewHelper extends jsPlugin_viewHelper{
	
	public $requiredFiles   = array(
		#- 'js/jqueryPlugins/jqueryValidable/jquery.validable.css',
		#- 'js/jqueryPlugins/jqueryValidable/jquery.validable.js'
	);
	#- public $requiredPlugins = array('jqueryui');
	public $requiredPlugins = array('jqueryToolkit');

	private $rules = array();

	function init(){
		$this->_jqueryToolkit_loadPlugin('validable');
	}

	function validable($inputName,array $options,$parentForm='form'){
		if( !empty($options['help']))
			$options['help'] = langManager::msg($options['help'],null,$this->view->_langManagerDicName);
		if( isset($this->rules[$parentForm]) && isset($this->rules[$parentForm]['rules'][$inputName]))
			$this->rules[$parentForm]['rules'][$inputName] = array_merge($this->rules[$parentForm]['rules'][$inputName],$options);
		else
			$this->rules[$parentForm]['rules'][$inputName] = $options;
		return $this;
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
			#- $this->_js_script("$('$parentForm').validable(".self::_optionString($rules,1).").validable('check');");
			$errorMsg = preg_replace("/(?<!\\\\)'/","\'",langManager::msg("Les données du formulaire ne sont pas valides, merci de vérifier votre saisie."));
			$this->_js_script("
$('$parentForm').validable(".self::_optionString($rules,1).")
	.bind('validable_formGetState',function(event,form,state){
		if( false===state ){
			if( $('.tk-notifybox').length && $.tk.notifybox){
				$('.tk-notifybox').notifybox('notify','<div class=\"tk-state-error\" style=\"border:none;\">$errorMsg</div>');
			}else{
				alert('$errorMsg');
			}
		}
	});");
		}
		$this->rules = array();
	}

}