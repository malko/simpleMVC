<?php
/**
* helper pour la gestion des langues
* @class langAssign_viewHelper
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @since 2008-01
* @changelog - 2008-03-23 - separate static code into class langManager (seems more practical and logic)
*/

class langAssign_viewHelper extends abstractViewHelper{

	function __construct(viewInterface $view){
		parent::__construct($view);
		if( empty(langManager::$currentLang) )
			langManager::langDetect(true);
	}

  /**
  * assign one or more var to view using lookUpDic().
  * @param mixed  $k name of var to assign or list of key=>values to assign
  * @param mixed  $v value of var to assign or null in case of multiple assignment or to unset a given var
  * @param string $langCode lang code
  * @return viewInterface to permit chaining
  */
  public function langAssign($k,$v=null,$langCode=null){
  	#- verification du code langue
  	$langCode = langManager::isAcceptedLang($langCode); # clean up the langCode
  	if( $langCode === false){
  		$langCode = langManager::getCurrentLang();
  		if( $langCode === false ){ # no currentLang set we try to do it
  			$langCode = langManager::setCurrentLang();
  			# no languages set at all we default to standard assign()
  			if( $langCode === false)
  				return $this->view->assign($k,$v);
			}
  	}
  	if( is_array($k) ){
      foreach($k as $key=>$val)
        $this->langAssign($key,$val);
    }elseif(is_null($v)){
      if( isset($this->view->_datas[$k]) )
        $this->view->assign($k);
    }else{
    	list($controller,$action) = explode(':',abstractController::getCurrentDispatch(),2);
    	$lang = ( $langCode === langManager::getDefaultLang() )?$langCode : $langCode.'|'.langManager::getDefaultLang();
    	$this->view->assign($k,langManager::lookUpMsg($v,$controller.'_'.$action.'|'.$controller.'|default',$lang));
    }
    return $this;
  }
}


