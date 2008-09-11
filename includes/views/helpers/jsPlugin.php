<?php
/**
* abstract class to assist in defining new jsPlugins.
* defining a new jsPlugin is quite simple:
* - extends this class
* - define static $requireFiles and eventual $requiredPlugins
* - eventually define an init() method to take particuliar actions at load time.
* @class jsPlugin_viewHelper
*/
abstract class jsPlugin_viewHelper extends abstractViewHelper{
	public $requiredFiles   = array();
	public $requiredPlugins = array();
	function __construct(viewInterface $view){
		parent::__construct($view);
		#- ensure that js plugin is loaded
		$this->helperLoad('js');
		#- load required plugins
		if( ! empty($this->requiredPlugins)){
			foreach($this->requiredPlugins as $p)
				js_viewHelper::loadPlugin($this->view,$p);
		}
		#- include required Files
		foreach($this->requiredFiles as $f)
			js_viewHelper::includes($f);
		#- exectute init method if exists
		if(method_exists($this,'init'))
			$this->init();
		#- register plugin
		js_viewHelper::registerPlugin($this);
	}
}

/**
* dummy jsPlugin that load jquery usefull for jsPlugins that require jquery
* @class jquery_viewHelper
*/
class jquery_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles = array('js/jquery.js');
}
