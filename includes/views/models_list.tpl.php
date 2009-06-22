<?php
/**
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
echo "<h1>$this->pageTitle</h1>";
if($this->_smvcAllowedAction!==null)
	extract($this->_smvcAllowedAction);
else
	$add=$edit=$del=true;

if(!empty($add)){
	echo '
	<div style="text-align:right;">
		<a href="'.$this->url('add',null,array('modelType'=>$this->modelType)).'" class="ui-button ui-button-circle-plus"> '.langManager::msg('Add new item',null,$this->_langManagerDicName).'.</a>
	</div>';
}

echo $this->adminSortableList($this->listDatas,$this->listHeaders,'id',$edit,$del);
?>
