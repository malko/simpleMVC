<?php

class formbuilder_viewHelper extends abstractViewHelper{
	public $paths = null;
	function __construct(viewInterface $view){
		$this->paths = smvcModule::getModulePaths('formBuilder');
		parent::__construct($view);
	}

	function formbuilder($formName,$evenInactive=false){
		#- lookup the good form
		$form = formBuilderForms::getFilteredInstance(array('where name=?',$formName));
		#- show($form->datas);
		if(! $form instanceof formBuilderForms){
			return null;
		}
		if(! ( $form->isActive() || $evenInactive) ){
			return null;
		}

		//$this->view->_js_includes($this->paths->uri.'/views/formbuilder.js',true);
		$this->view->_js_includes($this->paths->uri.'/views/formbuilder.css',true);

		if($form->groupMethod !== 'tabs'){
			$this->view->helperLoad('jqueryui');
		}
		return $form->html;
	}
}