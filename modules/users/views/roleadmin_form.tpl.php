<h1><?php echo $this->pageTitle ?></h1>
<form action="<?php echo $this->actionUrl ?>" method="post" class="adminForm">
<?php

function userRightsCheckboxes($selected){
	$view = baseView::getInstance();
	$userRightDomains = userRightDomains::getAllInstances('rights','order by domainId');
	$rightStr ='<div class="userRightDomains">';
	foreach($userRightDomains as $domain){
		$rightStr .= '<div class="userRightDomain"><h3>'.langManager::msg('domain: '.$domain->name,null,$view->_langManagerDicName).'</h3>';
		foreach( $domain->rights->sortByPK() as $right)
			$rightStr .= $view->formInput('rights[]',$right->PK,'check',array(
				'label'=>langManager::msg('right: '.$right->fullName,null,$view->_langManagerDicName)
				,'id'=>'right'.$right->PK
				,'checked'=>$view->_model_?($view->_model_->hasRight($right)?true:false):false
			));
		$rightStr .= '</div>';
	}
	return "$rightStr</div>";
}

	$this->js('$(".adminForm :input:not(:disabled,:hidden):first").focus();','jquery');
	if( !empty($this->datasDefs) ){
		$inputOpts = array(
			'formatStr'=>'<tr class="formInput"><td>%label</td><td>%input</td></tr>'
		);
		$primaryKey = null;
		if( isset($this->_model_)){
			$primaryKey = $this->_model_->primaryKey;
			echo $this->modelFormInput($this->_model_,$primaryKey,isset($this->inputOpts[$primaryKey])?$this->inputOpts[$primaryKey]:array());
		}

		if( isset($this->fieldsOrder['fieldGroupMethod']) ){
			$fieldGroupMethod = $this->fieldsOrder['fieldGroupMethod'];
			$formStr = '';
			foreach($this->fieldsOrder as $k=>$group){
				if( 'fieldGroupMethod'===$k || empty($group['fields']))
					continue;
				$groupStr = "\n<table border=\"0\" cellspacing=\"0\" cellpadding=\"2\">";
				foreach($group['fields'] as $f){
					$opts = $inputOpts;
					if( isset($this->inputOpts[$f] ) )
						$opts = array_merge($inputOpts,$this->inputOpts[$f]);
					if((! isset($this->_model_)) && !empty($this->{$f})){
						$opts['value'] = $this->{$f};
					}
					if( $f!=='rights' ){
						$groupStr .= $this->modelFormInput(isset($this->_model_)?$this->_model_:$this->modelType,$f,$opts);
					}else{
						$groupStr .= userRightsCheckboxes(isset($this->_model_)?$this->_model_->rights:userRightsCollection::init());
					}
				}
				$groupStr .= "\n</table>\n";
				$groupName = langManager::msg(empty($group['name'])?$this->modelType:$group['name'],null,$this->_langManagerDicName);
				switch($fieldGroupMethod){
					case 'tabs':
					case 'tabbed':
						$tabs = (isset($tabs)?$tabs:'')."<li><a href=\"#tabs-$k\">$groupName</a></li>";
						$formStr .= "\n<div id=\"tabs-$k\">$groupStr\n</div>\n";
						break;
					case 'accordion':
						$formStr .= "\n<h3><a href=\"#\">$groupName</a></h3>\n<div>$groupStr\n</div>\n";
						break;
					default:
						$formStr .= "\n<fieldset id=\"fieldGroup_$k\">\n\t<legend>$groupName</legend>\n$groupStr\n</fieldset>\n";
				}
			}
			$tabs = isset($tabs)?"<ul>$tabs</ul>":'';
			echo "<div id=\"fieldsGroups\">\n$tabs".($fieldGroupMethod!=='tabbed'?$formStr:'<div class="tk-tabbed-pannels">'.$formStr.'</div>')."\n</div>\n";
			if( !empty($fieldGroupMethod) && 'fieldset' !== $fieldGroupMethod){
				if( $fieldGroupMethod === 'tabbed' ){
					$this->_jqueryToolkit_tabbed('form #fieldsGroups');
				}else{
					$this->js("$('form #fieldsGroups').$fieldGroupMethod();",'jqueryui');
				}
			}
		}else{
			$formFields = empty($this->fieldsOrder)?array_keys($this->datasDefs):$this->fieldsOrder;
			echo '<table border="0" cellspacing="0" cellpadding="2">';
			foreach($formFields as $k){

				if( $k===$primaryKey)
					continue;
				if( $k==='rights'){
					echo userRightsCheckboxes(isset($this->_model_)?$this->_model_->rights:userRightsCollection::init());
					continue;
				}
				$opts = $inputOpts;
				if( isset($this->inputOpts[$k] ) )
					$opts = array_merge($inputOpts,$this->inputOpts[$k]);
				if((! isset($this->_model_)) && !empty($this->{$k})){
					$opts['value'] = $this->{$k};
				}
				echo $this->modelFormInput(isset($this->_model_)?$this->_model_:$this->modelType,$k,$opts);
			}
			echo '</table>';
		}

	}
?>
<div style="text-align:right;">
	<div class="ui-buttonset" style="margin:0.2em 0;">
		<?php
			$backAction = "window.location='".(( empty($this->_modelConfig['ACTION']) || $this->_modelConfig['ACTION']['list'] )?$this->listUrl:$this->url(DEFAULT_DISPATCH))."';";
			echo '<button type="button" onclick="'.$backAction.'" class="ui-button ui-button-arrowreturnthick-1-w" tabindex="1">'.langManager::msg('back',null,$this->_langManagerDicName).'</button>';
		?>
		<button type="submit" class="ui-button ui-button-disk"><?php echo langManager::msg('save',null,$this->_langManagerDicName); ?></button>
	</div>
</div>

</form>
<?php
$this->js('
	$(".userRightDomains .userRightDomain h3").each(function(){
		var parent = $(this).parent()
			, childs = parent.find(":checkbox");
		;
		$(\'<input type="checkbox" />\').prependTo(this).change(function(){
			childs.attr("checked",$(this).is(":checked"));
		}).attr("checked",childs.length===childs.filter(":checked").length?"checked":"");
	});
','jquery');