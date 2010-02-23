<?php
/**
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/

echo "<h1>$this->pageTitle</h1>";

// Get model filters and add it to the new item url
if( empty($this->fieldFilters) ){
	$filters = null;
}else{
	$filters = array() ;
	foreach($this->fieldFilters as $name=>$value){
		$filters[]= "$name,$value" ;
	}
	$filters = implode(',',$filters);
}

if( ! empty($this->_modelConfig['LIST_FILTERS']) ){
	echo '<div  class="adminListFilters"><h2>Filters</h2>
	<input type="hidden" id="filterBasePath" name="filterBasePath" value="'.$this->url('list',array('modelType'=>$this->modelType)).'"/>';
	foreach($this->_modelConfig['LIST_FILTERS'] as $f){
		$options=array(
			'id' => "_modelFilter_$f",
			'formatStr' => '<div class="adminListFiltersItem">%label %input</div>',
			'forceEmptyChoice'=>true,
			'multiple'=>false

		);
		if( isset($this->fieldFilters,$this->fieldFilters[$f]))
			$options['value'] = $this->fieldFilters[$f];

		echo $this->modelFormInput($this->modelType,$f,$options);
	}

	echo '<button class="ui-button ui-button-search" id="adminListFiltersDo">Filter</button></div>';
	$jsSelector = '#_modelFilter_'.implode(', #_modelFilter_',$this->_modelConfig['LIST_FILTERS']);
	$this->_js_script('
		$("'.$jsSelector.'").change(function(){ $("#adminListFiltersDo").click(); });
		$("#adminListFiltersDo").click(function(){
			var params=[];
			$("'.$jsSelector.'").each(function(){
				var i=$(this), v = i.val();
				if((! v) || v=="0")return;
				params.push(i.attr("name")+","+v);
			});
			window.location = $("#filterBasePath").val()+"/_filters/"+params.join(",");
		});
	');
}


if($this->_smvcAllowedAction!==null){
	$tmp=$this->_smvcAllowedAction;
	extract($tmp);
}else{
	$add=$edit=$del=true;
}

if(!empty($add)){
	echo '
	<div style="text-align:right;">
		<a href="'.$this->url('add',array('modelType'=>$this->modelType,'_filters'=>$filters),true).'" class="ui-button ui-button-circle-plus"> '.langManager::msg('Add new item',null,$this->_langManagerDicName).'.</a>
	</div>';
}
echo $this->adminSortableList($this->listDatas,$this->listHeaders,'id',$edit,$del);
