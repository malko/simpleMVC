<?php
$this->helperLoad('button');
$dateDeb = date("Y-m-d", strtotime( '- 14 days' ));

?>
<h1><?php echo $this->pageTitle; ?></h1>
<form id="exportCsv" style="margin:0 auto;background-color:#F5F5F5;" action="<?php echo $this->url(':csv'); ?>" method="POST" class="tk-validable tk-validable-req-noIcon" >
	<table cellspacing="0" cellpadding="2" border="0" id="sortTableformBuilderResults" class="adminList">
		<td>
			<label for="formId"><?php echo msg("select form"); ?></label>
			<select id="formId" name="formId">
				<option value="">--Formulaire--</option>
				<?php echo $this->forms->htmlOptions('%name'); ?>
			</select>
		</td>
		<td>
			<?php
			echo $this->modelFormInput('formBuilderResults','dateDeb',array(
				'forceEmptyChoice'=>true
				,"type"=>"datepicker"
				,"value"=>$dateDeb
				,'label'=>msg('resultDateStart')
			));
			?>
		</td>
		<td>
			<?php
				echo $this->modelFormInput('formBuilderResults','dateFin',array(
					'forceEmptyChoice'=>true
					,"type"=>"datepicker"
					,"value"=>date('Y-m-d')
					,'label'=>msg('resultDateEnd')
				));
			?>
		</td>
		<td style="text-align: right">
			<button class="ui-button ui-button-disk exportButton" title="Exporter"></button>
		</td>
	</tr>
	</table>
</form>

<?php

$this->validable('formId',array('required'=>true),'#exportCsv');
$this->validable('dateDeb',array('required'=>true,'rule'=>'function(v){
	if(v > $("#dateFin").val())
		return false;
	else
		return true;
}'),'#exportCsv');
$this->validable('dateFin',array('required'=>true,'rule'=>'function(v){
	return (v > "'.date('Y-m-d').'" || v < $("#dateDeb").val()) ? false  : true;
}'),'#exportCsv');