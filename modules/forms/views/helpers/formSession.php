<?php
class formSession_viewHelper extends abstractViewHelper{


	function formSession($sessionName,array $defaultValues = null,$formMethod='POST'){
		$formVars = $formMethod==='get'?$_GET:$_POST;

		$formVars = empty($formVars)?array():$formVars;
		if( !isset($_SESSION['formSessionModule'][$sessionName])){
			$_SESSION['formSessionModule'][$sessionName] = empty($defaultValues)?array():$defaultValues;
		}


		if(! empty($formVars) ){
			foreach($formVars as $k=>$v){
				$_SESSION['formSessionModule'][$sessionName][$k] = $v;
			}
		}

		return $_SESSION['formSessionModule'][$sessionName];

	}

	function reset($sessionName=null){
		if( null === $sessionName ){
			if( isset($_SESSION['formSessionModule'])){
				unset($_SESSION['formSessionModule']);
			}
		}
		if(isset($_SESSION['formSessionModule'][$sessionName])){
			unset($_SESSION['formSessionModule'][$sessionName]);
		}
	}

}