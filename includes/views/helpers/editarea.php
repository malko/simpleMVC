<?php
/**
* config options: http://www.cdolivet.com/editarea/editarea/docs/configuration.html
*
*
*/
class editarea_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles   = array(
		'js/editarea/edit_area/edit_area_full.js'
	);

	static public $defaultOptions = array(
		'id'  => null,
		'language' => 'fr',
		'syntax' => 'html',
		'font_size'=>'8',
		'toolbar' => 'undo,redo,syntax_selection,select_font,word_wrap,search,go_to_line,|,change_smooth_selection,reset_highlight,highlight,|,help', //save,load,new_document
		'is_editable' => true,
		'start_highlight' => true

	);

	function editArea($name,$value=null,array $options=null){
		$options = null==$options?self::$defaultOptions : array_merge(self::$defaultOptions,$options);
		if(empty($options['id']))
			$options['id'] = self::uniqueId();
		$this->_js_script("editAreaLoader.init(".json_encode($options).");");
		return "<textarea name=\"$name\" id=\"$options[id]\" class=\"editarea\">$value</textarea>";
	}
}