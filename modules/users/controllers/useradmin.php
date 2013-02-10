<?php
class useradminController extends moduseradminController{

	public $modelType='users';
	public $_modelConfig = array(
		'LIST' => array(
			 'login'=>''
			,'role'=>''
			,'email'=>'<a href="mailto:%email" class="ui-button ui-button-i-mail-open" title="send email">%email</a>'
			,'active'=>''
		)
		,'FORM_ORDER' => array(
			'fieldGroupMethod'=>'tabs'
			,array('name'=>'UserInfos','fields'=>array(
				'role','login','password','email','active'
			))
		)
		,'FORM' => array(
			 'userRole' => array('type'=>'skip')
			,'role' => array('type'=>'select')
			,'login'=>array('type'=>'text','required'=>true,'help'=>"5 to 20 characters long, containing only a-zA-Z0-9_-",'uneditable'=>true)
			,'password'=>array('type'=>'password')
			,'email'=>array(
				'rule'=>'email'
			)
			,'active'=>array('type'=>'selectbuttonset')
		)
	);

	function formAction(){
		if(userRoles::getCount()<5){
			$this->_modelConfig['FORM']['role']['type'] = 'selectbuttonset';
		}
		if($this->_model_){
			$this->_modelConfig['FORM']['password']['value'] = '';
		}else{
			$this->_modelConfig['FORM']['password']['value'] = self::generatePass(6,12);
		}
		if( isset($_GET['id']) && $_GET['id'] === '1'){
			$this->_modelConfig['FORM']['role']["uneditable"]=true;
			$this->_modelConfig['FORM']['role']["type"]='select';
			$this->_modelConfig['FORM']['active']["uneditable"]=true;
			$this->_modelConfig['FORM']['active']["type"]='select';
		}
		return parent::formAction();
	}

	function setActiveAction(){
		if( $_GET['id'] === "1" ){
			return $this->msgRedirect("Can't un-activate default administrator account.");
		}
		parent::setActionsAction();
	}

	function saveAction(){
		if( isset($_POST['pass']) && empty($_POST['pass']) ){
			unset($_POST['pass']);
		}
		if( !empty($_POST['resetPass'])){
			$_POST['pass'] = '';
		}
		if( isset($_POST['userId']) && $_POST['userId'] === "1"){
			unset($_POST['role'],$_POST['active']); // user 1 must stay an active admin
		}
		return parent::saveAction();
	}

	function delAction(){
		if( $_GET['id'] === "1" ){
			return $this->msgRedirect('You can not delete default administrator account.');
		}
		parent::delAction();
	}

	static function generatePass($minLength=6,$maxLength=null){
		$pass = '';
		$chars = array_merge(range(0,9),range('a','z'),range('A','Z'),array('-','_'));
		if( null === $maxLength ){
			$length = $minLength;
		}else{
			$length = rand($minLength,$maxLength);
		}
		for($i=0;$i<$length;$i++){
			shuffle($chars);
			$pass.=current($chars);
		}
		return $pass;
	}

}
