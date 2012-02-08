<?php
class rightadminController extends moduseradminController{
	public $modelType='userRights';
	#- public $loadDatas = 'domain';
	public $authorized_right = 'moduser.rightadmin';

	public $_allowedActions=array(
		'edit'=>true
		,'list'=>true
		,'del'=>true
		,'add'=>true
		,'export'=>false
	);

	function init(){
		abstractAdminmodelsController::init();
	}

	function delete(){
		if( $_GET['id'] === 1){
			return $this->msgRedirect('You can not delete administrator role.');
		}
	}

	public $_modelConfig = array(
		'LIST' => array(
			 'domain'=>'%{ %domain->name }%'
			,'name'=>''
		)
		,'FORM_ORDER' => array(
			'fieldGroupMethod'=>'tabs'
			,array('name'=>'Right properties','fields'=>array('domain','name'))
		)
		,'FORM' => array(
			'userRightDomain'=>array('type'=>'skip')
			,'domain'=>array('type'=>'select')
			,'name'=>array('required'=>true,'minlength'=>3,'maxlength'=>25,'rule'=>'/^[A-Za-z0-9_-]+$/','help'=>"3 to 25 characters long, accepted characters are a-z A-Z 0-9 _ -")
		)
	);

	function formAction(){
		if( userRightDomains::getCount() < 5){
			$this->_modelConfig['FORM']['domain']['type'] = 'selectbuttonset';
		}
		parent::formAction();
	}

}