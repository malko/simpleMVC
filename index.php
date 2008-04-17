<?php
/**
* front controller for admin site
*/
error_reporting(E_ALL | E_STRICT);
ini_set('default_charset','utf-8');
date_default_timezone_set('Europe/Paris');

#- definition du contexte d'execution
define('FRONT_NAME','front');
require '../includes/fx-common.php';

#- starting session in corresponding context
if( isset($_SESSION) )
	session_write_close();
session_name(FRONT_NAME);
session_start();

#- if needed specified your default database connection
#- db::setDefaultConnectionStr(DB_CONNECTION);

#- set les repertoires de vue par défaut
abstractController::$defaultViewClass = 'baseView';
abstractController::$defaultViewDirs  = array(ROOT_DIR.'/'.(defined('FRONT_NAME')?FRONT_NAME.'/':'').'views');

#- parametrage du layout par défaut
baseView::$defaultLayout = array(
  'header.tpl.php',
  ':controller_:action.tpl.php|default_:action.tpl.php',
  'footer.tpl.php'
);
#- if multilingual then setup langManager
#-langManager::$localesDirs = array(
#-	ROOT_DIR.'/locales',
#-	APP_DIR.'/locales',
#-);
#-if( isset($_SESSION['lang']) )
#-	langManager::setCurrentLang($_SESSION['lang']);
#-else
#-	$_SESSION['lang'] = langManager::langDetect(true);

# routage
if( USE_REWRITE_RULES ){
	if((!isset($_SERVER['PATH_INFO'])) && isset($_SERVER['REDIRECT_QUERY_STRING']) ){
		$_SERVER['PATH_INFO'] = preg_replace('!^([^\?&]+).*$!','\\1',$_SERVER['REDIRECT_QUERY_STRING']);
		if(isset($_GET[$_SERVER['PATH_INFO']]) && empty($_GET[$_SERVER['PATH_INFO']])){
			unset($_GET[$_SERVER['PATH_INFO']]);
		}
	}
	if( isset($_SERVER['PATH_INFO']) ){
		$route = explode('/',substr($_SERVER['PATH_INFO'],1));
		$_controller = count($route) ? array_shift($route) : null;
		$_action = count($route) ? array_shift($route) : null;
		while (count($route) > 1) {
			$_GET[array_shift($route)] = array_shift($route);
		}
	}
}

#- Recuperation des controllers et actions à executer.
$_controller = isset($_POST['ctrl'])?$_POST['ctrl']:(isset($_GET['ctrl'])?$_GET['ctrl']:(!empty($_controller)?$_controller:'default'));
$_action     = isset($_POST['action'])?$_POST['action']:(isset($_GET['action'])?$_GET['action']:(!empty($_action)?$_action:'index'));


#- instanciation du controller
try{
  $cname = $_controller.'Controller';
  $controller = new $cname;
}catch(Exception $e){
	#- ~ abstractController::appendAppMsg($e->getMessage(),'error');
	#- ~ $controller = new defaultController;
	#- ~ $controller->redirectAction('error','default',null,404);
	show($e->getMessage(),'exit;trace');
}
#- appelle de l'action
try{
  $controller->$_action();
}catch(Exception $e){
	#- ~ abstractController::appendAppMsg($e->getMessage(),'error');
	#- ~ $controller->redirectAction('error','default',null,404);
	show($e->getMessage(),$e->getTrace(),'color:maroon;trace;exit');
}
