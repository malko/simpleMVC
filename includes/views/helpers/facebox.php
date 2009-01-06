<?php
/**
* helper to easily incorporate jquery based Textarea
* @package simpleMVC
*/

class facebox_viewHelper extends  jsPlugin_viewHelper{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	public $requiredFiles = array(
		'js/jqueryPlugins/jqueryFacebox/facebox.css',
		'js/jqueryPlugins/jqueryFacebox/facebox.js'
	);
	public $requiredPlugins = array('jquery');
	protected $linkBoxRegistered=false;

	/** set default settings */
	function init(){
		$this->js("
			$.facebox.settings.loading_image = '".ROOT_URL."/js/jqueryPlugins/jqueryFacebox/loading.gif';
    	$.facebox.settings.close_image   = '".ROOT_URL."/js/jqueryPlugins/jqueryFacebox/closelabel.gif'
    	"
		);
	}
	/**
	* return a link to open a facebox
	* @param str $label  the html code to put as link label
	* @param str $target target to load in the facebox see facebox plugin doc for more details
	* */
	function facebox($label,$target){
		if(! $this->linkBoxRegistered ){
			$this->linkBoxRegistered = true;
			$this->js("$('a[rel*=facebox]').facebox();");
		}
		return "<a href=\"$target\" rel=\"facebox\">$label</a>";
	}

}
