<?php
/**
* helper to easily incorporate jquery Datepicker
* @package simpleMVC
* http://marcgrabanski.com/code/ui-datepicker
*/

class datepicker_viewHelper extends  jsPlugin_viewHelper{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	public $requiredFiles = array(
		'js/jqueryPlugins/jqueryDatepicker/jquery-ui-themeroller.css',
		'js/jqueryPlugins/jqueryDatepicker/18n/ui.datepicker-fr.js',
		'js/jqueryPlugins/jqueryDatepicker/jquery-ui-personalized-1.6b.packed.js'
	);
	public $requiredPlugins = array('jquery');

	function datepicker($idElement,$value='',$datePickerOptionStr=null){

	js_viewHelper::script('
		$("#'.$idElement.'").datepicker({
			dateFormat: "dd-mm-yy",
			highlightWeek: true,
			defaultDate: +1,
			showOn:"both",
			buttonText:"pick a date",
			buttonImage: "'.ELEMENTS_URL.'/icones/date.png"'
			.($datePickerOptionStr?",\n\t\t\t$datePickerOptionStr":'')
			.'
		});'
	);

		return 	'<input type="text" id="'.$idElement.'" name="'.$idElement.'" value="'.$value.'" />';
	}

}
