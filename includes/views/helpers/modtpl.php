<?php

class modtpl_viewHelper extends abstractViewHelper{
	private $activeModules = null;

	function __construct(viewInterface $view){
		parent::__construct($view);
		$this->checkActiveModule();
	}

	function modtpl($module,$template,array $contextVars = null){
		if(! in_array($module,$this->activeModules))
			return false;
		$this->view->renderScript($module."_$template.modtpl.php",true,$contextVars);
		return '';
	}

	function checkActiveModule(){
		$this->activeModules = array_keys((array) smvcModule::modulesConfig(true)->filter('active'));
	}
}