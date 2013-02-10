<?php

class langSelector_viewHelper extends abstractViewHelper{
	function langSelector($selectedLang = null){
		foreach( langManager::$acceptedLanguages as $l){
			$acceptedLanguages[$l] = $this->view->url("lang:$l");
		}
		return $this->modtpl(
			'langSelector'
			,'selector'
			,array(
				'availableLanguages'=>$acceptedLanguages
				,'currentLanguage'=>$selectedLang===null?langManager::getCurrentLang():$selectedLang
			)
		);
	}
}