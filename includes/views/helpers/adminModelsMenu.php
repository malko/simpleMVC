<?php
/**
* @changelog
*            - 2009-04-01 - add $excluded parameter
*/
class adminModelsMenu_viewHelper extends abstractViewHelper{

	static $adminModelsControllerName = 'adminModels';

	function adminModelsMenu($id="sMVCmodelsList",$withConfigOption=false,$withRegenLink=false,$excluded=null){
		#- recupere la liste des models
		$models = array_map('basename',glob(LIB_DIR.'/models/*.php'));
		$items = array();
		$itemStr = '<li><a href="'.$this->url('list','adminmodels',array('modelType'=>'%1$s'),true).'">%1$s</a>'
			.($withConfigOption?'<a href="'.$this->url('configure','adminmodels',array('modelType'=>'%1$s'),true).'"><img src="'.GUI_IMG_URL.'/icones/admin/document-properties.png" alt="edit __toString" border="0"/></a>':'')
			.'</li>';
		foreach($models as $m){
			if( preg_match('!^BASE_!',$m) || (null !== $excluded && preg_match('!^('.$excluded.')\.php$!',$m)) )
				continue;
			$items[] = sprintf($itemStr,match('!(.*)\.php$!',$m));
		}
		if( $withRegenLink ){
			$items[] = '<li><a href="'.$this->url('generation','adminmodels',array('modelType'=>'fake')).'" >Model (re-)generation</a></li>';
		}
		return  '<ul id="'.$id.'">'.implode('',$items).'</ul>';
	}
}
