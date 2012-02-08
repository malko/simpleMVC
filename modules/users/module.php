<?php
/**
* @todo gerer groupe d'utilisateur par défaut
*/
class usersModule extends smvcModule{

	public $_configureDispatch='modusers:configure';

	public $dependOn = array(
		'adminmodules'
	);

	function install(){

		if( ! isset($_SESSION['modulesUsersInstallConfig']) ){
			$controller = abstractController::getCurrent();
			$token = $controller->_token_get(null,true);
			return $controller->redirectAction('modusers:install',array($token[0]=>$token[1]));
		}

	}

	function uninstall(){
		#- delete sql tables but don't remove the models


	}

}