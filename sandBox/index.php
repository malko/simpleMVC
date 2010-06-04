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
ini_set('default_charset','utf-8');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Paris');

#- name of the execution context, in fact the application name, most of the time i like to use the parent directory name.
define('FRONT_NAME',basename(dirname(__file__)));

#- common paths definition
define('ROOT_DIR',dirname(dirname(__file__)));
define('CONF_DIR',ROOT_DIR.'/config');

#- include common function set and most important, will declare the autoloader and parse configs files.
require ROOT_DIR.'/includes/fx-common.php';

#- starting session in corresponding context and ensure each MVC application has it's own session
if( isset($_SESSION) )
	session_write_close();
session_name(FRONT_NAME);
session_start();

if( DEVEL_MODE ){
	$_SMVC_BENCH_ = array(
		'start'=> microtime(true),
		'initMem' => memory_get_usage(true)
	);
}

#- if needed specified your default database connection (uncomment next two lines)
#- db::setDefaultConnectionStr(DB_CONNECTION);
#- db::$_default_verbosity = DEVEL_MODE?1:0; #- only report errors

#- include class-abstractModel if you use them (uncomment next two lines)
#- abstractModel::$useDbProfiler = DEVEL_MODE?true:false;

#- Set default views directories lasts will be try first and vice-versa
abstractController::$defaultViewClass = 'baseView';
abstractController::$appMsgIgnoreRepeated=2;
abstractController::$defaultViewDirs  = array(
	LIB_DIR.'/views',
	APP_DIR.'/views'
);

#- some helpers configuration
#- formInput_viewHelper::$useFileEntry = true;
#- filemanager_viewHelper::$defaultOptions['prefixValue']='';
#- rte_viewHelper::$defaultRteOptions=array(
#- 	'imgPath' => ROOT_URL.'/js/jqueryPlugins/jqueryRte/',
#- 	'css_url' => ROOT_URL.'/rte.css'
#- );

#- if multilingual then setup langManager
#- first set directories for dictionaries lookUp
#- abstractController::$appMsgUseLangManager = true;
#- langManager::$acceptedLanguages = array('fr','en');
#-langManager::$localesDirs = array(
#-	ROOT_DIR.'/locales',
#-	APP_DIR.'/locales',
#-);
#- then set current lang in session
#- if( isset($_SESSION['lang']) || isset($_COOKIE['lang']) ){
#- 	if(empty($_SESSION['lang']))
#- 		$_SESSION['lang'] = $_COOKIE['lang'];
#- 	langManager::setCurrentLang($_SESSION['lang']);
#- }else{
#- 	$_SESSION['lang'] = langManager::langDetect(true);
#- }
#- set default dictionary for model filters messages (usefull only if you use abstractModels and langManager in the same app)
#- abstractModel::$dfltFiltersDictionary='filters';

# dispatching you don't need to edit following lines
if( USE_REWRITE_RULES ){
	if((!isset($_SERVER['PATH_INFO'])) && isset($_SERVER['REDIRECT_QUERY_STRING']) ){
		$_SERVER['PATH_INFO'] = preg_replace('!^([^\?&]+).*$!','\\1',$_SERVER['REDIRECT_QUERY_STRING']);
		if(isset($_GET[$_SERVER['PATH_INFO']]) && empty($_GET[$_SERVER['PATH_INFO']])){
			unset($_GET[$_SERVER['PATH_INFO']]);
		}
	}elseif((!isset($_SERVER['PATH_INFO'])) && PHP_SAPI==='cli' && isset($argv[1]) ){ // read from command line first parameter
		$_SERVER['PATH_INFO'] = ($argv[1][0]==='/'?'':'/').$argv[1];
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

#- setting default layout
baseView::getInstance()->setLayout(array(
  'header.tpl.php',
  ':controller_:action_'.langManager::getCurrentLang().'.tpl.php|:controller_:action.tpl.php|default_:action.tpl.php',
  'footer.tpl.php'
));
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
		show($e->getMessage(),$e->getTrace(),'color:orange;exit');
	abstractController::appendAppMsg(langManager::msg("Can't find '%s' controller.",array($_controller)),'error');
	$controller = new pagesController();
	$controller->redirectAction(ERROR_DISPATCH,null,null,404);
}
#- action call
try{
  $controller->$_action();
}catch(Exception $e){
	if( DEVEL_MODE )
		show($e->getMessage(),$e->getTrace(),'color:maroon;exit');
	abstractController::appendAppMsg(langManager::msg("Can't find '%s' action for '%s' controller.",array($_action,$_controller)),'error');
	$controller->redirectAction(ERROR_DISPATCH,null,null,404);
}
