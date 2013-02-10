<?php
/**
* @todo gerer groupe d'utilisateur par défaut
*/
class usersModule extends smvcModule{

	public $_configureDispatch='modusers:configure';

	public $dependOn = array(
		'adminmodules'
	);

	protected function init(){ // called at loading time
		//-- define default checkUserRights and checkSession methods
		if(! function_exists('checkUserRights')){
			function checkUserRight($right=null){
				$u = checkSession(true);
				return $u?$u->hasRight($right):false;
			}
		}
		if(! function_exists('checkSession')){
			function checkSession($returnInstance=false){
				$u = users::getCurrent();
				return $returnInstance ? $u : ($u? true:false) ;
			}
		}
	}

	protected function install(){
		if( ! isset($_SESSION['modulesUsersInstallConfig']) ){ //without required datas for install redirect to user install
			smvcModule::moduleSetActive('users'); // set active even if not installed to allow usage of own controllers and views
			$controller = abstractController::getCurrent();
			$token = $controller->_token_get('modmoduserInstall',true);
			return $controller->redirectAction('modusers:install',array($token[0]=>$token[1]));
		}
	}

	protected function uninstall(){
		#- delete sql tables but don't remove the models


	}

}