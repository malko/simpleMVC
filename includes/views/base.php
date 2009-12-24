<?php
/**
*@class baseView
* class pour la gestion des vues par défaut
* incluera automatiquement les fichiers nommés header.tpl.php et footer.tpl.php
* ce comportement peut etre changé en définissant la propriété baseView::$layout
* qui est une liste des templates à inclure pour recomposé la page.
* pour permettre des comportements génériques les noms de template peuvent comporter
* les variables ':controller' et ':action' qui seront remplacé par
* le nom du controller et de l'action en cours
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @since 2007-10
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2009-12-09 - cacheName now take currentLang into account when langManager is used
*            - 2009-11-20 - add support for cacheManager on helpers calls
*            - 2009-10-23 - add viewException
*            - 2009-10-13 - baseView::addViewDir() method now avoid duplicate entry
*            - 2009-06-22 - new viewInterface method hasLivingInstance()
*            - 2009-02-06 - now viewInterface implements singleton pattern
*            - 2008-10-13 - add support for "complex" helper method call (ie: _helperName_methodName)
*            - 2008-02-15 - now extendeded abstractViewHelper can directly call other viewHelper methods as if it were into views.
*            - 2007-12-05 - new method setController() required by abstractController forward to work properly
*            - 2007-11-12 - new methods lookUpScript*() to permit easy checking of existing script files
*                         - new static property $defaultLookUpModel to ease manual call to lookUpScriptByAction
*                         - render and renderScript methods now use lookUpScript* methods
*                         - renderScript now have an additionnal parameter $useLookUp (avoid twice lookup at render time)
*            - 2007-10-25 - now helpers which extends abstractViewHelper will support method getController
*                         - baseView now support getter methods for private vars like get_privateVars or getPrivateVars.
*                           (case sensitive only the first char prefixed by _ can be replace by an uppercase letter)
*            - 2007-10-24 - new method getPendingAppMsgs automaticly called at render time to set view->_appMsgs
*                         - new method assign to ease multiple var assignment in one call.
*/

/**
* @interface viewHelperInterface
*/
interface viewHelperInterface{
	function __construct(viewInterface $view);
	function getController();
}
/**
* abstract base class to ease the creation of viewHelpers
*@class abstractViewHelper
*/
abstract class abstractViewHelper implements viewHelperInterface{
	public $view = null;
	function __construct(viewInterface $view){
		$this->view = $view;
	}
	function __call($m,$a){
		return call_user_func_array(array($this->view,$m),$a);
	}
	function getController(){
		return $this->view->getController();
	}
}
/**
* define the interface any view system must implements to work properly inside the simpleMVC framework
*@interface viewInterface
*/
interface viewInterface{
	//function __construct(abstractController $controller=null,array $layout=null);
	static public function getInstance(abstractController $controller=null,array $layout=null);
	static public function hasLivingInstance($returnInstance=false);
	function __set($k,$v);
	function __get($k);
	function __isset($k);
	function assign($k,$v=null);

	function setController(abstractController $controller);
	function setLayout(array $layout=null);
	function addViewDir($viewDir);
	function lookUpScriptByAction($action=null,$controller=null,$scriptPathModel=null);
	function lookUpScript($scriptFileName);
	function render($action=null,$force=false);
	function renderScript($scripFile,$useLookUp=true);

	function getHelper($helperName,$autoLoad=false);
	function helperLoad($helperName,$forceReload=false);
	function helperLoaded($helperName,$returnHelper=false);

	function getPendingAppMsgs();
}

class viewException extends Exception{}
/**
* @class baseView
*/
class baseView implements viewInterface{
	static protected $_rendered = false;
	static public $defaultLayout = array(
		# 'header.tpl.php',
		':controller_:action.tpl.php|default_:action.tpl.php',
		# 'footer.tpl.php',
	);
	/** used only for manual script lookup */
	static public $defaultLookUpModel = ':controller_:action.tpl.php';
	protected $_viewDirs = array();
	protected $_layout = null;
	protected $_controller = null;
	protected $_loadedHelpers = array();

	/** where will go all the user define datas */
	private $_datas = array();

	public $_appMsgs = array();

	static protected $_instance = null;

	protected function __construct(abstractController $controller=null,array $layout=null){
		if( null !== $controller)
			$this->setController($controller);
		$this->setLayout($layout);
	}

	static public function getInstance(abstractController $controller=null,array $layout=null){
		if( self::$_instance instanceof baseView ){
			$view = self::$_instance;
			if( null !== $controller )
				$view->setController($controller);
			if( null !== $layout)
				$view->setLayout($layout);
		}else{
			$view = new baseView($controller,$layout);
			self::$_instance = $view;
		}
		return $view;

	}
	/**
	* check if there's a living instance of the given view.
	* @param bool $returnInstance if true will return viewInterface or null instead of bool
	* @return bool or viewInterface/null depending of $returnInstance parameter
	*/
	static public function hasLivingInstance($returnInstance=false){
		if( self::$_instance instanceof baseView ){
			return $returnInstance?self::$_instance:true;
		}
		return $returnInstance?null:false;
	}

