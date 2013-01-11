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

/** sample methods to use with module users
function checkSession($returnInstance=false){
	$u = users::getCurrent();
	return $returnInstance ? $u : ($u? true:false) ;
}

function checkUserRight($right=null){
	$u = checkSession(true);
	return $u?$u->hasRight($right):false;
}
*/
//-initialize our own error handler
smvcErrorHandler::init(DEVEL_MODE);
smvcErrorHandler::$contextFormatCb='smvc_print_r';

/** experimental profiling code
if( DEVEL_MODE ){
	if( isset($_GET['activateProfiler']) && $_GET['activateProfiler'] === '1'){
		$_SESSION['__smvcProfileNext__'] = true;
		echo "<script> window.onload=function(){window.top.loaction.reload();window.close();}</script>";
		smvcShutdownManager::shutdown(0,true);
	}elseif( !empty($_SESSION['__smvcProfileNext__']) ){
		$_SESSION['__smvcProfileNext__'] = false;
		function profilerReport(){
			echo '<div style="border:solid black 1px;position:absolute;background:#f0f0f0;top:0;width:98%;margin:1%;padding:5% 0;font-size:12px;z-index:9999;">
				<h1>Profiler report <a onclick="this.parentNode.parentNode.style.display=\'none\';" style="float:right">close</a></h1>
				'.smvcProfiler::htmlResults().'</div>';
		}
		smvcShutdownManager::register('profilerReport',1);
		smvcProfiler::start(2);
	}
}
*/
#- if needed specified your default database connection (uncomment next two lines)
#- db::setDefaultConnectionStr(DB_CONNECTION);
#- db::$_default_verbosity = DEVEL_MODE?1:0; #- only report errors

#- include class-abstractModel if you use them (uncomment next two lines)
#- abstractModel::$useDbProfiler = DEVEL_MODE?true:false;

#- Set default views directories lasts will be try first and vice-versa
abstractController::$defaultViewClass = 'baseView';
abstractController::$appMsgIgnoreRepeated=2;

#- smvcAutoloader::addAppPath(ROOT_DIR);
smvcAutoloader::addAppPath(LIB_DIR.'/modules');
smvcAutoloader::addAppPath(APP_DIR);


if( defined('MODULES_CONF') && PHP_SAPI!=='cli' ){
	$modulesConf = smvcModule::modulesConfig()->filter('active',true);
	foreach($modulesConf as $mod=>$conf){
		try{
			smvcAutoloader::addAppPath(MODULES_DIR."/$mod");
			if( is_dir(MODULES_DIR."/$mod/locales") )
				langManager::addLocalesDir(MODULES_DIR."/$mod/locales",false);
		}catch(Exception $e){
			show($e,'exit');
		}
	}
}

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
#- if( DEVEL_MODE )
#- 	langManager::collectFailures(true);
#-langManager::setLocalesDirs(array(
#-	ROOT_DIR.'/locales',
#-	APP_DIR.'/locales',
#-));
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
$actionParameters = array();
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
		if( count($route) ){
			$actionParameters = $route;
			while (count($route) > 1) {
				$_GET[array_shift($route)] = array_shift($route);
			}
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
	$controller->redirectAction(ERROR_DISPATCH,null,404);
}
#- action call
try{
  call_user_func_array(array($controller,$_action),$actionParameters);
}catch(Exception $e){
	if( DEVEL_MODE )
		show($e->getMessage(),$e->getTrace(),'color:maroon;exit');
	abstractController::appendAppMsg(langManager::msg("Can't find '%s' action for '%s' controller.",array($_action,$_controller)),'error');
	$controller->redirectAction(ERROR_DISPATCH,null,404);
}
