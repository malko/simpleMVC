<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
	<title><?php echo FRONT_NAME.($this->formName?" - $this->formName":'')?></title>
	<link rel="stylesheet" href="<?php echo ROOT_URL.'/'.FRONT_NAME ?>.css" type="text/css" />
	<?php
		$this->_js_loadPlugin('jqueryToolkit');
		//$this->_jqueryToolkit_initPlugins('notifybox|notify');
		echo $this->_js_getPending();
	?>
</head>
<body class="formbuilded errorpage">
<div class="msgbox tk-state-normal" style="margin:auto;width:500px;">
	<h1><?php echo msg('OUPS'); ?></h1>
	<?php echo ($this->_appMsgs ? implode('',$this->_appMsgs):''); ?>
</div>
</body>
</html>