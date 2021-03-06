<?php
/**
* @package simpleMVC
* @class abstractController
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @since 2007-10
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2011-07-01 - msgRedirect will now redirect to REFERER as default even for state other than error
*            - 2010-12-08 - redirect now check REFERER to be in the application path
*            - 2010-10-06 - add minified js deletetion on clearCache
*            - 2010-09-22 - now session datas are editable in showSession
*            - 2010-09-19 - add showSession method
*            - 2010-07-01 - add clearSession method
*            - 2010-06-24 - introduce method abstractController::_getActionCacheNameParameter() to better handle the unicity of cached pages
*            - 2010-05-26 - change treatment of undefined action methods call with existing coresponding views
*            - 2010-04-08 - msgRedirect() try redirection to HTTP_REFERER before DEFAULT_DISPATCH on error msg with null dispatchString
*            - 2010-03-29 - cacheManager integration
*            - 2010-02-22 - add msgRedirect() method
*            - 2010-02-08 - now redirectAction and forward only use the dispatch string as first parameter and so drop support for second controller parmeter (this will break backward compatibility so carrefull on updating older version)
*            - 2009-12-16 - bug correction in forward with full dispatch string as action
*            - 2009-11-24 - now redirect() method redirect to HTTP_REFERER or DEFAULT_DISPATCH if no URI given.
*            - 2009-10-23 - now __call() will try to render a view script with the action name if no action is found before trying to call a view method
*            - 2009-07-20 - new static method getCurrentViewInstance()
*            - 2009-04-28 - new static property $appMsgUseLangManager to allow appMsgs to be checked against loaded dictionaires in langManager.
*            - 2009-04-28 - new method forward() call form with only one string dispatch parameter as returned by getCurrentDispatch() (ie: 'controller:action') (old way still working)
*            - 2009-04-03 - add __isset method to also check for datas in view.
*            - 2009-02-08 - $appMsgIgnoreRepeated is now a int value to permitt better repeated appMsgs management
*            - 2009-02-06 - change view instanciation to work with new singletoned views
*                         - __call will send unknown method call to linked view if available instead of throwing an exception
*                         - now forward keep current instance of controller when controllerName parameter is same as the current controller
*            - 2009-02-02 - new static method checkAppMsgExist() and property $appMsgIgnoreRepeated to supress repeated application messages
*            - 2008-08-26 - now more unused static properties $defaultActionName,$defaultControllerName
*            - 2008-08-25 - new __get/__set methods to get/set values from/to $this->view
*                         - new static property abstract::$viewAssignMethod to set the method to use for undefined variable assignement into view
*                         - dynamic method abstract::url() as shorthand to call $this->view->url()
*            - 2008-03-23 - replace parameter $permanent by $withResponseCode for redirect* methods
*            - 2008-02-20 - new parameter $explodeResult for methods getCurrentDispatch getFirstDispatch
*            - 2008-01-22 - now redirectAction use url_viewHelper that reflect the use of rewriteRules on/off
*            - 2007-12-09 - addition of dispatchStack related static methods and property
*            - 2007-12-06 - new getActionStack() method
*            - 2007-12-05 - bug correction regarding parameters treatment in redirect method
*                         - bug correction regarding the forgotten assignation of the given view at construct time
*            - 2007-11-05 - new actionStack properties and related methods like getCurrentAction()
*            - 2007-10-29 - appendAppMsg can now appen multiple msgs at one time
*            - 2007-10-24 - no more view->_appMsgs setting at init time
*                         - now pendingAppMsgs can take a given msgs parameter (passed by reference) to append msgs to the array
*
* abstract action controller.
* Chaque controller de ce contexte est une extension de ce controller.
* les controller d'actions doivent avoir des méthodes pour chaque action par
* exemple pour l'action default defaultAction.
* Pour appeller ces methodes en bénéficiant de l'instanciation automatique des vues
* mais aussi de l'appel auto des methodes pre/post action ainsi vous devez appellez l'action
* actionController::default();
* si vous appellez la methode actionController::defaultAction() directement alors aucune
* action automatique ne sera prise.
*
* PRE/POST action methods:
* vous pouvez définir les méthodes preAction et postAction dans vos controlleurs
* elles seront appellées avant et apres chaque action, vous pouvez aussi définir
* des methodes pour pre/post pour chaque action par exemple pour l'action default
* preDefaultAction postDefaultAction.
* les methodes pre/post spécifiques à une action sont éxecutés avant les methodes
* pre/post génériques et empecherons leur execution si elle retournent TRUE.
* De meme si la methode de l'action retourne TRUE alors les post[action]Action
* ne seront pas éxécutées.
*
* forward d'action:
* il est possible de rediriger vers une autre action grace a la méthode forward.
* Attention selon la facon de rediriger l'action vous executerez ou non les methodes pre/post
* automatiques liées à la nouvelle action.
*
* Important note:
* Every time you call an action directly or by forwarding action remember that
* calling actionACtion instead of action will execute that action out of any automatic
* methods. So here's the list of what won't happen in such case:
* - no call to pre/post Action
* - no call to pre/post actionAction
* - no trace in actionStack/dispatchStack so no way to get the current action name corresponding to that action
* - no automatic view rendering.
* So if you call actionAction instead of action all of the previous steps have to be called manually
* if you need them to apply.
*
* regarding actionStack/dispatchStack:
* actionStack is internal of a given controller instance
* dispatchStack is complete controller:action stack even when forwarding action from a controller to another
*/

