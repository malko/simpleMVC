<?php
/**
* @changelog
*            - 2009-09-03 - add theme and version properties
* dummy jsPlugin that load jquery-ui usefull for jsPlugins that require jquery-ui
* @class jqueryui_viewHelper
*/
class jqueryui_viewHelper extends jsPlugin_viewHelper{
	static public $customThemeName = '';
	static public $uiVersion = '1.7.2';
	public $requiredFiles = array(
		'js/jqueryPlugins/bgiframe/jquery.bgiframe.min.js',
		'js/jquery-ui.js'
	);
	public $requiredPlugins = array(
		'jquery'
	);

	function init(){
		if( self::$customThemeName ){
			$this->_js_includes('js/css/'.self::$customThemeName.'/jquery-ui-'.self::$uiVersion.'.custom.css');
		}else{
			$this->_js_includes('js/css/base/ui.all.css');
		}
		$this->_js_script('$.ui.dialog.defaults.bgiframe = true;');
	}
}
