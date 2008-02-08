<?php
/**
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @since 2007-10
* @changelog - 2008-01-22 - now redirectAction use url_viewHelper that reflect the use of rewriteRules on/off
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
  static  $defaultViewClass = 'baseView';
  static  $defaultViewDirs = array();
  /** does a call to action methods (not actionAction) automaticly fire a view rendering */
  static  $viewAutoRendering = true;
  /** pointer on the view */
  public  $view = null;

  /** internally use to get the controller name */
  protected $name = '';
  /** action stack is used to provide current action name if required */
  protected $actionStack = array();
  /** dispatch stack is used to retrieve the full controller/action stack */
  static protected $dispatchStack= array();

  static $defaultActionName = 'index';
  static $defaultControllerName = 'index';
  /** set the way appMsg are prepared %T and %M will be respectively replaced byt msgType and msgStr*/
  static $appMsgModel = "<div class='%T'>%M</div>";

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
      $this->view = new self::$defaultViewClass($this);
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
  * @return string (ie: controller:action)
  */
  static public function getCurrentDispatch(){
    if(! count(self::$dispatchStack) ) return false;
    $res = array_reverse(self::$dispatchStack);
    return $res[0];
  }
  /**
  * return the first element in dispatch stack.
  * @return string (ie: controller:action)
  */
  static public function getFirstDispatch(){
    if(! count(self::$dispatchStack) ) return false;
    return self::$dispatchStack[0];
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
  *                      stylesheet
  */
  static function appendAppMsg($msg='',$msgType='info'){
    if(is_array($msg)){
      foreach($msg as $m){
        if(is_array($m))
          self::appendAppMsg($m[0],$m[1]);
        else
          self::appendAppMsg($m,$msgType);
      }
      return;
    }
    if(! isset($_SESSION))
      throw new Exception(__class__.'::appendAppMsg() require a session to be started before any call.');
    $_SESSION['simpleMVC_appMsgs'][] = str_replace(array('%T','%M'),array($msgType,$msg),self::$appMsgModel);
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
  
  ###--- ACTION CALL MANAGEMENT (where all the magic happen) ---###
  /**
  * gere les appels au methodes action (pas actionAction).
  * si une methode action est appellé gere les appels aux methodes pre/post
  * action correspondantes et genere le rendu de la vue si $viewAutoRendering = true
  */
  public function __call($method,$args=null){
    #- show($method,'color:green');
    if(method_exists($this,$method.'Action')){
      $this->_currentActionStart($method);
      $tryPreAction = $tryPostAction = true;
      #- appelle les methodes preAction
      if(method_exists($this,'pre'.$method.'Action'))
        if( true === call_user_func(array($this,'pre'.$method.'Action')) )
          $tryPreAction = false;
      if($tryPreAction && method_exists($this,'preAction'))
        $this->preAction($method);
      #- appelle l'action
      $result = call_user_func_array(array($this,$method.'Action'),$args);
      if($result === true)
        $tryPostAction = false;
      #- appelle les methodes postAction
      if($tryPostAction && method_exists($this,'post'.$method.'Action'))
        if( true === call_user_func(array($this,'post'.$method.'Action')) )
          $tryPostAction = false;
      if($tryPostAction && method_exists($this,'postAction'))
        if( true === $this->postAction($method))
          $tryPostAction = false;
      if($tryPostAction && self::$viewAutoRendering)
        $this->view->render($method);
      $this->_currentActionEnd($method);
      return $result;
    }else{
      throw new Exception(get_class($this)."::$method() method doesn't exist");
    }
  }

  ###--- FORWARD AND REDIRECTION MANAGEMENT MANAGEMENT ---###
  public function forward($actionName,$controllerName=null){
    if(is_null($controllerName)){
      $this->$actionName();
    }else{
      if(! preg_match('!(C|_c)ontroller$!',$controllerName) )
        $controllerName .= 'Controller';
      $controller = new $controllerName($this->view);
      $controller->$actionName();
      #- restore controller (was modified at new controller init time)
      $this->view->setController($this);
    }
    return true; #- convenience to easily skip chaining default postActions
  }
  /**
  * redirect user to the given uri
  * @param str   $uri				 there's no management of url rewriting using this method 
  *                          as it can be used for external redirection.
  * @param mixed $params     string or array of additionnal params to append to the uri
  *                          (no filtering only urlencode array values)
  * @param bool  $permanent  put true to specify a permanent redirection
  * @param bool  $keepGoing  put true if you don't want to trigger a user exit().
  */
  public function redirect($uri,$params=null,$permanent=false,$keepGoing=false){
    if(! is_null($params) ){
      if(is_array($params)){
        foreach($params as $k=>$v){
        	if(strlen($v)===0) continue;
          $Qstr[] = urlencode($k).'='.urlencode($v);
        }
        $params = implode('&amp;',$Qstr);
      }
      $params = ((strpos($uri,'?')!==false)?((substr($uri,-1)!=='?')?'&amp;':'') :'?').$params;
    }
    if($permanent)
      header("location: $uri$params",true,301);
    else
      header("location: $uri$params");
    if($keepGoing)
      return true; #- convenience to easily skip chaining default postActions
    exit();
  }
  /**
  * like redirect but in a more easyer way.
  * @param str   $action
  * @param str   $controller default to the current controller name
  * @param mixed $params     string or array of additionnal params to append to the uri
  *                          any value for action or ctrl params will be removed.
  * @param bool  $permanent  put true to specify a permanent redirection
  * @param bool  $keepGoing  put true if you don't want to trigger a user exit().
  */
  function redirectAction($action,$controller=null,$params=null,$permanent=false,$keepGoing=false){
    $url = $this->view->url($action,$controller,$params);
    return $this->redirect($url,null,$permanent,$keepGoing);
  }
}
