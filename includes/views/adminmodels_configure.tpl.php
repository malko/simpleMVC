<style>
	#settings form{ padding:0;margin:0;}
	#settings #fldList { padding:0;margin:0;}
	#settings #fldList li{ list-style-type:none; list-style-image:none;margin:5px 0;}
	#settings #fldList .placeholder{height:3em;}
	#settings .formInput{padding:5px;}
	#settings input {white-space:pre;}
</style>
<h1><?=$this->modelType?> settings </h1>
<div id="settings">
<form action="<?= $this->url('setToString',null,array('modelType'=>$this->modelType)) ?>" method="post" id="string">
	<h3><a name="string"><?= $this->modelType ?>::$__toString</a></h3>
	<div>
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
		<input type="text" name="_toStr" value="<?=$this->_toStr?>" />
	</label>
	<br />
	<input type="submit" value="<?= langManager::msg('save'); ?>" class="noSize" />
	</div>
</form>

<form action="<?= $this->url('setList',null,array('modelType'=>$this->modelType)) ?>" method="post"  id="list">
	<h3><a name="list">List</a></h3>
	<div>
		<div class="ui-state-highlight ui-corner-all" style="padding:5px;">
			Check fields you want to be displayed in adminModels list actions.
			<br />
			You also can specify an optional format string to render this field in the list, @see abstractModel::__toString() documentation for more infos on what can be put here.
			<br />
			Field list order may be arrange by simple drag&drop.
		</div>
		<ul id="fldList">
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
		<input type="submit" value="<?= langManager::msg('save'); ?>" class="noSize" />
	</div>
</form>

<form action="<?= $this->url('setFormInputs',null,array('modelType'=>$this->modelType)) ?>" method="post" id="forms">
	<h3><a name="forms">Forms</a></h3>
	<div>
		<div class="ui-state-highlight ui-corner-all" style="padding:5px;">
			<strong>Notes: </strong>
			options have to be passed as valid json as describe in json_decode function, it means that keys and values must be doublequoted.
			<br />
			Exemple: {"values":["value1","value2"],"size":"15"}<br />
			List of possible options as defined in formInput_viewHelper documentation:
			<ul style="padding:0;margin:0 0 0 15px;">
				<li> default is the default value to set if $value is empty.</li>
				<li>
				multiple,class, size, id, onchange, maxlength, rows, cols, style, checked and disabled are replaced by the corresponding html attributes</li>
				<li> default id will be same as name</li>
				<li> default class will be same as type</li>
				<li> values is an associative list of key value pairs (keys are used as values and values are used as labels) used with select | checkBox | radio</li>
				<li> label is an optional label for the input</li>
				<li> pickerOptStr is used for datepicker or timepicker fields</li>
				<li> pickerOpts   is used for datetimepicker (something like that: {"pickerOpts":["dateOptStr","timeOptStr"]}</li>
				<li> rteOpts      is used for rte options</li>
				<li> uneditable   setted to true will allow field to be filled only at item creation time, and will be disabled the rest of the time. 
			</ul>
			<strong>Warning: </strong>Options are not checked for validation so be carrefull to pass a valid json string as define in php json_encode.
		</div>
		<br />
		<div>
		<?php
			if( $this->datasDefs ){
				$types = array('--- default ---','skip','select','text','password','forcetextarea','textarea','rte','checkbox','radio','hidden','datepicker','timepicker','datetimepicker','file','codepress');
				$types = array_combine($types,$types);
				foreach($this->datasDefs as $f){
					if( $f === $this->primaryKey)
						continue;
					echo "<div class=\"formInput\">"
						.$this->formInput("inputTypes[$f]",(isset($this->inputTypes[$f])?$this->inputTypes[$f]:null),'select',array('values'=>$types,'label'=>$f,'formatStr'=>'%label %input'))
						.$this->formInput("inputOptions[$f]",(isset($this->inputOptions[$f])?$this->inputOptions[$f]:null),'text',array('size'=>50,'label'=>'options','formatStr'=>' %label %input'))
						."</div>\n";
				}
			}
		?>
		<br />
		<input type="submit" value="<?= langManager::msg('save'); ?>" class="noSize" />
		</div>
	</div>
</form>

<form action="<?= $this->url('setMessages',null,array('modelType'=>$this->modelType)) ?>" method="post" id="messages">
	<h3><a name="messages">Field names</a></h3>
	<div>
	<?php
		echo $this->formInput('setLang',langManager::getDefaultLang(),'select',array('values'=>$this->langs,'label'=>'Display Lang'));
		if( $this->idMsgs ){
			foreach($this->idMsgs as $lang=>$ids){
				echo "<div id=\"langMessages_$lang\" class=\"langMessages\"><h2>$lang messages</h2>";
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
	<input type="submit" value="<?= langManager::msg('save'); ?>" class="noSize" />
	</div>
</form>
</div>
<input type="reset" onclick="window.location = '<?= $this->listUrl ?>';"; value="<?= langManager::msg('back'); ?>"  class="noSize"/>


<?php
$this->js('
	//-- make this an accordion
	$("#settings").accordion({header:"h3",autoHeight:false,animated:false,active:false,collapsible:true});

	// make fieldname clickable to ease _toStr setting
	$(".sMVC_dataField").click(function(){
		var v = $("input[name=_toStr]").val();
		$("input[name=_toStr]").val((v?v+" ":"")+this.innerHTML);
	}).css({cursor:"pointer"});

	//-- make list fields sortable
	$("#fldList").sortable({
		placeholder: "ui-state-highlight placeholder ui-corner-all",
		forcePlaceholderSize:true
	}).find("li").addClass("ui-widget-content ui-corner-all");

	//-- manage lang pannel
	$("select#setLang").change(function(){
		var l = this.options[this.selectedIndex].innerHTML;
		$("fieldset div.langMessages").hide();
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