abstract class abstractController{
	###--- default view settings ---###
	/** the class to use as the default view */
	static  public  $defaultViewClass = 'baseView';
	static  public  $defaultViewDirs = array();
	/** does a call to action methods (not actionAction) automaticly fire a view rendering */
	static  public  $viewAutoRendering = true;
	/** set the view method to call for undefined var assignation */
	static public  $viewAssignMethod = 'assign';
	/** pointer on the view */
	public $view = null;

	/** internally use to get the controller name */
	protected $name = '';
	/** action stack is used to provide current action name if required */
	protected $actionStack = array();
	/** dispatch stack is used to retrieve the full controller/action stack */
	static protected $dispatchStack= array();

	/** set the way appMsg are prepared %T and %M will be respectively replaced byt msgType and msgStr*/
	static public  $appMsgModel = "<div class=\"ui-state-%T %T tk-state-%T tk-notify  tk-notify-10000\">%M</div>";
	/** set the way repeated appMsgs are treated
	* - 0: won't perform any check and so will display all messages,
	* - 1: avoid consecutive message repetition by checking that last message is different)
	* - 2: will drop any messages that were previously appended to appMsgs nevermind the position.
	*/
	static public  $appMsgIgnoreRepeated = 2;
	/** sets whether yes or not to use langManager for appMsgs */
	static public  $appMsgUseLangManager = false;

	/**
	* crée une nouvelle instance de controller.
	* @param viewInterface $view Par défaut le controlleur va demander une instance de $defaultViewClass à utiliser.
	*                            On peut cependant réutiliser une instance déjà existante c'est notamment ce qui se passe
	*                            lors d'un appel à la méthode forward afin d'eviter la création de multiples instances de la vue
	*                            mais aussi de conservé les variables déjà assignées.
	*                            (en gros ceci concerne une utilisation avancée de simpleMVC
	*                             donc si vous débutez avec simpleMVC laissez ce parametre de coté).
	*/
	public function __construct(viewInterface $view = null){
		$this->name = strtolower(preg_replace('!(?:_c|C)ontroller$!','',get_class($this)));
		if(! is_null($view) )
			$this->view = $view;
		$this->init();
	}

	/**
	* initialisation communes aux controlleurs de ce contexte.
	* Notamment l'instanciation de ce qui est nécéssaire au vu et le traitement des messages.
	*/
	public function init(){
		if(is_null($this->view)){
			$this->view = call_user_func(array(self::$defaultViewClass,'getInstance'),$this);
			foreach(self::$defaultViewDirs as $d)
				$this->view->addViewDir($d);
		}else{
			#- this is required for forward method to work properly
			$this->view->setController($this);
		}
	}