	function __set($k,$v){
		$this->_datas[$k] = $v;
	}

	function __get($k){
		return isset($this->_datas[$k])?$this->_datas[$k]:null;
	}

	/**
	* this one is required to permit use of empty()
	*/
	function __isset($k){
		return isset($this->_datas[$k]);
	}
	/**
	* assign one or more var to view.
	* @param mixed $k name of var to assign or list of key=>values to assign
	* @param mixed $v value of var to assign or null in case of multiple assignment or to unset a given var
	* @return viewInterface to permit chaining
	*/
	function assign($k,$v=null){
		if( is_array($k) ){
			foreach($k as $key=>$val)
				$this->_datas[$key] = $val;
		}elseif(is_null($v)){
			if( isset($this->_datas[$k]) )
				unset($this->_datas[$k]);
		}else{
			$this->_datas[$k] = $v;
		}
		return $this;
	}

	/**
	* manage protected var getters (ie: getProtectedVar, get_protectedVar (case sensitive) ) and call to helpers methods.
	*/
	function __call($m,$a){
		#- getter methods for protected vars ie: getDatas() / get_datas()
		if(preg_match('!^get((_|[A-Z]).*)!',$m,$match)){
			if($match[2] !== '_')
				$match[1] = '_'.strtolower($match[2]).substr($match[1],1);
			if( isset($this->{$match[1]}) )
				return $this->{$match[1]};
		}
		$cached = false;
		if( strpos($m,'_cached_')===0 ){
			$cached = true;
			$m = substr($m,8);
			$cacheName = $m.(class_exists('langManager',false)?'_'.langManager::getCurrentLang():'').(empty($a)?'':'_'.md5(serialize($a)));
			$res = cacheManager::get($cacheName);
			if( null !== $res)
				return $res;
		}
		#- try helpers method calls
		if(! preg_match('!^_([a-zA-Z0-9]+?)_(.*)!',$m,$match) ){ #- call to default helper methods
			$helper = $this->getHelper($m,true);
			if( $cached)
				return cacheManager::set($cacheName,call_user_func_array(array($helper,$m),$a));
			return call_user_func_array(array($helper,$m),$a);
		}else{ #- considering we are in presence of a "complex" helper method call ie: _helperName_methodName()
			if( method_exists($match[1].'_viewHelper',$match[2]) ){
				$helper = $this->getHelper($match[1],true);
				if( $cached)
					return cacheManager::set($cacheName,call_user_func_array(array($helper,$match[2]),$a));
				return call_user_func_array(array($helper,$match[2]),$a);
			}
		}
		#- if(! preg_match('!^_([a-zA-Z0-9]+?)_(.*)!',$m,$match) ){ #- call to default helper methods
			#- $helper = $this->getHelper($m,true);
			#- return call_user_func_array(array($helper,$m),$a);
		#- }else{ #- considering we are in presence of a "complex" helper method call ie: _helperName_methodName()
			#- if( method_exists($match[1].'_viewHelper',$match[2]) ){
				#- $helper = $this->getHelper($match[1],true);
				#- return call_user_func_array(array($helper,$match[2]),$a);
			#- }
		#- }
		#- nothing was found at all throw an exception
		throw new viewException(__class__."::$m() unsupported method call.");
	}

	/**
	* return existing instance of a given helper.
	* if autoLoad is set to TRUE then will try to load it if not already loaded
	* @param str  $helperName
	* @param bool $autoLoad   if true then will try to load helper even if not loaded
	* @param viewHelperInterface
	*/
	function getHelper($helperName,$autoLoad=false){
		if( $autoLoad || $this->helperLoaded($helperName) )
			return $this->helperLoad($helperName);
		return null;
	}
	/**
	* try to load the given helper
	* @param str  $helperName  name of the helper class (without _viewHelper suffix)
	* @param bool $forceReload if true then will replace any previous instance with a new one
	*                          instead of returning the one that already exists
	* @return viewHelperInterface
	*/
	public function helperLoad($helperName,$forceReload=false){
		#- check for loaded helper first
		if( (! $forceReload) && ($helper = $this->helperLoaded($helperName,true)) )
			return $helper;
		#- return new instance of helper
		$helperKey = strtolower($helperName);
		$helperName.= '_viewHelper';
		try{
			class_exists($helperName);
		}catch(Exception $e){
			throw new viewException("$helperName view Helper not found");
		}
		$this->_loadedHelpers[$helperKey] = new $helperName($this);
		return $this->_loadedHelpers[$helperKey];
	}
	/**
	* check if a given helper is loaded or not.
	* @param str  $helperName   name of the helper to check
	* @param bool $returnHelper if set to true then will return null or viewHelperInterface
	*                           instead of bool
	* @return bool or viewHelperInterface depending on $returnHelper value.
	*/
	public function helperLoaded($helperName,$returnHelper=false){
		$helperName = strtolower($helperName);
		if(! isset($this->_loadedHelpers[$helperName]) )
			return $returnHelper?null:false;
		return $returnHelper?$this->_loadedHelpers[$helperName]:true;
	}

