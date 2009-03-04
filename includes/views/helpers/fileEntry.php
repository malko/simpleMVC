<?php
/**
* helper to easily incorporate jquery based Textarea
* @package simpleMVC
*/

class fileEntry_viewHelper extends  jsPlugin_viewHelper{
	public $requiredFiles = array(
		'js/jqueryPlugins/jqueryFileEntry/jquery.fileentry.js'
	);
	public $requiredPlugins = array(
		'facebox'
	);

	/**
	* return necessary code to render an input file form element.
	* @param string $inputName    name of the input element
	* @param mixed  $options path -> chemin ou ouvrir la fenetre de dialogue
	* @return string.
	*/
	function fileEntry($inputName,$value=null,array $options=null){
		static $loaded;
		if( ! $loaded ){
			$loaded = true;
			$this->_js_script("$('.fileEntry').fileEntry({imgPath:'".ELEMENTS_URL."/icones/admin',rootPath:'".APP_URL."'})");
		}
		$id = preg_replace('![^a-z0-9_-]!i','',$inputName);
		$path =  empty($options['path'])?'':' path="'.docsController::encodeFilePath($options['path'],true).'"';
		return '<input type="text" name="'.$inputName.'" id="'.$id.'" value="'.$value.'" class="fileEntry"'.$path.'/>';
	}

}
