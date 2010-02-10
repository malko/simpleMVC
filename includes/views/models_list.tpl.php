<?php
/**
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
// Get model filters and add it to the new item url

if( empty($this->fieldFilters) ){
	$filters = null;
}else{
	$filters = array() ;
	foreach($this->fieldFilters as $name=>$value)
		$filters[]= "$name,$value" ;
	$filters = implode(',',$filters);
}

echo "<h1>$this->pageTitle</h1>";
if($this->_smvcAllowedAction!==null)
	extract($this->_smvcAllowedAction);
else
	$add=$edit=$del=true;

if(!empty($add)){
	echo '
	<div style="text-align:right;">
		<a href="'.$this->url('add',array('modelType'=>$this->modelType,'_filters'=>$filters),true).'" class="ui-button ui-button-circle-plus"> '.langManager::msg('Add new item',null,$this->_langManagerDicName).'.</a>
	</div>';
}
echo $this->adminSortableList($this->listDatas,$this->listHeaders,'id',$edit,$del);
