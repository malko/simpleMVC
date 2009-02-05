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
				echo $this->formInput('fields',$this->listedFields,'checkbox',array('values'=>$values))."<br />\n";
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
		<div>
		<?php
			if( $this->datasDefs ){
				$types = array('--- default ---','skip','select','text','password','textarea','checkbox','radio','hidden','datepicker','timepicker','datetimepicker','file','codepress');
				$types = array_combine($types,$types);
				foreach($this->datasDefs as $f){
					if( $f === $this->primaryKey)
						continue;
					echo $this->formInput($f,(isset($this->formSettings[$f])?$this->formSettings[$f]:null),'select',array('values'=>$types,'label'=>$f))."<br />\n";
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
						echo $this->formInput("msgs[$lang][".str_replace(']','\]',$f).']',(isset($this->messages[$lang][$f])?$this->messages[$lang][$f]:null),'text',array('label'=>$f))."<br />\n";
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
	$("fieldset#forms legend").click();
	$("label",$("fieldset").not(":first, :last")).css("display","block");
	$("select#setLang").change(function(){
		var l = this.options[this.selectedIndex].innerHTML;
		$("fieldset div.langMessages").hide();
		$("div#langMessages_"+l).show();
	}).change();

');
?>
