<h1>FormBuilder</h1>

<div style="text-align:right">
	<a href="<?php echo $this->url('formbuilder:new'); ?>" class="ui-button ui-button-circle-plus" id="adminListAddNew" tabindex="0"><?php echo msg('add new form'); ?></a>
</div>
<?php

if( (! $this->fbdFormList) || $this->fbdFormList->isEmpty() ){
	echo '<div style="font-weight:bold;color:silver;">'.msg('empty form list').'</div>';
}else{

}