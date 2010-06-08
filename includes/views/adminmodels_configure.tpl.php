<style>
	#settings form{ padding:0;margin:0;}
	#settings .sortable { padding:0;margin:0;}
	#settings .sortable li{ list-style-type:none; list-style-image:none;margin:5px 0;padding:5px;}
	#settings .sortable .placeholder{height:3em;}
	#settings input{white-space:pre;}
	div.fieldSet .ui-widget-header{ display:block;}
	div.fieldSet .ui-widget-header, div.fieldSet, div.formInput{ padding:5px;margin-bottom:5px;}
	div.fieldSet .ui-widget-header .ui-icon{ float:right;}
	code{ white-space:pre; font-size:.8em; display:block;border-width:1px; border-style:dotted; padding:.4em;}
	.editarea{ width:98%;height:350px;}
	.sMVC_dataField{ vertical-align:middle !important; }
</style>
<h1><?php echo $this->modelType?> settings </h1>
<div id="settings">

<form action="<?php echo $this->url('setToString',array('modelType'=>$this->modelType)) ?>" method="post" id="string">
	<h3><a name="string"><?php echo $this->modelType ?>::$__toString</a></h3>
	<div id="string-pannel">
	<div class="selectors">
	<?php
		if( $this->datasFields ){
			echo "List of datas Fields: $this->datasFields<br />";
		}
		if( $this->hasOnes ){
			echo "List of hasOne relations : $this->hasOnes<br />";
		}
	?>
	</div>
	<label>
		<?php echo $this->modelType ?>::$__toString
		<input type="text" name="_toStr" value="<?php echo htmlentities($this->_toStr,ENT_COMPAT,'UTF-8')?>" />
	</label>
	<br />
	<button type="submit" class="ui-button ui-button-small-disk"><?php echo langManager::msg('save'); ?></button>
	</div>
</form>

<form action="<?php echo $this->url('setActions',array('modelType'=>$this->modelType)) ?>" method="post" id="actions">
	<h3><a name="actions">Allowed Actions</a></h3>
	<div id="actions-pannel">
	Choose allowed actions to manage this model
	<?php
		$tmp = $this->_allowedActions;
		extract($tmp);
		echo $this->formInput('actions[edit]',empty($edit)?0:1,'selectbuttonset',array('label'=>'Can be edited','values'=>array('no','yes')));
		echo $this->formInput('actions[add]',empty($add)?0:1,'selectbuttonset',array('label'=>'Can be added','values'=>array('no','yes')));
		echo $this->formInput('actions[del]',empty($del)?0:1,'selectbuttonset',array('label'=>'Can be deleted','values'=>array('no','yes')));
		echo $this->formInput('actions[list]',empty($list)?0:1,'selectbuttonset',array('label'=>'Can be listed','values'=>array('no','yes')));
	?>
	<br />
	<button type="submit" class="ui-button ui-button-small-disk"><?php echo langManager::msg('save'); ?></button>
	</div>
</form>

<form action="<?php echo $this->url('setList',array('modelType'=>$this->modelType)) ?>" method="post"  id="list">
	<h3><a name="list">List</a></h3>
	<div id="list-pannel">
		<div class="ui-state-highlight ui-corner-all" style="padding:5px;">
			Check fields you want to be displayed in adminModels list actions.
			<br />
			You also can specify an optional format string to render this field in the list, @see abstractModel::__toString() documentation for more infos on what can be put here.
			<br />
			Field list order may be arrange by simple drag&drop.
		</div>
		<ul id="listItems" class="sortable">
		<?php
			if( $this->listDatasDefs){
				foreach($this->listDatasDefs as $fld){
					$formatInput = $this->formInput("formatStr[$fld]",isset($this->listedFields[$fld])?htmlentities($this->listedFields[$fld]):null,'text',array('label'=>'format string','size'=>'50'));
					echo $this->formInput("fields[$fld]",$fld,'checkbox',array('checked'=>isset($this->listedFields[$fld]),'label'=>$fld,'formatStr'=>'<li>%input %label'.$formatInput.'</li>'))."\n";
				}
			}
		?>
		</ul>
		<br />
		<div class="ui-buttonset-small">
			<button type="button" class="ui-button ui-button-circle-plus" id="listAddField"><?php echo langManager::msg('add list field')?></button>
			<button type="submit" class="ui-button ui-button-disk"><?php echo langManager::msg('save'); ?></button>
		</div>
	</div>
