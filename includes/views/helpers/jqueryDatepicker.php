<?php
/**
* helper to easily incorporate jquery Datepicker
* @package simpleMVC
* http://marcgrabanski.com/code/ui-datepicker
*/

class jqueryDatepicker_viewHelper extends  jQueryPlugin{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	protected $requiredFiles = array(
		#- ~ 'ui.datepicker.css',
		#- ~ 'ui.datepicker.js',
		#- ~ 'ui.datepicker-fr.js'
		'jquery-ui-themeroller.css',
		'i18n/ui.datepicker-fr.js',
		'jquery-ui-personalized-1.6b.packed.js'
	);

	protected $initRequired=false;


	function jqueryDatepicker($idElement,$value='',$datePickerOptionStr=null){

		$this->initRequired=true;
		//<input type="text" name="$idElement" value="$value" class="jqueryDatepicker"/>
	jQuery::OnReadyDocument('
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


	/*function getOnReadyDocument(){
		if($this->initRequired){
			$this->initRequired = false;
			return "$('.jqueryRte').rte({imgPath:'".ROOT_URL.jQuery::$pluginPath."/jqueryRte/'});";
		}
		return '';
	}*/

}
