<?php
/**
* helper to manage sortof include_once with js files and permit to manage dependancies between jsPlugins helpers
* @code
* 	//--inside view template files preload some plugins and print the includes strings
* 	echo $this->js(null,$stringPluginsToLoad);
* 	//-- do the normal job calling eventual jsPlugins as normal helpers inside the code
*   //-- then render additional scripts and include files that may still need to be include
* 	echo $this->js($stringScriptToExecuteAtWindowOnLoad);
* @endcode
* @class js_viewHelper
* @changelog
*            - 2008-10-13 - new parameter absolutePath for include method
*/
class js_viewHelper extends abstractViewHelper{

	static $includedFiles = array();
	static $pendingScript = '';
	static $registeredPlugins = array();

	function js($datas=null,$pluginToLoad=null){
		if( $pluginToLoad !== null)
			self::loadPlugin($this->view,$pluginToLoad);
		if( null === $datas)
			return self::getPending();
		if( is_array($datas) || preg_match('!\.(js|css)$!',$datas) )
			return self::includes($datas);
		return self::script($datas);
	}
	/**
	* preload jsPlugins
	* @param viewInterface $view    the view interface used to load plugins
	* @param mixed         $plugins array or string list of plugins to load (string item delimiter is '|')
	* @return includes string
	*/
	static function loadPlugin(viewInterface $view,$plugins){
		if( ! is_array($plugins) )
			$plugins = explode('|',$plugins);
		foreach($plugins as $p){
			$view->helperLoad($p);
		}
	}

	static function registerPlugin(jsPlugin_viewHelper $plugin){
		$pluginName = strtolower(str_replace('_viewHelper','',get_class($plugin)));
		self::$registeredPlugins[$pluginName]=true;
	}

	static function getRegisteredPlugins(){
		return array_keys(self::$registeredPlugins);
	}

	static function isRegistered($pluginName){
		return isset(self::$registeredPlugins[$pluginName])?true:false;
	}

	static function script($script){
		self::$pendingScript .= "\n$script\n";
	}

	static function getPending(){

		if( ! strlen(self::$pendingScript) )
			return self::getIncludes();

		$script = self::isRegistered('jquery')?"jQuery().ready(function(){\n".self::$pendingScript."\n});" : self::$pendingScript;
		self::$pendingScript = '';

		return self::getIncludes()."\n<script type=\"text/javascript\">\n/*<![CDATA[*/\n$script\n/*]]>*/\n</script>\n";
	}

	/**
	* include js and css files only once.
	* By default it takes relative path to ROOT_DIR/ROOT_URL and make them absolute path,
	* no check will be made if you pass true as $absolutePath. This can be usefull to include external files for example.
	* @param  string $file         relative path to ROOT_DIR or absolute path with second parameter set to true.
	* @param  bool   $absolutePath $file will be considered to already be an absolute path and so no check would be done.
	* @return bool
	*/
	static function includes($file,$absolutePath=false){
		if( is_array($file) ){
			$success = true;
			foreach($file as $f)
				$success &= self::includes($f);
			return $success;
		}
		#- check paths
		if(! $absolutePath ){
		if( ! is_file(ROOT_DIR.'/'.$file) )
			return false;
			$file = ROOT_URL.'/'.$file;
		}
		if( isset(self::$includedFiles[$file]) )
			return true;
		self::$includedFiles[$file]=false;
		return true;
	}

	static function getIncludes(){
		$incStr = '';
		foreach(self::$includedFiles as $k=>$v){
			if( $v )#- avoid multiple time inclusion
				continue;
			if( preg_match('!\.js$!',$k) )
				$incStr.= "<script src=\"$k\" type=\"text/javascript\"></script>\n";
			if( preg_match('!\.css$!',$k) )
				$incStr.= "<link type=\"text/css\" rel=\"stylesheet\" href=\"$k\" />\n";
			self::$includedFiles[$k]=true;
		}
		return $incStr;
	}
}
