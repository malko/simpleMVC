<?php
/**
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
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @since 2007-10
* @changelog - 2007-10-29 - appendAppMsg can now appen multiple msgs at one time
*            - 2007-10-24 - no more view->_appMsgs setting at init time
*                         - now pendingAppMsgs can take a given msgs parameter (passed by reference) to append msgs to the array
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
  
  static $defaultActionName = 'index';
  static $defaultControllerName = 'index';
  /** set the way appMsg are prepared %T and %M will be respectively replaced byt msgType and msgStr*/
  static $appMsgModel = "<div class='%T'>%M</div>";
  
  public function __construct(viewInterface $view = null){
    $this->name = strtolower(preg_replace('!(?:_c|C)ontroller$!','',get_class($this)));
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
    }
  } 
  
  /**
  * retourne le nom du controller
  */
  public function getName(){
    return $this->name;
  }
  
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
  
  /**
  * gere les appels au methodes action (pas actionAction).
  * si une methode action est appellé gere les appels aux methodes pre/post 
  * action correspondantes et genere le rendu de la vue si $viewAutoRendering = true
  */
  public function __call($method,$args=null){
    #- show($method,'color:green');
    if(method_exists($this,$method.'Action')){
      $tryPreAction = $tryPostAction = true;
      #- appelle les methodes preAction
      if(method_exists($this,'pre'.$method.'Action'))
        if( true === call_user_func(array($this,'pre'.$method.'Action')) )
          $tryPreAction = false;
      if($tryPreAction && method_exists($this,'preAction'))
        $this->preAction();
      #- appelle l'action
      $result = call_user_func_array(array($this,$method.'Action'),$args);
      if($result === true)
        $tryPostAction = false;
      #- appelle les methodes postAction
      if($tryPostAction && method_exists($this,'post'.$method.'Action'))
        if( true === call_user_func(array($this,'post'.$method.'Action')) )
          $tryPostAction = false;
      if($tryPostAction && method_exists($this,'postAction'))
        if( true === $this->postAction())
          $tryPostAction = false;
      if($tryPostAction && self::$viewAutoRendering)
        $this->view->render($method);
      return $result;
    }else{
      throw new Exception(get_class($this)."::$method() method doesn't exist");
    }
  }
  
  public function forward($actionName,$controllerName=null){
    if(is_null($controllerName)){
      $this->$actionName();
    }else{
      if(! preg_match('!(C|_c)ontroller$!',$controllerName) )
        $controllerName .= 'Controller';
      $controller = new $controllerName($this->view);
      $controller->$actionName();
    }
    return true; #- convenience to easily skip chaining default postActions
  }
  
  /**
  * like redirect but in a more easyer way.
  * @param str   $action
  * @param str   $controller default to the current controller name
  * @param mixed $params     string or array of additionnal params to append to the uri
  *                          (no filtering only urlencode array values)
  * @param bool  $permanent  put true to specify a permanent redirection
  * @param bool  $keepGoing  put true if you don't want to trigger a user exit().
  */
  public function redirect($uri,$params=null,$permanent=false,$keepGoing=false){
    if(! is_null($params) ){
      if(! is_array($params)){
        foreach($params as $k=>$v)
          $Qstr[] = urlencode($k).'='.urlencode($v);
        $params = implode('&amp;',$Qstr);
      }
      $params = ((strpos($uri,'?')!==false)?'&amp;':'?').$params;
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
    if($params){
      if(is_string($params)){
        $params = preg_replace('!(?:^|\?|&(amp;)?])(action|ctrl)=[^=&]+!','',$params);
      }elseif(is_array($params)){
        unset($params['ctrl'],$params['action']);
      }
    }
    if(is_null($controller)) 
      $controller = $this->getName();
    return $this->redirect("?ctrl=$controller&action=$action",$params,$permanent,$keepGoing);
  }
}
