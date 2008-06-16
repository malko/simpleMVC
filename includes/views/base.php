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
* @changelog - 2008-02-15 - now extendeded abstractViewHelper can directly call other viewHelper main methods as if it were into views.
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

interface viewHelperInterface{
  function __construct(viewInterface $view);
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
  function __construct(abstractController $controller=null,array $layout=null);
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

  function __construct(abstractController $controller=null,array $layout=null){
    if(! is_null($controller) )
      $this->setController($controller);
    $this->setLayout($layout);
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
    #- return protected vars
    if(preg_match('!^get((_|[A-Z]).*)!',$m,$match)){
      if($match[2] !== '_')
        $match[1] = '_'.strtolower($match[2]).substr($match[1],1);
      if( isset($this->{$match[1]}) )
        return $this->{$match[1]};
    }
    #- looking for corresponding helper
    $helper = $this->getHelper($m,true);
    return call_user_func_array(array($helper,$m),$a);
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
    if(! class_exists($helperName) ){
      throw new Exception("$helperName view Helper not found");
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
    $this->_layout = is_null($layout)?self::$defaultLayout:$layout;
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
      throw new Exception("$viewDir is not a valid directory");
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
