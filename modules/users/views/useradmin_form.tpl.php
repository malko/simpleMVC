<?php require LIB_DIR .'/views/models_form.tpl.php'; ?>
<script>
$(function(){
	$('<input type="text" id="passReveal"/>').insertBefore('#password').hide().parent()
	$('<label style="display:inline;"><input type="checkbox"><?php echo langManager::msg('show password') ?></label>').insertAfter('#password')
		.find('input').change(function(){
			if( $(this).is(':checked')){
				$("#password").hide();$('#passReveal').show().val($("#password").val());
			}else{
				$("#passReveal").hide();$('#password').show().val($("#passReveal").val());;
			}
		})
		.parent().after('<br /><label style="display:inline;"><input type="checkbox" value="1" name="resetPass"<?php echo ($this->_model_ && empty($this->_model_->datas['password']))?' checked="checked"':''; ?>><?php echo langManager::msg('set empty password'); ?></label>')
	;
	$('#password,#passReveal').change(function(){
		var i = $(this);
		if( !i.is(':visible') ){ return; }
		$( i.attr('id')==='passReveal'?'#password':'#passReveal').val(i.val());
	});
	$('input[name=resetPass]').change(function(){
		if( $(this).is(':checked') ){
			$('#password,#passReveal').val('');
		}
	});

	<?php
	if( $this->_model_ && !empty($this->_model_->datas["password"])){
		echo '$(\'<div class="tk-content tk-border tk-state-info" >'
		.langManager::msg('keeping password blank without checking "set empty password" will simply leave actual password unchanged')
		.'</div><br />\').insertAfter($(".adminForm").prev("h1"));';
	}
	?>
});
</script>