	/**
	* retourne le nom du controller
	*/
	public function getName(){
		return $this->name;
	}

	/**
	* return the current view instance
	* @param bool $failSafe if true then in case there's no living view instance will get a view from a default controller
	*                       so ensure you to get a view of $defaultViewClass and with defaults view dirs sets
	* @param viewInterface or null
	*/
	static public function getCurrentViewInstance($failSafe=false){
		$livingView = call_user_func(array(self::$defaultViewClass,'hasLivingInstance'),true);
		if( $livingView instanceof viewInterface)
			return $livingView;
		if(! $failSafe)
			return null;
		#- failsafe and get an empty view with viewDirs correctly set
		$view = call_user_func(array(self::$defaultViewClass,'getInstance'));
		foreach(self::$defaultViewDirs as $d)
			$view->addViewDir($d);
		return $view;
		#- failsafe and get a view from default controller
		#- ~ $controller = (defined('DEFAULT_DISPATCH')?preg_replace('!:.*$!','',DEFAULT_DISPATCH):'default').'Controller';
		#- ~ $controller = new $controller;
		#- ~ return $controller->view;
	}

	/**
	* return the active controller
	*/
	static public function getCurrent(){
		$view = self::getCurrentViewInstance();
		if(! $view )
			return null;
		return $view->getController();
	}

	###--- DISPATCH STACK MANAGEMENT ---###
	/**
	* append element to dispatch stack
	* @param abstractController the controller doing an action you want to push in stack
	* @protected
	*/
	static protected function dispatchStackAppend(abstractController $controller){
		self::$dispatchStack[] = $controller->getName().':'.$controller->getCurrentAction();
	}
	/**
	* remove element from dispatch stack
	* @param abstractController the controller doing an action you want to remove from stack
	* @protected
	*/
	static protected function dispatchStackRemove(abstractController $controller){
		$stackNeedle =  $controller->getName().':'.$controller->getCurrentAction();
		// note: if you wonder why i haven't used array_search as it return last corresponding index to do this,
		// the response is that it's not safe to rely on, as not clearly announced in manual
		// so i've chosen this way which will ever be safe
		for($i=count(self::$dispatchStack)-1;$i>=0;$i--){
			if(self::$dispatchStack[$i]===$stackNeedle){
				unset(self::$dispatchStack[$i]);
				self::$dispatchStack = array_values(self::$dispatchStack);# reindex stack
				return;
			}
		}
		// if nothing found we are in trouble
		throw new Exception("Error occured in dispatch stack order.".(print_r(self::$dispatchStack,1)));
	}
	/**
	* @return array full dispatchStack
	*/
	static public function getDispatchStack(){
		return self::$dispatchStack;
	}
	/**
	* return the current last element in dispatch stack.
	* @param  bool $explodeResult if true then will return array(controller,action)
	* @return string (ie: controller:action) or array if $explodeResult is true
	*/
	static public function getCurrentDispatch($explodeResult=false){
		if(! count(self::$dispatchStack) ) return false;
		$res = array_reverse(self::$dispatchStack);
		return $explodeResult?explode(':',$res[0],2):$res[0];
	}
	/**
	* return the first element in dispatch stack.
	* @param  bool $explodeResult if true then will return array(controller,action)
	* @return string (ie: controller:action) or array if $explodeResult is true
	*/
	static public function getFirstDispatch($explodeResult=false){
		if(! count(self::$dispatchStack) ) return false;
		return $explodeResult?explode(':',self::$dispatchStack[0],2):self::$dispatchStack[0];
	}
	/**
	* return the current controller name
	*/
	static public function getCurrentControllerName(){
		$dispatch = self::getCurrentDispatch();
		if($dispatch===false)
			return false;
		return substr($dispatch,0,strpos($dispatch,':'));
	}
	/**
	* return the first called controller name
	*/
	static public function getFirstControllerName(){
		if(! count(self::$dispatchStack))
			return false;
		return substr(self::$dispatchStack[0],0,strpos(self::$dispatchStack[0],':'));
	}

