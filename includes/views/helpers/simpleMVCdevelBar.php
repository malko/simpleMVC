<?php
/**
* helper to incorporate simpleMVC develBar
* @package simpleMVC
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/

class simpleMVCdevelBar_viewHelper extends  jsPlugin_viewHelper{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	public $requiredFiles = array(
		'js/simpleMVC_develBar.js'
	);
	public $requiredPlugins = array(
		'jquery'
	);
	function simpleMVCdevelBar(){
		return $this->view->_js_getPending();
	}
}