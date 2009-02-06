<form action="<?= $this->url('setToString',null,array('modelType'=>$this->modelType)) ?>" method="post">
	<fieldset id="string">
		<legend>Setting how to display <?= $this->modelType ?> as string</legend>
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
	</fieldset>
</form>

<form action="<?= $this->url('setList',null,array('modelType'=>$this->modelType)) ?>" method="post">
	<fieldset id="list">
		<legend>Setting <?= $this->modelType ?> fields to display in list</legend>
		<div>
		<?php
			if( $this->datasDefs ){
				$values = array_combine($this->datasDefs,$this->datasDefs);
				echo $this->formInput('fields',$this->listedFields,'checkbox',array('values'=>$values))."\n";
			}
		?>
		<br />
		<input type="submit" value="<?= langManager::msg('save'); ?>" class="noSize" />
		</div>
	</fieldset>
</form>

<form action="<?= $this->url('setFormInputs',null,array('modelType'=>$this->modelType)) ?>" method="post">
	<fieldset id="forms">
		<legend>Setting how to display forms</legend>
		<div style="background:#ffc;padding:5px;border:solid silver 1px;color:#555;">
			<strong>Notes: </strong>
			options have to be passed as valid json as describe in json_decode function, it means that keys and values must be doublequoted.
			<br />
			Exemple: {"values":["value1","value2"],"width":"160"}<br />
			List of possible options as defined in formInput_viewHelper documentation:
			<ul style="padding:0;margin:0 0 0 15px;">
				<li> default is the default value to set if $value is empty.</li>
				<li> multiple,class, size, id, onchange, maxlength, disabled are replaced by the corresponding html attributes</li>
				<li> default id will be same as name</li>
				<li> default class will be same as type</li>
				<li> values is an associative list of key value pairs (keys are used as values and values are used as labels) used with select | checkBox | radio</li>
				<li> label is an optional label for the input</li>
				<li> pickerOptStr is used for datepicker and timepicker fields</li>
				<li> pickerOpts   is used for datetimepicker (something like that: array(0=>dateOptStr,1=>timeOptStr))</li>
			</ul>
			<strong>Warning: </strong>Options are not checked for validation so be carrefull to pass a valid json string as define in php json_encode.
		</div>
		<br />
		<div>
		<?php
			if( $this->datasDefs ){
				$types = array('--- default ---','skip','select','text','password','textarea','checkbox','radio','hidden','datepicker','timepicker','datetimepicker','file','codepress');
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
	</fieldset>
</form>

<form action="<?= $this->url('setMessages',null,array('modelType'=>$this->modelType)) ?>" method="post">
	<fieldset id="messages">
		<legend>Setting how to display field names</legend>
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
	</fieldset>
</form>

<input type="reset" onclick="window.location = '<?= $this->listUrl ?>';"; value="<?= langManager::msg('back'); ?>"  class="noSize"/>


<?php
$this->_js_script('
	// make fieldname clickable to ease _toStr setting
	$(".sMVC_dataField").click(function(){
		$("input[name=_toStr]").val($("input[name=_toStr]").val()+" "+this.innerHTML);
	}).css({cursor:"pointer"});
	// create sort of accordions
	$("fieldset").css({border:"solid black 1px",background:"#fff"});
	$("fieldset legend").click(function(){
		$("fieldset legend").not(this).siblings("div").slideUp("fast");
		$(this).siblings().slideDown("fast");
	}).css({fontWeight:"bold",cursor:"pointer"})
	$("fieldset#list legend").click();
	$("label",$("fieldset#list")).css("display","block");
	$("select#setLang").change(function(){
		var l = this.options[this.selectedIndex].innerHTML;
		$("fieldset div.langMessages").hide();
		$("div#langMessages_"+l).show();
	}).change();

');
?>
