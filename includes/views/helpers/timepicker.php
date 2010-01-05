<?php
class timepicker_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles   = array(
		'js/jqueryPlugins/jqueryMousewheel/jquery.mousewheel.js',
		#- 'js/jqueryPlugins/jqueryTimepicker/jquery.timepicker.js',
		'js/jquery.toolkit/src/jquery.toolkit.js',
		'js/jqueryPlugins/jqueryTimepicker/jquery.tk.timepicker.js',
	);
	public $requiredPlugins = array('jquery');

	function timepicker($idElement,$value,$optionsString=null){
		$class = ( null==$optionsString)?' tk-timepicker-24-nosecs':'';
		$this->_js_script("$('#$idElement').timepicker(".($optionsString===null?'':$optionsString).");");
		return "<input type=\"text\" name=\"$idElement\" value=\"$value\" id=\"$idElement\" class=\"tk-timepicker$class\"/>";
	}
}
