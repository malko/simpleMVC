<?php
/**
* helper to easily incorporate jquery Datepicker
* @package simpleMVC
* http://marcgrabanski.com/code/ui-datepicker
* @changelog 
*            - 2009-01-05 - add withTime method to combine datepicker and timepicker (use new timepicker helper)
*            - 2008-11-07 - change _js_script call (not static anymore)
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

		$this->_js_script('
		$("#'.$idElement.'").datepicker({
			dateFormat: "yy-mm-dd",
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

	/**
	* 
	* @return 
	* @param string $idElement name/id of the created elemenet
	* @param string $value[optional] value of the input field
	* @param array  $dateTimePickerOptions[optional] array(0=>'optionStringForDatePicker',1=>'optionStringForTimePicker')
	*/
	function withTime($idElement,$value='',array $dateTimeOptionsStrings=null){
		static $alreadyUsed = false;
		if(! $alreadyUsed ){
			$this->_js_script("
			function dateTimePickerUpdate(){
				id = this.id.replace(/^(date_|time_)/,'');
				$('#'+id).attr('value',$('#date_'+id).attr('value')+' '+$('#time_'+id).attr('value'));
			}");
			$alreadyUsed = true;
		}
		$this->_js_loadPlugin('timepicker');
		if( empty($value) ){
			$date = date('Y-m-d');
			$time = date('H:i:s');
		}else{
			preg_match('!^(\d\d\d\d[-/]\d\d[-/]\d\d)?\D?(\d\d:\d\d(:\d\d)?)?$!',$value,$m);
			$date = empty($m[1])?date('Y-m-d'):$m[1];
			$time = empty($m[2])?date('H:i:s'):$m[2];
		}
		$str = $this->datepicker("date_$idElement",$date, isset($dateTimeOptionsStrings[0])?$dateTimeOptionsStrings[0]:null);
		$str.= $this->timepicker("time_$idElement",$time, isset($dateTimeOptionsStrings[1])?$dateTimeOptionsStrings[1]:null);
		$str.= '<input type="text" name="'.$idElement.'" id="'.$idElement.'" value="'.$value.'" />'; 
		$this->_js_script("$('#$idElement').hide();$('#date_$idElement, #time_$idElement').change(dateTimePickerUpdate);");
		return $str;
	}
}
