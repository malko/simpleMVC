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
		$filters[]= "$name,$value";
	}
	$filters = implode(',',$filters);
}

if( ! empty($this->_modelConfig['LIST_FILTERS']) && count(array_diff($this->_modelConfig['LIST_FILTERS'],array('hidden'))) ){
	$datasDefs = abstractModel::_getModelStaticProp($this->modelType,'datasDefs');
	echo '<form  class="adminListFilters" method="post" action="'.$this->url('filteredList',array('modelType'=>$this->modelType)).'"><h2>'.langManager::msg('list_filters_title').'</h2>';
	foreach($this->_modelConfig['LIST_FILTERS'] as $f=>$type){
		$options=array(
			'id' => "_modelFilter_$f",
			'formatStr' => '<div class="adminListFiltersItem">%label %input</div>',
			'forceEmptyChoice'=>true,
			'multiple'=>false,
			//'maxlength'=>0
		);
		if( $type !== 'default'){
			$options['type'] = ($type==='like')?'text':$type;
		}
		if( isset($this->fieldFilters,$this->fieldFilters[$f]))
			$options['value'] = $this->fieldFilters[$f];
		#- in case of boolean values force an empty value
		if( preg_match('!^(bool|tinyint(\(1\))?)$!i',$datasDefs[$f]['Type'])){
			$options['values'] = array(''=>langManager::msg('all'),0=>langManager::msg('no'),1=>langManager::msg('yes'));
			$options['default'] ='';
			if( $type==='default')
				$options['type'] = 'selectbuttonset';
		}
		echo $this->modelFormInput($this->modelType,$f,$options);
	}

	echo '<button class="ui-button ui-button-search" id="adminListFiltersDo" type="submit">'.langManager::msg('list_filters_button').'</button></form>';
/*
	$jsSelector = '#_modelFilter_'.implode(', #_modelFilter_',$this->_modelConfig['LIST_FILTERS']);
	$this->_js_script('
		//$("'.$jsSelector.'").change(function(){ $("#adminListFiltersDo").click(); });
		$("#adminListFiltersDo").click(function(){
			var params=[];
			$("'.$jsSelector.'").each(function(){
				var i=$(this), v = i.val();
				if((! v) || v=="0")return;
				params.push(i.attr("name")+","+v);
			});
			window.location = $("#filterBasePath").val()+"/_filters/"+params.join(",");
		});
	');*/
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
		<a href="'.$this->url('add',array('modelType'=>$this->modelType,'_filters'=>$filters),true).'" class="ui-button ui-button-circle-plus" id="adminListAddNew"> '.langManager::msg('Add new item',null,$this->_langManagerDicName).'.</a>
	</div>';
	$this->js('$("#adminListAddNew").button().focus();','jquery');
}
echo $this->adminSortableList($this->listDatas,$this->listHeaders,'id',$edit,$del);
