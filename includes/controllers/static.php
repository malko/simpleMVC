<?php
/**
* simple controller to render static content using the default layout.
* You can change the layout by setting staticController::$layout
* by default it will look for static_actionName.tpl.php in you view dirs.
* @class staticController
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @since 2007-11-13
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog - 2008-12-19 - add check for extended class layout setting at init time
*/
class staticController extends abstractController{

  static public $layout    = array(
    #- 'header.tpl.php',
    'static_:action.tpl.php',
    #- 'footer.tpl.php'
  );

  function init(){
    parent::init();
		if( isset($this->_layout) )
    	$this->view->setLayout($this->_layout);
		else
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
