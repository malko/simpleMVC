<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
	<title><?= FRONT_NAME .($this->pageTitle?" - $this->pageTitle":'')?></title>
	<link rel="stylesheet" href="<?= ROOT_URL .'/'.FRONT_NAME.'.css' ?>" type="text/css" />
	<?php
		$this->_jqueryToolkit_initPlugins('notifybox|notify');
		echo $this->_js_getPending();
	?>
</head>
<body>
<?php
	if( $this->_appMsgs ){
		echo '<div class="msgbox tk-notifybox tk-notifybox-top-center">'.implode('',$this->_appMsgs).'</div>';
	}