	###--- ACTION STACK MANAGEMENT ---###
	/**
	* methode interne pour garder une trace de la pile d'appels des actions.
	* ajoute l'action en cours a la pile d'appel
	* @param string $action le nom de l'action en cours
	* @protected
	*/
	protected function _currentActionStart($action){
		$this->actionStack[] = $action;
		self::dispatchStackAppend($this);
	}
	/**
	* methode interne pour garder une trace de la pile d'appels des actions.
	* supprime l'action en cours de la pile d'appel
	* @param string $action le nom de l'action en cours
	* @protected
	*/
	protected function _currentActionEnd($action){
		if($action !== $this->getCurrentAction()){
			throw new Exception("Error occured in action stack order.".(print_r($this->actionStack,1)));
		}
		self::dispatchStackRemove($this);
		array_pop($this->actionStack);
	}
	/**
	* retourne le nom de l'action en cours
	*/
	public function getCurrentAction(){
		if(! count($this->actionStack) ) return false;
		$res = array_reverse($this->actionStack);
		return $res[0];
	}
	/**
	* retourne la pile d'action.
	*/
	public function getActionStack(){
		return $this->actionStack;
	}

	###--- APPLICATION MESSAGES MANAGEMENT ---###
	/**
	* @param mixed $msg    the message string or list of messages (array).
	*                      array can be of two form first one is only a list of msgs:
	*                      array(msg1,msg2,msg3...)
	*                      or a list of array(msg, msgClass)
	*                      array(array(msg1,msgClass1))
	* @param str $msgClass info|error|success in fact can be whatever you want
	*                      just think about declaring corresponding styles in the
	*                      stylesheet or to handle it properly in abstractModel::$appMsgModel
	* return true
	*/
	static function appendAppMsg($msg='',$msgType='info'){
		if(is_array($msg)){
			foreach($msg as $m){
				if(is_array($m))
					self::appendAppMsg($m[0],$m[1]);
				else
					self::appendAppMsg($m,$msgType);
			}
			return true;
		}
		if(! isset($_SESSION))
			throw new Exception(__class__.'::appendAppMsg() require a session to be started before any call.');
		if( self::$appMsgUseLangManager )
			$msg = langManager::msg($msg);
		if(self::$appMsgIgnoreRepeated > 0){ //-- check for repeated msg if needed
			if( self::checkAppMsgExist($msg,$msgType,self::$appMsgIgnoreRepeated>1?false:true) )
				return true;
		}
		$_SESSION['simpleMVC_appMsgs'][] = str_replace(array('%T','%M'),array($msgType,$msg),self::$appMsgModel);
		return true;
	}

	/**
	* retourne les messages de l'application y compris apres une redirection et
	* vide les messsages en attente
	* @param array $msgs     tableau auquel ajouter la liste des messages
	* @param bool  $noClean  si vrai alors ne vide pas la liste des messages en attente (à utiliser avec précaution)
	* @return array
	*/
	static function pendingAppMsgs(array &$msgs=null,$noClean=false){
		if(is_null($msgs))
			$msgs = array();
		if( isset($_SESSION['simpleMVC_appMsgs']) )
			$msgs = array_merge($msgs,(array) $_SESSION['simpleMVC_appMsgs']);
		if(! $noClean)
			$_SESSION['simpleMVC_appMsgs'] = array();
		return $msgs;
	}

	/**
	* check if given message is already or not in the pendingAppMsg
	* @param string $msg     the message string
	* @param str $msgClass   info|error|success @see abstractController::appendAppMsg
	* @param bool $onlyLast  check only that the last message is the same or not
	*/
	static function checkAppMsgExist($msg,$msgType='info',$onlyLast=false){
		if( empty($_SESSION['simpleMVC_appMsgs']) )
			return false;
		$msg = str_replace(array('%T','%M'),array($msgType,$msg),self::$appMsgModel);
		if( $onlyLast )
			return ($msg === end($_SESSION['simpleMVC_appMsgs']))?true:false;
		return in_array($msg,$_SESSION['simpleMVC_appMsgs']);
	}