	/**
	* set the controller of the view
	* @param abstractController $controller
	* @return viewInterface for method chaining
	*/
	public function setController(abstractController $controller){
		$this->_controller = $controller;
		return $this;
	}

	/**
	* set layout for this view without touching the default one
	* @param array $layout array of view script files
	* @return viewInterface for method chaining
	*/
	public function setLayout(array $layout=null){
		$this->_layout = (null===$layout?self::$defaultLayout:$layout);
		return $this; #- for chaining
	}

	/**
	* append a directory for view script lookup.
	* the last added will be the first checked.
	* @param str $viewDir
	* @return viewInterface for method chaining
	*/
	public function addViewDir($viewDir){
		if(! is_dir($viewDir) )
			throw new viewException("$viewDir is not a valid directory");
		#- if already set remove it first so it appear only once at last position
		if( ($tmp = array_search($viewDir,$this->_viewDirs,true)) !== false){
			unset($this->_viewDirs[$tmp]);
			$this->_viewDirs = array_values($this->_viewDirs);
		}
		$this->_viewDirs[] = $viewDir;
		return $this;
	}

	/**
	* look in viewDirs for script existing script to render for the given action.
	* @param str $action     case sensitive name of the action to look at
	* @param str $controller case sensitive name of the controller.
	*                        (can be usefull to dinamicly define the layout
	*                         by checking other controllers scripts to render.)
	* @param str $scriptPathModel will be set to $defaultLookUpModel if not given
	* @return str scriptPath or false if not found.
	*/
	public function lookUpScriptByAction($action=null,$controller=null,$scriptPathModel=null){
		if(is_null($scriptPathModel))
			$scriptPathModel = self::$defaultLookUpModel;
		if(is_null($controller))
			$controller = $this->_controller->getName();
		if( is_null($action))
			$action = 'default';
		$scripts = explode('|',preg_replace('!:(action|controller)!e','$\1',$scriptPathModel));
		foreach($scripts as $scriptFile){
			$tmp = $this->lookUpScript($scriptFile);
			if($tmp !== false)
				return $tmp;
		}
		return false;
	}
	/**
	* check path for given scriptFile regarding the viewDirs setted
	* @param str scriptFile script filename
	* @return str script path or false if not found
	*/
	public function lookUpScript($scriptFile){
		foreach(array_reverse($this->_viewDirs) as $d){
			if(is_file("$d/$scriptFile"))
				return "$d/$scriptFile";
		}
		return is_file($scriptFile)?$scriptFile:false;
	}

	/**
	* try to render all scripts setted in layout.
	* calling render will automaticly call getPendingAppMsgs()
	* @param string $action if empty will be replace by default
	* @param bool   $force  will force rendering even if already rendered
	*/
	public function render($action=null,$force=false){
		if( self::$_rendered && ! $force)
			return;
		$this->getPendingAppMsgs();
		$controller = $this->_controller->getName();
		if(is_null($action))
			$action = 'default';
		foreach($this->_layout as $scriptPathModel){
			$scriptFile = $this->lookUpScriptByAction($action,$controller,$scriptPathModel);
			if($scriptFile !== false)
				$this->renderScript($scriptFile,false);
		}
		self::$_rendered = true;
	}

	/**
	* render the given script file.
	* @param  str  $scriptFile can be the script file path or only the name if you do a lookup
	* @param  bool $useLookUp   if false then won't use lookUpScript but only check if file exists.
	* @return bool scriptFile included or not.
	*/
	public function renderScript($scriptFile,$useLookUp=true){
		if($useLookUp){
			$scriptFile = $this->lookUpScript($scriptFile);
			if($scriptFile === false)
				return false;
		}elseif(! is_file($scriptFile) ){
				return false;
		}
		include($scriptFile);
		return true;
	}
	/**
	* populate this->_appMsgs with pending appMsgs
	* @return viewInterface for method chaining
	*/
	public function getPendingAppMsgs(){
		eval(get_class($this->_controller)."::pendingAppMsgs(\$this->_appMsgs);");
		return $this;
	}
}
