<style>
	#settings form{ padding:0;margin:0;}
	#settings .sortable { padding:0;margin:0;}
	#settings .sortable li{ list-style-type:none; list-style-image:none;margin:5px 0;padding:5px;}
	#settings .sortable .placeholder{height:3em;}
	#settings input{white-space:pre;}
	div.fieldSet .ui-widget-header{ display:block;}
	div.fieldSet .ui-widget-header, div.fieldSet, div.formInput{ padding:5px;margin-bottom:5px;}
	div.fieldSet .ui-widget-header .ui-icon{ float:right;}
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
	<button type="submit" class="ui-button-disk"><?= langManager::msg('save'); ?></button>
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
		<ul id="fldList" class="sortable">
		<?php
			if( $this->datasDefs ){
				foreach($this->datasDefs as $fld){
					$formatInput = $this->formInput("formatStr[$fld]",isset($this->listedFields[$fld])?htmlentities($this->listedFields[$fld]):null,'text',array('label'=>'format string','size'=>'50'));
					echo $this->formInput("fields[$fld]",$fld,'checkbox',array('checked'=>isset($this->listedFields[$fld]),'label'=>$fld,'formatStr'=>'<li>%input %label'.$formatInput.'</li>'))."\n";
				}
			}
		?>
		</ul>
		<br />
		<button type="submit" class="ui-button-disk"><?= langManager::msg('save'); ?></button>
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
			</ul>
			<strong>Warning: </strong>Options are not checked for validation so be carrefull to pass a valid json string as define in php json_encode.
		</div>
		<br />
		<div id="fieldList">
			<?php
					if( $this->datasDefs ){
						$types = array('--- default ---','skip','select','text','textConfirm','password','passwordConfirm','forcetextarea','textarea','rte','checkbox','radio','hidden','datepicker','timepicker','datetimepicker','file','codepress');
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
		<div class="ui-buttonset">
			<button type="button" id="addFieldSet" class="ui-button">Create an input group container (fieldset)</button>
			<button type="submit" class="ui-button-disk"><?= langManager::msg('save'); ?></button>
		</div>
	</div>
</form>

<form action="<?= $this->url('setMessages',null,array('modelType'=>$this->modelType)) ?>" method="post" id="messages">
	<h3><a name="messages">Field names</a></h3>
	<div id="messages-pannel">
	<?php
		echo $this->formInput('setLang',langManager::getDefaultLang(),'select',array('values'=>$this->langs,'label'=>'Display Lang'));
		if( $this->idMsgs ){
			foreach($this->idMsgs as $lang=>$ids){
				echo "<div id=\"langMessages_$lang\" class=\"langMessages ui-widget-content ui-corner-all\"><h2 class=\"ui-widget-header\">$lang messages</h2>";
				foreach($ids as $f){
					if( $f === $this->primaryKey)
						continue;
					echo $this->formInput("msgs[$lang][".str_replace(']','\]',$f).']',(isset($this->messages[$lang][$f])?$this->messages[$lang][$f]:null),'text',array('label'=>$f))."\n";
				}
				echo "</div>";
			}
		}
	?>
	<br />
	<button type="submit" class="ui-button-disk"><?= langManager::msg('save'); ?></button>
	</div>
</form>

<form action="<?= $this->url('setActions',null,array('modelType'=>$this->modelType)) ?>" method="post" id="actions">
	<h3><a name="actions">Allowed Actions</a></h3>
	<div id="actions-pannel">
	Choose allowed actions to manage this model
	<?php
		if( isset($this->config['ACTION_'.$this->modelType])){
			extract(json_decode($this->config['ACTION_'.$this->modelType],true));
		}else{
			$add = $edit = $list = $del = 1;
		}
		echo $this->formInput('actions[edit]',empty($edit)?0:1,'radio',array('label'=>'Can be edited','values'=>array('no','yes')));
		echo $this->formInput('actions[add]',empty($add)?0:1,'radio',array('label'=>'Can be added','values'=>array('no','yes')));
		echo $this->formInput('actions[del]',empty($del)?0:1,'radio',array('label'=>'Can be deleted','values'=>array('no','yes')));
		echo $this->formInput('actions[list]',empty($list)?0:1,'radio',array('label'=>'Can be listed','values'=>array('no','yes')));
	?>
	<br />
	<button type="submit" class="ui-button-disk"><?= langManager::msg('save'); ?></button>
	</div>
</form>

<form action="<?= $this->url('saveEditConfig',null,array('modelType'=>$this->modelType)) ?>" method="post" id="config">
	<h3><a name="config">Edit Configuration File</a></h3>
	<div id="config-pannel">
		<?= $this->editarea('smvcConfig',file_get_contents($this->configFile),array('syntax'=>'js','min_width'=>'700',"min_height"=>'350','display'=>'later')) ?>
		<button type="submit" class="ui-button-disk">save</save>
	</div>
</form>

<form action="<?= $this->url('saveEditModel',null,array('modelType'=>$this->modelType)) ?>" method="post" id="model">
	<h3><a name="model">Edit Model File</a></h3>
	<div id="model-pannel">
		<?= $this->editarea('smvcModel',file_get_contents($this->modelFile),array('syntax'=>'php','min_width'=>'700',"min_height"=>'350','display'=>'later')) ?>
		<button type="submit" class="ui-button-disk">save</save>
	</div>
</form>
<a  href="<?= $this->listUrl ?>" class="ui-button-arrowreturnthick-1-w" style="float:right;"><?= langManager::msg('back to list'); ?></a>
<div class="ui-helper-clearfix"></div>
</div>


<?php
$this->js('
	var styleClass = "ui-widget-content ui-corner-all";

	//-- make this an accordion
	var formsIds = {"string":0,"list":1,"forms":2,"messages":3,"actions":4,"config":5,"model":6};
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

	//-- manage lang pannel
	$("select#setLang").change(function(){
		var l = this.options[this.selectedIndex].innerHTML;
		$("div.langMessages").hide();
		$("div#langMessages_"+l).show();
	}).change();
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