	###--- ACTION CALL MANAGEMENT (where all the magic happen) ---###
	/**
	* gere les appels au methodes action (pas actionAction).
	* si une methode action est appellé gere les appels aux methodes pre/post
	* action correspondantes et genere le rendu de la vue si $viewAutoRendering = true
	*/
	public function __call($method,$args=null){
		#- show($method,'color:green');
		#- check for action method or a scriptView corresponding to controller:action
		if(method_exists($this,$method.'Action') || ($this->view instanceof viewInterface && $viewScriptAction = $this->view->lookUpScriptByAction($method)) ){
			$this->_currentActionStart($method);
			$tryPreAction = $tryPostAction = true;
			#- appelle les methodes preAction
			if(method_exists($this,'pre'.$method.'Action'))
				if( true === call_user_func(array($this,'pre'.$method.'Action')) )
					$tryPreAction = false;
			if($tryPreAction && method_exists($this,'preAction'))
				$this->preAction($method);
			#- appelle l'action
			if( defined('CACHE_MANAGER_ENABLE') && CACHE_MANAGER_ENABLE ){
				#- check for a cached view before going further
				$useCache = 0;
				foreach($this->view->getLayout() as $tpl){
					if( preg_match('!(^|\|)_cached_[^|]*:(controller|action)!',$tpl) ){
						#- check for cached tpl for this action
						$scriptFile = substr($this->view->lookUpScriptByAction($method,$this->getName(),$tpl),8);
						$cacheName = FRONT_NAME.'_'.preg_replace('!\.tpl\.php$!','',basename($scriptFile)).(class_exists('langManager',false)?'_'.langManager::getCurrentLang():'').$this->_getActionCacheNameParameter();
						if( null === cacheManager::get($cacheName) ){
							$useCache = 0;
							break;
						}
						$useCache++;
					}
				}
			}
			if( isset($viewScriptAction) && $viewScriptAction!== false )
				$result = null;
			else
				$result = empty($useCache)?call_user_func_array(array($this,$method.'Action'),$args):null;
			if($result === true)
				$tryPostAction = false;
			#- appelle les methodes postAction
			if($tryPostAction && method_exists($this,'post'.$method.'Action'))
				if( true === call_user_func(array($this,'post'.$method.'Action')) )
					$tryPostAction = false;
			if($tryPostAction && method_exists($this,'postAction'))
				if( true === $this->postAction($method))
					$tryPostAction = false;
			if($tryPostAction && self::$viewAutoRendering){
				$this->view->render($method);
			}
			$this->_currentActionEnd($method);
			return $result;
		}elseif($this->view instanceof viewInterface){ #- try to delegate to viewInterface (allowing call to helpers methods)
			try{
				return call_user_func_array(array($this->view,$method),$args);
			}catch(viewException $e){
				throw new BadMethodCallException(get_class($this)."::$method() method doesn't exist");
			}
		}else{
			throw new BadMethodCallException(get_class($this)."::$method() method doesn't exist");
		}
	}

	public function _getActionCacheNameParameter($additionalParameters=null){
		return md5(serialize(array($_GET,$_POST,$additionalParameters)));
	}

	public function __get($k){
		if(! $this->view instanceof viewInterface )
			throw new Exception($this->getName()." trying to get unknow $k property.");
		return $this->view->{$k};
	}

	public function __set($k,$v){
		if(! $this->view instanceof viewInterface )
			throw new Exception($this->getName()." trying to set unknow $k property without any viewInterface instance.");
		return $this->view->{self::$viewAssignMethod}($k,$v);
	}

	public function __isset($k){
		if(! $this->view instanceof viewInterface )
			throw new Exception($this->getName()." trying to get unknow $k property.");
		return $this->view->__isset($k);
	}

