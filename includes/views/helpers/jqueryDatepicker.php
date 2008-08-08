<?php
/**
* helper to easily incorporate jquery Datepicker
* @package simpleMVC
*/

class jqueryDatepicker_viewHelper extends  jQueryPlugin{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	protected $requiredFiles = array(
		'ui.datepicker.css',
		'ui.datepicker.js',
		#- ~ 'ui.datepicker-fr.js'
	);

	protected $initRequired=false;


	function jqueryDatepicker($idElement,$value=''){

		$this->initRequired=true;
		//<input type="text" name="$idElement" value="$value" class="jqueryDatepicker"/>
	jQuery::OnReadyDocument('
	$("#'.$idElement.'").attachDatepicker({ dateFormat: "yy-mm-dd", minDate: new Date(2008, 1 - 1, 1) });');

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
