<?php
/**
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
	echo "<h1>$this->pageTitle</h1>";
	echo '
	<div style="text-align:right;">
		<a href="'.$this->url('add',null,array('modelType'=>$this->modelType)).'"><img src="'.ELEMENTS_URL.'/icones/admin/add.png" alt="'.langManager::msg('Add new item',null,$this->_langManagerDicName).'"/> '.langManager::msg('Add new item',null,$this->_langManagerDicName).'.</a>
	</div>';
	echo $this->adminSortableList($this->listDatas,$this->listHeaders);
?>
