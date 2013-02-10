<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
	<title><?php echo FRONT_NAME.($this->formName?" - $this->formName":'')?></title>
	<link rel="stylesheet" href="<?php echo ROOT_URL.'/'.FRONT_NAME ?>.css" type="text/css" />
	<script src="<?php echo ROOT_URL.'/js/l.js/l.min.js'; ?>">
		ljs
			.load('<?php echo ROOT_URL.'/js/ljs.aliases.js#ljsAliases'; ?>','position','notify',function(){
				$.toolkit.initPlugins('notifybox|notify');
			})
		;
	</script>
</head>
<body class="formbuilded">
<div class="msgbox tk-notifybox tk-notifybox-top-center">
<?php echo ($this->_appMsgs ? implode('',$this->_appMsgs):''); ?>
</div>
<?php
	// error is already managed in controller add one check here just in case
	$form = $this->formbuilder($this->formName,checkUserRight(FORM_BUILDER_RIGHT));
	echo $form?$form:'<div class="tk-state-error tk-content">Formulaire inexistant ou fermé.</div>';
?>
</body>
</html>