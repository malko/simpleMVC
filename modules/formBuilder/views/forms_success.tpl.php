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
<body class="formbuilded">
<div id="pageContent">
<div class="msgbox tk-notifybox tk-notifybox-top-center">
<?php echo ($this->_appMsgs ? implode('',$this->_appMsgs):''); ?>
</div>
	<?php echo msg('Votre demande a bien été prise en compte. Merci de votre participation.'); ?>
</div>
</body>
</html>
