<?php
/**
* simple controller to render static content using the default layout.
* You can change the layout by setting staticController::$layout
* by default it will look for static_actionName.tpl.php in you view dirs.
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @since 2007-11-13
*/
class staticController extends abstractController{
  
  static public $layout    = array(
    'header.tpl.php',
    'static_:action.tpl.php',
    'footer.tpl.php'
  );
  
  function init(){
    parent::init();
    $this->view->setLayout(self::$layout);
  }
  
  function __call($m,$args=null){
    # first look for static content.
    if($scriptFile = $this->view->lookUpScriptByAction($m) ){
      $this->_currentActionStart($m);
      $this->view->render($m);
      $this->_currentActionEnd($m);
      return;
    }
    # else call parent method.
    return parent::__call($m,$args);
  }
}
