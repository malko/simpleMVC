<?php
/**
* @package simpleMVC
* @subPackage sandbox
* front controller for sandBox application
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
#- next three lines depends on your needs they correspond to my developpment settings and are optionals, they could have be defined in the php.ini for example.
error_reporting(E_ALL | E_STRICT);
ini_set('default_charset','utf-8');
date_default_timezone_set('Europe/Paris');


#- name of the execution context, in fact the application name, most of the time i like to use the parent directory name.
define('FRONT_NAME',basename(dirname(__file__)));

#- common paths definition
define('ROOT_DIR',dirname(dirname(__file__)));
define('CONF_DIR',ROOT_DIR.'/config');

#- include common function set and most important, will declare the autoloader and parse configs files.
require '../includes/fx-common.php';

#- starting session in corresponding context and ensure each MVC application has it's own session
if( isset($_SESSION) )
	session_write_close();
session_name(FRONT_NAME);
session_start();

#- if needed specified your default database connection (uncomment next two lines)
#- db::setDefaultConnectionStr(DB_CONNECTION);
#- db::$_default_verbosity = DEVEL_MODE?1:0; #- only report errors

#- include class-abstractModel if you use them (uncomment next two lines)
#- require LIB_DIR.'/class-abstractmodel.php';
#- abstractModel::$useDbProfiler = DEVEL_MODE?true:false;

#- Set default views directories lasts will be try first and vice-versa
abstractController::$defaultViewClass = 'baseView';
abstractController::$defaultViewDirs  = array(
	LIB_DIR.'/views',
	APP_DIR.'/views'
);

#- setting default layout
baseView::$defaultLayout = array(
  'header.tpl.php',
  ':controller_:action.tpl.php|default_:action.tpl.php',
  'footer.tpl.php'
);

#- if multilingual then setup langManager
#- first set directories for dictionaries lookUp
#-langManager::$localesDirs = array(
#-	ROOT_DIR.'/locales',
#-	APP_DIR.'/locales',
#-);
#- then set current lang in session
#-if( isset($_SESSION['lang']) )
#-	langManager::setCurrentLang($_SESSION['lang']);
#-else
#-	$_SESSION['lang'] = langManager::langDetect(true);
#- set default dictionary for model filters messages (usefull only if you use abstractModels and langManager in the same app)
#- abstractModel::$dfltFiltersDictionary='filters';

# dispatching you don't need to edit following lines
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
#- get requested controller and action
$_controller = isset($_POST['ctrl'])?$_POST['ctrl']:(isset($_GET['ctrl'])?$_GET['ctrl']:(!empty($_controller)?$_controller:$_defaultController));
$_action     = isset($_POST['action'])?$_POST['action']:(isset($_GET['action'])?$_GET['action']:(!empty($_action)?$_action:$_defaultAction));

#- controller instanciation
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
#- action call
try{
  $controller->$_action();
}catch(Exception $e){
	if( DEVEL_MODE )
		show($e->getMessage(),$e->getTrace(),'color:maroon;trace;exit');
	abstractController::appendAppMsg($e->getMessage(),'error');
	$controller->redirectAction('error','default',null,404);
}

