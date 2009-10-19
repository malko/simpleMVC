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
</style>
<h1><?=$this->modelType?> settings </h1>
<div id="settings">
<form action="<?= $this->url('setToString',null,array('modelType'=>$this->modelType)) ?>" method="post" id="string">
	<h3><a name="string"><?= $this->modelType ?>::$__toString</a></h3>
	<div id="string-pannel">
	<div class="selectors">
	<?php
		if( $this->datasFields )
			echo "List of datas Fields: $this->datasFields<br />";
		if( $this->hasOnes )
			echo "List of hasOne relations : $this->hasOnes<br />";
	?>
	</div>
	<label>
		<?= $this->modelType ?>::$__toString
		<input type="text" name="_toStr" value="<?=htmlentities($this->_toStr,ENT_COMPAT,'UTF-8')?>" />
	</label>
	<br />
	<button type="submit" class="ui-button ui-button-small-disk"><?= langManager::msg('save'); ?></button>
	</div>
</form>

<form action="<?= $this->url('setList',null,array('modelType'=>$this->modelType)) ?>" method="post"  id="list">
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
			<button type="button" class="ui-button ui-button-circle-plus" id="listAddField"><?= langManager::msg('add list field')?></button>
			<button type="submit" class="ui-button ui-button-disk"><?= langManager::msg('save'); ?></button>
		</div>
	</div>
</form>

