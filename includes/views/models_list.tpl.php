<?php
/**
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/

if($this->_smvcAllowedAction!==null)
	extract($this->_smvcAllowedAction);
else 
	$add=$edit=$del=true;

if(!empty($add)){
	echo '
	<div style="text-align:right;">
		<a href="'.$this->url('add',null,array('modelType'=>$this->modelType)).'"><img src="'.GUI_IMG_URL.'/icones/admin/document-new.png" alt="'.langManager::msg('Add new item',null,$this->_langManagerDicName).'"/> '.langManager::msg('Add new item',null,$this->_langManagerDicName).'.</a>
	</div>';
}

echo $this->adminSortableList($this->listDatas,$this->listHeaders,'id',$edit,$del);
?>
