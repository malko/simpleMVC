<?php

class swfobject_viewHelper extends jsPlugin_viewHelper{

	public $requiredFiles   = array('js/swfobject.js');
	public $requiredPlugins = array();

	function swfobject($swfUrl,$name='myswf',$w='100%',$h='100%',$v=8,$bg='#fff',$defaultContent=""){
		$this->_js_script("
		var fo = new SwfObject('$swfUrl', '$name', '$w', '$h', '$v', '$bg');
			fo.addParam('quality', 'high');
			//fo.setAttribute('redirectUrl', 'noflash.php');
			fo.write('$name');
		");
		return "<div id=\"$name\">$defaultContent</div>";
	}

}
