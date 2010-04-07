<?php
/**
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - add forgotten input hidden on primaryKey when using fieldsOrder
*/
$encType=false;
if(! empty($this->inputOpts)){
	foreach($this->inputOpts as $o){
		if( (!empty($o['type'])) && ($o['type']==='file' || $o['type']==='fileextended') ){
			$encType=true;
			break;
		}
	}
}
?>
<h1><?php echo $this->pageTitle ?></h1>
<form action="<?php echo $this->actionUrl ?>" method="post" class="adminForm"<?php echo $encType?' enctype="multipart/form-data"':''?>>
<?php
	if( !empty($this->datasDefs) ){
		$inputOpts = array(
			'formatStr'=>'<tr class="formInput"><td>%label</td><td>%input</td></tr>'
		);
		$primaryKey = null;
		if( isset($this->_model_)){
			$primaryKey = $this->_model_->primaryKey;
			echo $this->modelFormInput($this->_model_,$primaryKey,isset($this->inputOpts[$primaryKey])?$this->inputOpts[$primaryKey]:array());
		}

		if( is_object($this->fieldsOrder)){
			$fieldGroupMethod = $this->fieldsOrder->fieldGroupMethod;
			$formStr = '';
			foreach($this->fieldsOrder as $k=>$group){
				if( 'fieldGroupMethod'===$k || empty($group->fields))
					continue;
				$groupStr = "\n<table border=\"0\" cellspacing=\"0\" cellpadding=\"2\">";
				foreach($group->fields as $f){
					$opts = $inputOpts;
					if( isset($this->inputOpts[$f] ) )
						$opts = array_merge($inputOpts,$this->inputOpts[$f]);
					if((! isset($this->_model_)) && !empty($this->{$f})){
						$opts['value'] = $this->{$f};
					}
					$groupStr .= $this->modelFormInput(isset($this->_model_)?$this->_model_:$this->modelType,$f,$opts);
				}
				$groupStr .= "\n</table>\n";
				$groupName = langManager::msg(empty($group->name)?$this->modelType:$group->name,null,$this->_langManagerDicName);
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
						$formStr .= "\n<fieldset id=\"fieldGroup_$group->name\">\n\t<legend>$groupName</legend>\n$groupStr\n</fieldset>\n";
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
			$backAction = "window.location='".(( empty($this->_modelConfig['ACTION']) || $this->_modelConfig['ACTION']['list'] )?$this->listUrl:DEFAULT_DISPATCH)."';";
			echo '<button type="button" onclick="'.$backAction.'" class="ui-button ui-button-arrowreturnthick-1-w">'.langManager::msg('back',null,$this->_langManagerDicName).'</button>';
		?>
		<button type="submit" class="ui-button ui-button-disk"><?php echo langManager::msg('save',null,$this->_langManagerDicName); ?></button>
	</div>
</div>

</form>
