<?php

class formbuilderController extends abstractController{

	function preAction($action){
		$requiredRight = ( $action === 'configure' || ! defined('FORM_BUILDER_RIGHT') ) ? 'modules.admin' : FORM_BUILDER_RIGHT;
		if(! users::getCurrent($requiredRight) instanceof users){
			return $this->msgRedirect('Unhautorized access.','error',ERROR_DISPATCH);
		}
	}

	function configureAction(){
		if( !empty($_POST['right']) ){
			if(! $this->_token_check(300,'formbuilder') ){
				return $this->msgRedirect('action token expired','error');
			}
			$right = userRights::getInstance($_POST['right']);
			if(! $right instanceof userRights ){
				return $this->msgRedirect('unknown right','error');
			}
			$formSubmitDispatch = empty($_POST['formSubmitURI']) ? '' : preg_replace('/["\'<>\\\\]+/','',$_POST['formSubmitURI']);

			write_conf_file(CONF_DIR.'/'.FRONT_NAME.'_config.php',array(
				'FORM_BUILDER_RIGHT'=>"$right"
				,'FORM_BUILDER_DEFAULTSUBMIT'=>$_POST['formSubmitDispatch']
			));
			return $this->msgRedirect('settings saved','success','adminmodules:');
		}
	}

	/*function indexAction($pageId=1,$pageSize=10){
		$pageId = max(1,intval($pageId));
		$pageSize = min(1,max(100,intval($pageSize)));
		list($this->fbdFormList,$this->fbdNav,$this->fbdTotal) = formBuilderForms::getPagedInstances('ORDER BY formId',$pageId,$pageSize);
	}

	function newAction(){
		$this->forward('form');
	}

	function formAction(){

	}

	function editAction(){

	}*/

}

/*extends modulesAdminmodelsController{
	public $modelType = "formBuilderForm";
	public $_allowedActions=array(
		'edit'=>true
		,'list'=>true
		,'del'=>true
		,'add'=>true
		,'export'=>false
	);

	public $_modelConfig = array(
		// 'LIST' => array(
		// 	 'domain'=>'%{ %domain->name }%'
		// 	,'name'=>''
		// ),
		// 'FORM_ORDER' => array(
		// 	'fieldGroupMethod'=>'tabs'
		// 	,array('name'=>'Right properties','fields'=>array('domain','name'))
		// )
		// ,'FORM' => array(
		// 	'userRightDomain'=>array('type'=>'skip')
		// 	,'domain'=>array('type'=>'select')
		// 	,'name'=>array('required'=>true,'minlength'=>3,'maxlength'=>25,'rule'=>'/^[A-Za-z0-9_-]+$/','help'=>"3 to 25 characters long, accepted characters are a-z A-Z 0-9 _ -")
		// )
	);

}

*/