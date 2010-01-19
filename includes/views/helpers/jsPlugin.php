<?php
/**
* abstract class to assist in defining new jsPlugins.
* defining a new jsPlugin is quite simple:
* - extends this class
* - define static $requireFiles and eventual $requiredPlugins
* - eventually define an init() method to take particuliar actions at load time.
* @class jsPlugin_viewHelper
* @changelog
*            - 2010-01-18 - _optionString() bug correction
*            - 2009-10-20 - _optionString() method now preserve regexp object too
*            - 2009-03-24 - add common method getuniqueId
*/
abstract class jsPlugin_viewHelper extends abstractViewHelper{
	public $requiredFiles   = array();
	public $requiredPlugins = array();
	public function __construct(viewInterface $view){
		parent::__construct($view);
		#- ensure that js plugin is loaded
		$this->helperLoad('js');
		#- load required plugins
		if( ! empty($this->requiredPlugins)){
			foreach($this->requiredPlugins as $p)
				$this->view->_js_loadPlugin($p);
		}
		#- include required Files
		foreach($this->requiredFiles as $f){
			$this->view->_js_includes($f,preg_match('!^http!',$f)?true:false);
		}
		#- exectute init method if exists
		if(method_exists($this,'init'))
			$this->init();
		#- register plugin
		$this->view->_js_registerPlugin($this);
	}

	/**
	* render a php array to a javascript optionObject preserving anonymous functions and regex obects
	*/
	static function _optionString($opts,$indentSize=0){
		if(! is_array($opts)){
			if( null===$opts)
				return '';
			if( preg_match('!^\s*function\s*\(!i',$opts) )
				return $opts;
			if(! preg_match('!^\s*(\[.*\]|\{.*\}|["\'].*["\']|/.*/[igm]*|\d+|true|false)\s*$!s',$opts) ){
				$opts = "'".preg_replace("/(?<!\\\\)'/","\'",$opts)."'";
			}
			return $opts;
		}
		$str = array();
		$isObject = false;
		foreach($opts as $k=>$opt){
			if((! $isObject) && ! is_numeric($k) )
				$isObject = true;
			$str[]= ($isObject?"'$k':":'').(is_bool($opt)?($opt?'true':'false'):self::_optionString($opt,$indentSize+1));
		}
		$indentStr = "\n".str_repeat("\t",$indentSize);
		$indentStrEnd = "\n".str_repeat("\t",max(0,$indentSize-1));
		$str = implode(",$indentStr",$str);
		return $isObject?'{'."$indentStr$str$indentStrEnd}":"[$indentStr$str$indentStrEnd]";
	}

	final static public function uniqueId(){
		static $id;
		if( ! isset($id) )
			$id=0;
		return 'jsPlugin'.(++$id);
	}
}