	###--- FORWARD AND REDIRECTION MANAGEMENT MANAGEMENT ---###
	/**
	* forward the dispatching to another action in same or other controller.
	* @param string $dispatchString dispatch string 'controller:action', 'controller:' or simply 'action'
	* @param mixed  $params  array of additional parameters to pass to the following action or a scalart that will be passed as the first parameter
	*/
	public function forward($dispatchString){
		if( false !== strpos($dispatchString,':') ){
			list($controllerName,$actionName)=explode(':',$dispatchString,2);
		}else{
			$controllerName = null;
			$actionName = $dispatchString;
		}
		if( empty($actionName)){
			$actionName = substr(DEFAULT_DISPATCH,strpos(DEFAULT_DISPATCH,':')+1);
		}
		$params = null;
		if( func_num_args() > 1){
			$params = func_get_args();
			array_shift($params);
		}
		if(empty($controllerName) || in_array($controllerName,array($this->getName(),get_class($this)),true)){
			if( null === $params){
				$this->$actionName();
			}else{
				call_user_func_array(array($this,$actionName),$params);
			}
		}else{
			if(! preg_match('!(C|_c)ontroller$!',$controllerName) )
				$controllerName .= 'Controller';
			$controller = new $controllerName($this->view);
			if( null === $params){
				$controller->$actionName();
			}else{
				call_user_func_array(array($controller,$actionName),$params);
			}
			#- restore controller (was modified at new controller init time)
			$this->view->setController($this);
		}
		return true; #- convenience to easily skip chaining default postActions
	}
	/**
	* redirect user to the given uri
	* @param str   $uri				 there's no management of url rewriting using this method
	*                          as it can be used for external redirection.
	*                          if null is passed then will default to HTTP_REFERER || DEFAULT_DISPATCH
	* @param mixed $params     string or array of additionnal params to append to the uri
	*                          (no filtering only urlencode array values)
	* @param bool/int $withResponseCode  put true to specify a permanent redirection (code 301)
	*                                    you also can pass an int as $http_response_code (404 for example)
	* @param bool  $keepGoing  put true if you don't want to trigger a user exit().
	*/
	public function redirect($uri=null,$params=null,$permanent=false,$keepGoing=false){
		if( null === $uri ){
			$uri = (isset($_SERVER['HTTP_REFERER']) && false!==strpos($_SERVER['HTTP_REFERER'],ROOT_URL))?$_SERVER['HTTP_REFERER']:$this->url(DEFAULT_DISPATCH);
		}
		if(! is_null($params) ){
			static $argSeparator;
			if(! isset($argSeparator))
				$argSeparator = ini_get('arg_separator.output');
			if(is_array($params)){
				foreach($params as $k=>$v){
					if(strlen($v)===0) continue;
					$Qstr[] = urlencode($k).'='.urlencode($v);
				}
				$params = implode($argSeparator,$Qstr);
			}
			$params = ((strpos($uri,'?')!==false)?((substr($uri,-1)!=='?')?$argSeparator:'') :'?').$params;
		}
		//-- keep errors for next page
		if( class_exists('smvcErrorHandler',false)){
			smvcErrorHandler::store();
		}

		if(! $permanent){
			header("location: $uri$params");
		}else{
			if(! is_int($permanent))
				$permanent = 301;
			if($permanent === 404)
				header("Refresh: 0; url=$uri$params", false, 404);
			else
				header("location: $uri$params",true,$permanent);
		}
		if($keepGoing)
			return true; #- convenience to easily skip chaining default postActions
		smvcShutdownManager::shutdown(0,true);
	}
	/**
	* like redirect but in a more easyer way.
	* @param str   $dispatchString dispatch string 'controller:action', 'controller:' or simply 'action'
	* @param mixed $params     string or array of additionnal params to append to the uri
	*                          any value for action or ctrl params will be removed.
	* @param bool/int $withResponseCode  put true to specify a permanent redirection (code 301)
	*                                    you also can pass an int as $http_response_code (404 for example)
	* @param bool  $keepGoing  put true if you don't want to trigger a user exit().
	* @see url_viewHelper::ur() methods for more infos on first three parameters
	*/
	function redirectAction($dispatchString,$params=null,$withResponseCode=false,$keepGoing=false){
		$url = $this->view->url($dispatchString,$params);
		return $this->redirect($url,null,$withResponseCode,$keepGoing);
	}