</form>


<form action="<?php echo $this->url('setFilters',array('modelType'=>$this->modelType)) ?>" method="post" id="filters">
	<h3><a name="filters">List filters</a></h3>
	<div id="filters-pannel">
	<div class="selectors">
		<div class="ui-state-highlight ui-corner-all" style="padding:5px;">
			Here you can set fields that may be used for adminModels list actions filtering.
			<br />
			Unauthorized is the default values and mean that such fields will just be ignored as filters field.
		</div>
	<ul id="listFilterItems" class="sortable">
	<?php
		$filterDisplayOptions = array(
			''       => 'unauthorized',
			'hidden' => 'allowed but hidden',
			'default'=> 'default (let simpleMVC decide the filter display)',
			'select' => 'comboBox',
			'selectbuttonset' => 'set of buttons (like radio buttons)',
			'like' => 'LIKE text ( will be filterd using a LIKE "%text%" SQL clause',
			'text' => 'EQUAL text ( will be filterd using a  = "text" SQL clause',
		);
		$filterFields = array_flip(array_merge(empty($this->_modelConfig['LIST_FILTERS'])?array():array_keys($this->_modelConfig['LIST_FILTERS']),$this->datasDefs,array_keys(abstractModel::_getModelStaticProp($this->modelType,'hasOne'))));
		foreach($filterFields as $fld=>$val){
			$filterDisplay = isset($this->_modelConfig['LIST_FILTERS'][$fld])?$this->_modelConfig['LIST_FILTERS'][$fld]:null;
			echo $this->formInput("filters[$fld]",$filterDisplay,'select',array('label'=>$fld,"values"=>$filterDisplayOptions,'formatStr'=>'<li>%label %input</li>'))."\n";
			//echo $this->formInput("filters[$fld]",$fld,'checkbox',array('checked'=>$filterDisplay?true:false,'label'=>$fld,'formatStr'=>'<li>%input %label'.$filterDisplayInput.'</li>'))."\n";
		}
	?>
	</ul>
	</div>
	<label>

	</label>
	<br />
	<button type="submit" class="ui-button ui-button-small-disk"><?php echo langManager::msg('save'); ?></button>
	</div>
</form>


