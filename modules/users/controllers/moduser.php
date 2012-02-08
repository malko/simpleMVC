<?php
class moduserController extends abstractController{

	function loginAction(){
		if( empty($_POST['login']) || empty($_POST['password']) ){
			return $this->msgRedirect(langManager::msg('moduser - missing authentication datas',null,'moduser'),'error');
		}
		$u = users::getByLoginPass($_POST['login'],$_POST['password']);
		if(! $u instanceof users ){
			return $this->msgRedirect(langManager::msg('moduser - bad credentials',null,'moduser'),'error');
		}
		$u->startSession();
		return $this->redirect();
	}

	function logoutAction(){
		users::resetSession();
		return $this->redirect();
	}

}