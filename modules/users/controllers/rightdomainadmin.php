<?php
class rightdomainadminController extends moduseradminController{
	public $modelType='userRightDomains';
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
			'name'=>''
		)
		,'FORM_ORDER' => array(
			'fieldGroupMethod'=>'tabs'
			,array('name'=>'Right Domain properties','fields'=>array('name'))
		)
		,'FORM' => array(
			'name'=>array('required'=>true,'minlength'=>3,'maxlength'=>25,'rule'=>'/^[A-Za-z0-9_-]+$/','help'=>"3 to 25 characters long, accepted characters are a-z A-Z 0-9 _ -")
		)
	);

}