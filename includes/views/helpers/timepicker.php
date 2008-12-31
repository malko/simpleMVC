<?php
class timepicker_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles   = array(
		'js/jqueryPlugins/jqueryTimepicker/jquery.timepicker.js',
		'js/jqueryPlugins/jqueryMousewheel/jquery.mousewheel.js',
	);
	public $requiredPlugins = array('jquery');
	
	function timepicker($name,$value){
		$this->js("$('#timepicker_$name').timepicker();");
		return "<input type=\"text\" name=\"$name\" value=\"$value\" id=\"timepicker_$name\"/>";
	}
}