<?php
/**
* helper pour la gestion des langues
* @class langAssign_viewHelper
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @since 2008-01
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
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
		#- ~ if(is_object($v) || is_array($v)){ #- object/array assignation are not managed by langAssign
			#- ~ $this->view->assign($k,$v);
		#- ~ }else
		if(is_array($k)){ #- multiple assignation at once
      foreach($k as $key=>$val)
        $this->langAssign($key,$val,$langCode);
		}elseif(! is_string($v)){
				$this->view->assign($k,$v);
    }else{
			list($controller,$action) = abstractController::getCurrentDispatch(true);
			$this->view->assign($k,langManager::lookUpMsg($v,$controller.'_'.$action.'|'.$controller.'|default',$langCode));
    }
		return $this->view;
  }
}
