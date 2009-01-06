<?php
class timepicker_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles   = array(
		'js/jqueryPlugins/jqueryMousewheel/jquery.mousewheel.js',
		'js/jqueryPlugins/jqueryTimepicker/jquery.timepicker.js',
	);
	public $requiredPlugins = array('jquery');
	
	function timepicker($idElement,$value,$optionsString=null){
		$this->_js_script("$('#$idElement').timepicker(".($optionsString===null?"{format:24,showSeconds:true}":'').");");
		return "<input type=\"text\" name=\"$idElement\" value=\"$value\" id=\"$idElement\"/>";
	}
}