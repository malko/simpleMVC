<h1>Form builder configuration</h1>
<form method="post" class="adminForm" id="fbuilder_configureform">
	<?php echo $this->token('formbuilder'); ?>
	<table cellpadding="2" cellspacing="0" border="0">
		<tfoot>
			<tr>
				<td colspan="2">
					<button type="submit"><?php echo msg('save'); ?></button>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<td><label for="right"><?php echo msg('configure_requireRightLabel'); ?></label></td>
				<td>
					<select id="right" name="right">
						<?php
						echo userRights::getAllInstances()
							->loadDatas("domain") // must do this to avoid warning about array modified
							->sort(create_function('$a,$b','return strcasecmp($a,$b);'))
							->htmlOptions('',userRights::getInstance(defined('FORM_BUILDER_RIGHT') ? FORM_BUILDER_RIGHT : 'modules.admin') )
						;
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="formSubmitDispatch"><?php echo msg('configure_formSubmitDispatch'); ?></label></td>
				<td>
					<input type="text" name="formSubmitDispatch" id="formSubmitDispatch" value="<?php echo (defined('FORM_BUILDER_DEFAULTSUBMIT') ? FORM_BUILDER_DEFAULTSUBMIT : ''); ?>">
				</td>
			</tr>
		</tbody>
	</table>
</form>
<?php
	$this->validable('formSubmitDispatch',array('rule'=>'/^(https?:\/\/.*|[a-zA-Z0-9_-]+:[a-zA-Z0-9_-]+)$/'),'#fbuilder_configureform');
?>