<form action="<?php echo $this->url('setFormInputs',array('modelType'=>$this->modelType)) ?>" method="post" id="forms">
	<h3><a name="forms">Forms</a></h3>
	<div id="forms-pannel">
		<div class="ui-state-highlight ui-corner-all" style="padding:5px;">
			<strong>Notes: </strong>
			options have to be passed as valid json as describe in json_decode function, it means that keys and values must be doublequoted.
			<br />
			Exemple: {"values":["value1","value2"],"size":"15"}<br />
			List of possible options as defined in formInput_viewHelper documentation:
			<ul style="padding:0;margin:0 0 0 15px;">
				<li> default is the default value to set if $value is empty.</li>
				<li>multiple,class, size, id, onchange, maxlength, rows, cols, style, checked and disabled are replaced by the corresponding html attributes</li>
				<li> default id will be same as name</li>
				<li> default class will be same as type</li>
				<li> values is an associative list of key value pairs (keys are used as values and values are used as labels) used with select | checkBox | radio</li>
				<li> label is an optional label for the input</li>
				<li> pickerOptStr is used for datepicker or timepicker fields</li>
				<li> pickerOpts   is used for datetimepicker (something like that: {"pickerOpts":["dateOptStr","timeOptStr"]}</li>
				<li> rteOpts      is used for rte options</li>
				<li> uneditable   setted to true will allow field to be filled only at item creation time, and will be disabled the rest of the time.</li>
				<li> sort         is only used for hasMany relations and require a valid modelCollection sort method to be call ({"sort":"rsortByName"}).</li>
				<li>User input validation use our jquery.validable plugin here's how you define your rules.<br />
			All options are optionnals, here a thoose:
			<code>- useIcon   => bool (default: true)
- stateElemt=>'label|self|jQuerySelector'     (self as default)
- rule      => '/regexp or javascript callback validation function name/',
- maxlength => int,
- minlength => int,
- required  => bool (default: false)
- help      => 'message to display next the input as a tooltip'
</code></li>
			</ul>
			<strong>Warning: </strong>Options are not checked for validation so be carrefull to pass a valid json string as define in php json_encode.
		</div>
		<br />
		<div id="fieldList">
			<?php
					if( $this->datasDefs ){
						$types = array('--- default ---','skip','select','selectbuttonset','text','textConfirm','password','passwordConfirm','forcetextarea','textarea','rte','checkbox','radio','hidden','datepicker','timepicker','datetimepicker','file','fileextended','fileentry','codepress');
						$types = array_combine($types,$types);
						$fieldGroupMethod = '';
						if( is_object($this->fieldOrder)){
							foreach($this->fieldOrder as $k=>$group){
								if( $k === 'fieldGroupMethod'){
									$fieldGroupMethod = $group;
									continue;
								}
								#- ~ echo "<fieldset><legend>$group->name</legend>\n";
								if( empty($group->name) ){
									echo '<div class="fieldSet">'
									.'<label class="ui-widget-header">Primary ungrouped inputs</label>'
									.'<input type="hidden" name="fieldsOrder[primary]" value="" />';
								}else{
									echo '<div class="fieldSet" id="fieldSet_'.$k.'">'
									.'<label class="ui-widget-header">Group Name: <input type="text" name="fieldSet[]" value="'.htmlentities($group->name,ENT_COMPAT,'UTF-8').'" /></label>'
									.'<input type="hidden" name="fieldsOrder[]" value="" />';

								}
								if( !empty($group->fields)){
									foreach($group->fields as $f){
										if( $f === $this->primaryKey )
											continue;
										echo "\n\t<div class=\"formInput\">"
											.$this->formInput("inputTypes[$f]",(isset($this->inputTypes[$f])?$this->inputTypes[$f]:null),'select',array('values'=>$types,'label'=>$f,'formatStr'=>'%label %input'))
											.$this->formInput("inputOptions[$f]",(isset($this->inputOptions[$f])?$this->inputOptions[$f]:null),'text',array('size'=>50,'label'=>'options','formatStr'=>' %label %input'))
											."</div>\n";
									}
								}
								echo "\n</div>\n";
								#- ~ echo "</fieldset>\n";
							}
						}else{
							echo '<div class="fieldSet"><label class="ui-widget-header">primary ungrouped inputs</label><input type="hidden" name="fieldsOrder[primary]" value="" />';
							$fields = is_array($this->fieldOrder)?array_keys(array_merge(array_flip($this->fieldOrder),array_flip($this->datasDefs))):$this->datasDefs;
							foreach($fields as $f){
								if( $f === $this->primaryKey)
									continue;
								echo "<div class=\"formInput\">"
									.$this->formInput("inputTypes[$f]",(isset($this->inputTypes[$f])?$this->inputTypes[$f]:null),'select',array('values'=>$types,'label'=>$f,'formatStr'=>'%label %input'))
									.$this->formInput("inputOptions[$f]",(isset($this->inputOptions[$f])?$this->inputOptions[$f]:null),'text',array('size'=>50,'label'=>'options','formatStr'=>' %label %input'))
									."</div>\n";
							}
							echo "\n</div>\n";
						}
					}
				?>
		</div>
		<div class="ui-widget-content ui-corner-all" id="formAddFieldList" style="padding:.4em;">
			add relation fields :
			<?php

				if( $hasOnes = array_keys(abstractModel::_getModelStaticProp($this->modelType,'hasOne')) ){
					echo '<span class="tk-border tk-corner tk-inlineStack tk-content">'
						.implode('</span><span class="tk-border tk-corner tk-inlineStack tk-content">',$hasOnes)
						.'</span>';
				}
				if( $hasManys = array_keys(abstractModel::_getModelStaticProp($this->modelType,'hasMany')) ){
					echo '<span class="tk-border tk-corner tk-inlineStack tk-content">'
						.implode('</span><span class="tk-border tk-corner tk-inlineStack tk-content">',$hasManys)
						.'</span>';
				}

			?>
		</div>
		<br />
		<div id="fieldGroupMethod"<?php echo empty($fieldGroupMethod)?' style="display:none;"':''?> class="ui-widget-content ui-corner-all">Grouping Method:
			<label><input type="radio" name="fieldGroupMethod" value="fieldset"<?php echo 'fieldset'===$fieldGroupMethod?' checked="checked"':''?>/> FieldSet</label>
			<label><input type="radio" name="fieldGroupMethod" value="tabs" <?php echo 'tabs'===$fieldGroupMethod?' checked="checked"':''?>/> Tabs</label>
			<label><input type="radio" name="fieldGroupMethod" value="tabbed" <?php echo 'tabbed'===$fieldGroupMethod?' checked="checked"':''?>/> Tabbed</label>
			<label><input type="radio" name="fieldGroupMethod" value="accordion" <?php echo 'accordion'===$fieldGroupMethod?' checked="checked"':''?>/> Accordion</label>
			<label><input type="radio" name="fieldGroupMethod" value="" <?php echo empty($fieldGroupMethod)?' checked="checked"':''?>/> none</label>
		</div>
		<div class="ui-buttonset ui-buttonset-small">
			<button type="button" id="resetFieldsOrder" class="ui-button ui-button-arrowreturnthick-1-w"><?php echo langManager::msg('Reset fields orders settings')?></button>
			<button type="button" id="addFieldSet" class="ui-button ui-button-circle-plus"><?php echo langManager::msg('Add input group container')?></button>
			<button type="submit" class="ui-button ui-button-disk"><?php echo langManager::msg('save'); ?></button>
		</div>
	</div>
</form>

<form action="<?php echo $this->url('setMessages',array('modelType'=>$this->modelType)) ?>" method="post" id="messages">
	<h3><a name="messages">Field names translations</a></h3>
	<div id="messages-pannel">
	<?php
		echo $this->formInput('setLang',langManager::getDefaultLang(),'select',array('values'=>$this->langs,'label'=>'Display Lang'));
		if( $this->idMsgs ){
			foreach($this->idMsgs as $lang=>$ids){
				echo "<div id=\"langMessages_$lang\" class=\"langMessages ui-widget-content ui-corner-all\"><h2 class=\"ui-widget-header\">$lang messages</h2>";
				foreach($ids as $f){
					if( $f === $this->primaryKey)
						continue;
					echo $this->formInput("msgs[$lang][".str_replace(']','\]',$f).']',(isset($this->messages[$lang][$f])?$this->messages[$lang][$f]:null),'text',array('label'=>$f,'formatStr'=>'<div class="formInput">%label: %input <small>( default: '.langManager::msg($f,null,"adminmodels|default",$lang)." )</small></div>\n"));
				}
				echo "</div>";
			}
		}
	?>
	<br />
	<div class="ui-buttonset ui-buttonset-small">
		<button type="button" class="ui-button ui-button-circle-plus" id="addTranslationField"><?php echo langManager::msg('add new field name translation'); ?></button>
		<button type="submit" class="ui-button ui-button-disk"><?php echo langManager::msg('save'); ?></button>
	</div>
	</div>
</form>

<form action="<?php echo $this->url('saveEditConfig',array('modelType'=>$this->modelType)) ?>" method="post" id="config">
	<h3><a name="config">Edit Configuration File</a></h3>
	<div id="config-pannel">
		<?php /*= $this->editarea('smvcConfig',file_get_contents($this->configFile),array('syntax'=>'js','min_width'=>'700',"min_height"=>'350','display'=>'later')) */?>
		<?php echo $this->codemirror('smvcConfig',file_get_contents($this->configFile),array('language'=>'js')) ?>
		<button type="submit" class="ui-button ui-button-small-disk">save</button>
	</div>
</form>

<form action="<?php echo $this->url('saveEditModel',array('modelType'=>$this->modelType)) ?>" method="post" id="model">
	<h3><a name="model">Edit Model File</a></h3>
	<div id="model-pannel">
		<div class="ui-state-highlight ui-corner-all" style="padding:5px;">
			<strong>ModelsAddons</strong>
			<ul id="modelAddonList">
				<li>activable:
				<code>class activableModel extends BASE_activableModel{
	//** list of activalbe fields must be real dataField not a relName (neither hasOne or hasMany)
	public $_activableFields = array('active');
	static protected $modelAddons = array('activable');
}</code>
				</li>
				<li>formatTime</li>
				<li>frDate:
				<code>class frDateModel extends BASE_frDateModel{
	static protected $modelAddons = array('frDate');
}</code>
				</li>
				<li>mpTreeTraversal:
				<code>class sample_mpTreeTraversalNode implements mpTreeTraversalModelAddonInterface{
	static protected $treeFields = array(
		'right'=>'rightId','left'=>'leftId','id'=>'Id','level'=>'level',//'parent'=>'parentId'
	);
	static function getNew(){
		return self::newNode();
	}
	static function newNode(array $datas=null){
		return mpTreeTraversalModelAddon::newModelNode(self::$modelName,$datas);
	}
	static function getTreeCollection($startnode=FALSE,$depth=FALSE,$removeStartNode=false,$fromDB=false){
		return mpTreeTraversalModelAddon::getModelTreeCollection(self::$modelName,$startnode,$depth,$removeStartNode,$fromDB);
	}
	static function HtmlOptions($labelFld,$selected=null,$removed=null,$startnode=null,$depth=null){
		return mpTreeTraversalModelAddon::modelHtmlOptions(self::$modelName,$labelFld,$selected,$removed,$startnode,$depth);
	}
	function delete(){
		#- ~ $clone = clone $this;
		$this->_modelAddons['mpTreeTraversal']->removeNode();
		parent::delete();
	}
}</code>
				</li>
				<li>multilingual:
				<code>class multilingualModel extends BASE_multilingualModel{
	static protected $modelAddons = array('multilingual');
  static public $_multilingualFieldScheme = ':name_:lc';
}</code>
				</li>
				<li>orderable:
				<code>class orderableModel extends BASE_orderableModel{
	//** must be a real dataField not a relName (neither hasOne or hasMany)
	public $_orderableField = 'ordre';
	//** may be a real dataField or a hasOne relName (only with a localField in relDef) but must not be an hasMany one
	//** this one is optional
	public $_orderableGroupField = false;
	static protected $modelAddons = array('orderable');
}</code>
				</li>
				<li>rssItem</li>
				<li>tagCloud</li>
				<li>withCreateDate:
				<code>class mymodel extends BASE_mymodel{
	static protected $modelAddons = array('withCreateDate');
	public $createDateField = 'createAt';
	public $createDateStr   = 'Y-m-d H:i:s';
}</code>
				</li>
				<li>withUpdateDate:
				<code>class mymodel extends BASE_mymodel{
	static protected $modelAddons = array('withUpdateDate');
	static public $_updateDateFields = array('dateUpdate'=>'Y-m-d H:i:s');
}</code>
				</li>
			</ul>
		</div>
		<?php /* = $this->editarea('smvcModel',file_get_contents($this->modelFile),array('syntax'=>'php','min_width'=>'700',"min_height"=>'350','display'=>'later')) */ ?>
		<?php echo $this->codemirror('smvcModel',file_get_contents($this->modelFile),array('language'=>'php')) ?>
		<button type="submit" class="ui-button ui-button-small-disk">save</button>
	</div>
</form>
<a  href="<?php echo $this->listUrl ?>" class="ui-button ui-button-arrowreturnthick-1-w" style="float:right;"><?php echo langManager::msg('back to list'); ?></a>
<div class="ui-helper-clearfix"></div>
</div>


<?php
echo '<script type="text/javascript">
var ADMIN_MODEL_URL = "'.$this->url('resetFieldsOrder',array('modelType'=>$this->modelType)).'",
	langMessages = {
		"listFldName": "'.addslashes(langManager::msg('list field name')).'",
		"translationMsgId": "'.addslashes(langManager::msg('message id')).'"
	};
</script>';
$this->js("js/simpleMVC_adminConfig.js",'jqueryui');
?>
