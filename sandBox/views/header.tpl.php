<?= '<?' ?>xml version="1.0" encoding="UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
	<title><?= FRONT_NAME .($this->pageTitle?" - $this->pageTitle":'')?></title>
	<link rel="stylesheet" href="<?= ROOT_URL .'/'.FRONT_NAME.'.css' ?>" type="text/css" />
	<?php
		//$this->_jqueryToolkit_initPlugins('notifybox|notify');
		echo $this->_js_getPending();
	?>
</head>
<body>
<?php
	if( $this->_appMsgs ){
		echo '<div class="msgbox tk-notifybox tk-notifybox-top-center">'.implode('',$this->_appMsgs).'</div>';
	}
