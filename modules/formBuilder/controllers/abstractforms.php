<?php
abstract class abstractformsController extends jsonRpcController {

	protected function preAction($action){
		$this->view->setLayout(array('forms_:action.tpl.php'));
	}
	protected function formAction($formName=null){
		$form =  formBuilderForms::getFilteredInstance( array('where name=?',$formName) );
		if( (!$form instanceof formBuilderForms) || $form->isTemporary() ){
			self::appendAppMsg('Unknown page','error');
			return $this->forward('error');
		}else if( !$form->isActive() ){
			self::appendAppMsg('Requested form is closed','error');
			return $this->forward('error');
		}
		$this->formName = $formName;
	}

	protected function errorAction($formName=null){ /* dummy function to allow error page display */ }

	protected function resultAction($formName=null){
		if( null===$formName && isset($_GET['formName']) ){
			$formName = $_GET['formName'];
		}
		$form =  formBuilderForms::getFilteredInstance( array('where name=?',$formName) );
		url_viewHelper::$rewriteUseKeys=false; // force no keys in url rewrite
		if(! $form instanceof formBuilderForms ){
			return $this->msgRedirect('Unknown page','error',':error',array($formName));
		}
		if( ! $form->isActive() ){
			return $this->msgRedirect('Requested form is closed','error',':error',array($formName));
		}
		try{
			$r = formBuilderResults::getInstanceFromDatas(array(
					'formBuilderForm' => $form->PK
					,'rawDatas'=>json_encode(empty($_POST)?$_GET:$_POST)
			));
			$r->save();
			//-- Sending a message to the admin when the action is an email
			if( preg_match('/^[^@]+@[^@]+$/',$form->action ) && easymail::check_address($form->action) ){
				$m = new easymail($form->action,'['.FRONT_NAME.' - '.$form->name.'] submission');
				$m->body("FORMULAIRE $form->name\n\n".$this->_varexport('',json_decode($r->rawDatas))."\n\nRAWDATAS:\n".$r->rawDatas,'plain');
				$m->send();
			}
		}catch(Exception $e){
			return $this->msgRedirect("Error while saving your submission.".$e->getMessage(),'error',':error',array($formName));
		}
		return $this->redirectAction(':success',array($formName));
	}

	private function _varexport($k,$v,$level=0){
		$indent = str_repeat("\t",max(0,$level));
		$indentK = ($level>0?substr($indent,1):'');
		if( is_scalar($v) ){
			$v = str_replace("\n","\n$indent",$v);
		}else{
			$res = array();
			foreach( $v as $vk=>$vv){
				$res[] = str_replace("\n","\n$indent",$this->_varexport($vk,$vv,$level+1));
			}
			$v = implode("\n$indent",$res);
		}
		return ($k?"$indentK$k:\n":'')."$indent$v";
	}

	//-- defaults failure/success methods (just print a message for now
	protected function successAction($formName=null){
		/* method can be override with formName testing */
	}
	/**
	* check a given form name already exists or not
	* @param string $formName
	* @return bool
	*/
	function formNameExistsAction($formName,$ignoredId=null){
		return (int) db::getInstance(DB_CONNECTION)->get_count(
			'formBuilderForms'
			, array('where name=?'.($ignoredId!==null?' AND formId !=?':''),$formName,$ignoredId)
		);
	}

}