<?php
class langController extends abstractController{

	function __call($m,$a=null){
		if( strlen($m) === 2 && langManager::isAcceptedLang($m)){
			$_SESSION['lang'] = $m;
			return $this->redirect();
		}
	}

}