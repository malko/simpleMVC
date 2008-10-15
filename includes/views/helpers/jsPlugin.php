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
				$this->view->_js_loadPlugin($p);
		}
		#- include required Files
		foreach($this->requiredFiles as $f)
			$this->view->_js_includes($f);
		#- exectute init method if exists
		if(method_exists($this,'init'))
			$this->init();
		#- register plugin
		$this->view->_js_registerPlugin($this);
	}
}
