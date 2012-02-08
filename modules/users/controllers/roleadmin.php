<?php
class roleadminController extends moduseradminController{
	public $modelType='userRoles';

	public $authorized_right = 'moduser.roleadmin';

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

	function delAction(){
		if( $_GET['id'] === "1" || $_GET['id'] === "2" ){
			return $this->msgRedirect('You can not delete this role.');
		}
		parent::delAction();
	}

	public $_modelConfig = array(
		'LIST' => array(
			 'name'=>''
			,'active'=>''
		)
		,'FORM_ORDER' => array(
			'fieldGroupMethod'=>'fieldset'
			,array('name'=>'Role properties','fields'=>array('name','active'))
			,array('name'=>'Permissions','fields'=>array('rights'))
		)
		,'FORM' => array(
			'name'=>array('required'=>true,'minlength'=>3,'maxlength'=>25,'rule'=>'/^[A-Za-z0-9_-]+$/','help'=>"3 to 25 characters long, accepted characters are a-z A-Z 0-9 _ -")
			#- ,'active'=>array('type'=>'selectbuttonset','default'=>true)
			,'active'=>array('type'=>'hidden','default'=>true)
			,'rights'=>array('type'=>'checkbox')
		)
	);

}