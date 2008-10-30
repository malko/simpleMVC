<?php
/**
* @package simpleMVC
* @subPackage sandbox
* front controller for admin site
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
error_reporting(E_ALL | E_STRICT);
ini_set('default_charset','utf-8');
date_default_timezone_set('Europe/Paris');

#- definition du contexte d'execution
define('FRONT_NAME',basename(dirname(__file__)));

#- definition des chemins communs
define('ROOT_DIR',dirname(dirname(__file__)));
define('CONF_DIR',ROOT_DIR.'/config');

require '../includes/fx-common.php';

#- starting session in corresponding context
if( isset($_SESSION) )
	session_write_close();
session_name(FRONT_NAME);
session_start();

#- if needed specified your default database connection
#- db::setDefaultConnectionStr(DB_CONNECTION);
#- ~ db::$_default_verbosity = DEVEL_MODE?1:0; #- only report errors

#- include class-abstractModel if you use them
#- require LIB_DIR.'/class-abstractmodel.php';
#- ~ abstractModel::$useDbProfiler = DEVEL_MODE?true:false;

#- set les repertoires de vue par défaut
abstractController::$defaultViewClass = 'baseView';
abstractController::$defaultViewDirs  = array(
	LIB_DIR.'/views',
	APP_DIR.'/views'
);

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
#- set default dictionary for model filters messages (usefull only if you use abstractModels and langManager in the same app)
#- abstractModel::$dfltFiltersDictionary='filters';

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

list($_defaultController,$_defaultAction) = explode(':',DEFAULT_DISPATCH);
#- Recuperation des controllers et actions à executer.
$_controller = isset($_POST['ctrl'])?$_POST['ctrl']:(isset($_GET['ctrl'])?$_GET['ctrl']:(!empty($_controller)?$_controller:$_defaultController));
$_action     = isset($_POST['action'])?$_POST['action']:(isset($_GET['action'])?$_GET['action']:(!empty($_action)?$_action:$_defaultAction));

#- instanciation du controller
try{
	$cname = $_controller.'Controller';
	if( class_exists($cname) )
		$controller = new $cname;
}catch(Exception $e){
	if( DEVEL_MODE )
		show($e->getMessage(),'color:orange;trace;exit');
	abstractController::appendAppMsg($e->getMessage(),'error');
	$controller = new defaultController;
	$controller->redirectAction('error','default',null,404);
}
#- appelle de l'action
try{
  $controller->$_action();
}catch(Exception $e){
	if( DEVEL_MODE )
		show($e->getMessage(),$e->getTrace(),'color:maroon;trace;exit');
	abstractController::appendAppMsg($e->getMessage(),'error');
	$controller->redirectAction('error','default',null,404);
}