<form action="<?= $this->url('setFormInputs',null,array('modelType'=>$this->modelType)) ?>" method="post" id="forms">
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
			</ul>
			<strong>Warning: </strong>Options are not checked for validation so be carrefull to pass a valid json string as define in php json_encode.
		</div>
		<br />
		<div id="fieldList">
			<?php
					if( $this->datasDefs ){
						$types = array('--- default ---','skip','select','selectbuttonset','text','textConfirm','password','passwordConfirm','forcetextarea','textarea','rte','checkbox','radio','hidden','datepicker','timepicker','datetimepicker','file','codepress');
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
		<br />
		<div id="fieldGroupMethod"<?=empty($fieldGroupMethod)?' style="display:none;"':''?> class="ui-widget-content ui-corner-all">Grouping Method:
			<label><input type="radio" name="fieldGroupMethod" value="fieldset"<?= 'fieldset'===$fieldGroupMethod?' checked="checked"':''?>/> FieldSet</label>
			<label><input type="radio" name="fieldGroupMethod" value="tabs" <?= 'tabs'===$fieldGroupMethod?' checked="checked"':''?>/> Tabs</label>
			<label><input type="radio" name="fieldGroupMethod" value="accordion" <?= 'accordion'===$fieldGroupMethod?' checked="checked"':''?>/> Accordion</label>
		</div>
		<div class="ui-buttonset ui-buttonset-small">
			<button type="button" id="resetFieldsOrder" class="ui-button ui-button-arrowreturnthick-1-w"><?php echo langManager::msg('Reset fields orders settings')?></button>
			<button type="button" id="addFieldSet" class="ui-button ui-button-circle-plus"><?php echo langManager::msg('Add input group container')?></button>
			<button type="submit" class="ui-button ui-button-disk"><?= langManager::msg('save'); ?></button>
		</div>
	</div>
</form>

<form action="<?= $this->url('setValidations',null,array('modelType'=>$this->modelType)) ?>" method="post" id="validations">
	<h3><a name="validations">User input validations</a></h3>
	<div id="validations-pannel">
		<div class="ui-state-highlight ui-corner-all" style="padding:5px;">
			<strong>Notes: </strong>
			<br />
			User input validation use our jquery.validable plugin here's how you define your rules.<br />
			All options are optionnals, here a sample exemple:
			<code>array(
  'stateElemt'=>'label'
	'useIcon'   => true
	'rules'     => array(
      <span id="validableTemplate">'inputName' => array(
      'rule'      => '/regexp or javascript callback validation function name/',
      'maxlength' => int,
      'minlength' => int,
      'required'  => bool,
      'help'      => 'message to display next the input'
    ),</span>
  )
)
</code>
<div id="validableTags"> name of user inputs available: <br /></div>
<script>
//-- add here quick rule helper
var validableTags=[];
$(':input[id^=inputType]').each(function(){
	validableTags.push(this.id.replace(/^[^\[]+\[([^\]]+)\]$/,'$1'));
});
$('#validableTags').append(validableTags.join(',&nbsp;'));
</script>
		</div>
	<?php
		echo $this->editarea(
			'validableRules',
			(empty($_POST['validableRules'])?(empty($this->_modelConfig["VALIDATION"])?'':var_export($this->_modelConfig["VALIDATION"],1)):$_POST['validableRules']),
			array('syntax'=>'js','min_width'=>'700',"min_height"=>'350','display'=>'later')
		);
	?>
	<br />
	<button type="submit" class="ui-button ui-button-disk"><?= langManager::msg('save'); ?></button>
	</div>
</form>

<form action="<?= $this->url('setMessages',null,array('modelType'=>$this->modelType)) ?>" method="post" id="messages">
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
		<button type="button" class="ui-button ui-button-circle-plus" id="addTranslationField"><?= langManager::msg('add new field name translation'); ?></button>
		<button type="submit" class="ui-button ui-button-disk"><?= langManager::msg('save'); ?></button>
	</div>
	</div>
</form>

<form action="<?= $this->url('setActions',null,array('modelType'=>$this->modelType)) ?>" method="post" id="actions">
	<h3><a name="actions">Allowed Actions</a></h3>
	<div id="actions-pannel">
	Choose allowed actions to manage this model
	<?php
		extract($this->_allowedActions);
		echo $this->formInput('actions[edit]',empty($edit)?0:1,'radio',array('label'=>'Can be edited','values'=>array('no','yes')));
		echo $this->formInput('actions[add]',empty($add)?0:1,'radio',array('label'=>'Can be added','values'=>array('no','yes')));
		echo $this->formInput('actions[del]',empty($del)?0:1,'radio',array('label'=>'Can be deleted','values'=>array('no','yes')));
		echo $this->formInput('actions[list]',empty($list)?0:1,'radio',array('label'=>'Can be listed','values'=>array('no','yes')));
	?>
	<br />
	<button type="submit" class="ui-button ui-button-small-disk"><?= langManager::msg('save'); ?></button>
	</div>
</form>

<form action="<?= $this->url('saveEditConfig',null,array('modelType'=>$this->modelType)) ?>" method="post" id="config">
	<h3><a name="config">Edit Configuration File</a></h3>
	<div id="config-pannel">
		<?= $this->editarea('smvcConfig',file_get_contents($this->configFile),array('syntax'=>'js','min_width'=>'700',"min_height"=>'350','display'=>'later')) ?>
		<button type="submit" class="ui-button ui-button-small-disk">save</save>
	</div>
</form>

<form action="<?= $this->url('saveEditModel',null,array('modelType'=>$this->modelType)) ?>" method="post" id="model">
	<h3><a name="model">Edit Model File</a></h3>
	<div id="model-pannel">
		<?= $this->editarea('smvcModel',file_get_contents($this->modelFile),array('syntax'=>'php','min_width'=>'700',"min_height"=>'350','display'=>'later')) ?>
		<button type="submit" class="ui-button ui-button-small-disk">save</save>
	</div>
</form>
<a  href="<?= $this->listUrl ?>" class="ui-button ui-button-arrowreturnthick-1-w" style="float:right;"><?= langManager::msg('back to list'); ?></a>
<div class="ui-helper-clearfix"></div>
</div>


<?php
$this->js('
	var styleClass = "ui-widget-content ui-corner-all";

	//-- make this an accordion
	var formsIds = {"string":0,"list":1,"forms":2,"validations":3,"messages":4,"actions":5,"config":6,"model":7};
	var activeForm =  window.location.href.match(/#\/?(\w+)/);
	activeForm = (null!==activeForm && activeForm.length)?formsIds[activeForm[1]]:1;
	$("#settings").accordion({header:"h3",autoHeight:false,animated:false,active:activeForm,collapsible:true});

	// make fieldname clickable to ease _toStr setting
	$(".sMVC_dataField").click(function(){
		var v = $("input[name=_toStr]").val();
		$("input[name=_toStr]").val((v?v+" ":"")+this.innerHTML).focus();
	}).css({cursor:"pointer"});

	//-- make list fields sortable
	$("ul.sortable").sortable({
		placeholder: "ui-state-highlight placeholder ui-corner-all",
		forcePlaceholderSize:true
	}).find("li").addClass(styleClass);
	//-- add items to list of fields
	function listAddItem(fieldName,format){
		var item = $(\'<li><input id="fields[\'+fieldName+\']" name="fields[\'+fieldName+\']" class="checkbox" type="checkbox" checked="checked" value="\'+fieldName+\'"/>\\
		<label for="fields[\'+fieldName+\']">\'+fieldName+\'</label> \\
		<div class="formInput"><label for="formatStr[\'+fieldName+\']">format string</label><input id="formatStr[\'+fieldName+\']" class="text" type="text" size="50" value="" name="formatStr[\'+fieldName+\']"/> \\
		</div>\').appendTo("#listItems").addClass(styleClass);
		item.find(".ui-button").button().css({float:"right"}).click(function(){$(this).parent("li").remove();});
	}
	$("#listAddField").click(function(){
		var fieldName = prompt("'.addslashes(langManager::msg('list field name')).'");
		if(! fieldName)
			return;
		listAddItem(fieldName,"");
	});

	//-- manage ordering and grouping of forms inputs
	function updateFieldSets(){
		$("#fieldList .fieldSet").each(function(){
			var val = new Array();
			var labs = $(this).find(".formInput label:first-child");
			labs.each(function(){val.push(this.innerHTML);});
			$(this).find("input[type=hidden][name^=fieldsOrder]").val(val.join(","));
		});
	}
	$.fn.connectInputSortList = function(){
		$(this).addClass(styleClass).sortable({
				placeholder: "ui-state-highlight placeholder ui-corner-all",
				forcePlaceholderSize:true,
				dropOnEmpty:true,
				items:".formInput",
				scrollSensitivity: 40,
				update:updateFieldSets
			}).find(".formInput").addClass(styleClass);
			var header = $(".ui-widget-header",this);
			$(\'<span class="ui-icon ui-icon-close"></span>\').prependTo(header).click(function(){
				if( $(this).parents(".fieldSet").children(".formInput").length < 1)
					$(this).parents(".fieldSet").remove();
				else
					alert("Can\'t remove a not empty group");
			});
		$("#fieldList .fieldSet").sortable("option", "connectWith", "#fieldList .fieldSet");
	};
	$("#fieldList").sortable({
		placeholder: "ui-state-highlight placeholder ui-corner-all",
		forcePlaceholderSize:true,
		items:".fieldSet",
		handle:".ui-widget-header"
	}).find(".fieldSet").connectInputSortList();
	$("#addFieldSet").click(function(){
		$(\'<div class="fieldSet" id="fieldSet_\'+($("#forms .fieldSet").length+1)+\'">\
			<label class="ui-widget-header">Group Name: <input type="text" name="fieldSet[]" value="" /></label>\
			<input type="hidden" name="fieldsOrder[]" value="" />\
		</div>\').appendTo("#fieldList").connectInputSortList();
		$("#forms #fieldGroupMethod").show();
	});
	updateFieldSets();
	//- manage fields order reset button
	$("#resetFieldsOrder").click(function(){
		if( confirm("Are You sure you want to reset form fields order?") ){
			window.location="'.$this->url('resetFieldsOrder',null,array('modelType'=>$this->modelType,'#'=>'forms')).'";
		}
	});

	//-- manage lang pannel
	$("select#setLang").change(function(){
		var l = this.options[this.selectedIndex].innerHTML;
		$("div.langMessages").hide();
		$("div#langMessages_"+l).show();
	}).change();
	$("#addTranslationField").click(function(){
		var fName = prompt("'.addslashes(langManager::msg('message id:')).'");
		if(! fName)
			return;
		// detection des langues possibles
		$("select#setLang option").each(function(i){
			var l = this.innerHTML;
			$("#langMessages_"+l).append("<div class=\"formInput\">"+fName+": <input type=\"text\" name=\"msgs["+l+"]["+fName+"]\" value=\"\" /></div>");
		});
	});


	//-- add form option client side validation

	//--- test options are valid json
	$("#forms input[id^=inputOptions]").keyup(function(){
		var val = $(this).val();
		if( val.length<1)
			return $(this).removeClass("ui-state-error");
		if( (/[^,:\\{\\}\\[\\]0-9.\\-+Eaeflnr-u \\n\\r\\t]/.test(val.replace(/"(\\\\.|[^"\\\\])*"/g, ""))) )
			return $(this).addClass("ui-state-error");
		var  e;
		try{
			var test =  eval("(" + $(this).val() + ")");
			$(this).removeClass("ui-state-error");
		}catch(e){
			$(this).addClass("ui-state-error");
		}
	});
','jqueryui');
?>