	/**
	* shorthand for appendAppMsg + redirectAction.
	* @param mixed  $msg string message or array list of messages
	* @param string $state
	* @param string $dispatchString if null will use referrer if in same domain, error or default dispatch regarding
	*                               the $state passed /!\ pay attention when leaving to null of what referrer may be as it could
	*                               en in an infinite redirection loop!
	* @param mixed $params @see redirectAction for more info
	*/
	function msgRedirect($msg,$state='error',$dispatchString=null,$params=null){
		self::appendAppMsg($msg,$state);
		if( $dispatchString===null ){
			if( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],ROOT_URL)!==false ){
				return  $this->redirect($_SERVER['HTTP_REFERER'],$params);
			}else{
				$dispatchString = $state==='error'?ERROR_DISPATCH:DEFAULT_DISPATCH;
			}
		}
		return $this->redirectAction($dispatchString,$params);
	}

	###--- DEVEL_MODE SPECIFIC METHODS ---###

	function clearCache(){
		if( DEVEL_MODE_ACTIVE() ){
			cacheManager::clear(0);
			if( js_viewHelper::$autoMinify){
				$fileCacheManager = new fileCacheBackend(js_viewHelper::$scriptRootDir.'/minified',false,'-min.js');
				$fileCacheManager->clear(0);
			}
		}
		return $this->redirect();
	}
	function clearSession(){
		if( DEVEL_MODE_ACTIVE() )
			$_SESSION = array();
		return $this->redirect();
	}
	function showSession(){
		if( DEVEL_MODE_ACTIVE() ){
			simpleMVCdevelBar_viewHelper::$disable=true;
			if( isset($_POST['SESSION']) ){
				$s = json_decode($_POST['SESSION'],true);;
				$_SESSION = $s;
				$this->redirect();
			}
			echo '<!DOCTYPE HTML>
			<html>
			<head>
				<script type="text/javascript">
					window.onclose = function(){document.cookie="SMVCSessionPanel=; path=/; expires=Thu, 01-Jan-70 00:00:01 GMT";};
				</script>
			</head>
			<body>
			<div style="text-align:center">
			<div style="float:right";">
				<a href="javascript:document.cookie=\'SMVCSessionPanel=; path=/; expires=Thu, 01-Jan-70 00:00:01 GMT\';window.close();">Close</a>
				<a href="'.$this->url(':clearSession').'">Clear session</a>
			</div>
			<h1 style="font-size:16px;border-bottom:solid silver 1px;">$_SESSION content</h1>
			<pre style="text-align:left;font-size:12px;line-height:16px;">'.preg_replace("!<br\s*/?>(?:&nbsp;)*(array&nbsp;\()!i",'$1',highlight_string("<?php\n\$_SESSION = ".var_export($_SESSION,1),1)).'</pre>
			<form action="'.$this->url('showSession').'" method="post" style="font-size:14px;border-top:solid silver 1px;padding-top:1em">
			<h1 style="font-size:16px;">$_SESSION Modification</h1>
			This is a json representation of you session data you can edit this and submit to modify $_SESSION content<br />
			(Be warn that content must be valid json or you simply will erase your session data. and it will transform your json into an associative array that will simply replace your actual session datas)
			<textarea rows="10" name="SESSION" style="width:100%">'.json_encode($_SESSION).'</textarea>
			<input type="submit" name="submit" value="modify session datas">
			</form>
			</div></body></html>';
			smvcShutdownManager::shutdown(0,true);
		}
		return $this->redirect();
	}
	function saveDicFormInputs(){
		if(! DEVEL_MODE_ACTIVE() )
			return $this->redirect();
		#- delegate to the langManager the saving process and return to previous page
		langManager::saveDicFormInputs($_POST);
		$this->redirect();
	}
}
