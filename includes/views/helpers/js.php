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
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2008-12-05 - now use addeventListener/attachEvent to encapsulate appended script in window.onload when there's no jquery plugins registered
*                         - new static properties $scriptRootDir and self::$scriptRootDir to allow setting of relatives paths
*                         - new static method setRootPaths() to set $scriptRootDir and self::$scriptRootDir
*            - 2008-10-13 - new parameter absolutePath for include method
*                         - methods are not static any more as view can now make complex call to helpers methods
*/
class js_viewHelper extends abstractViewHelper{

	public static $includedFiles = array();
	public static $pendingScript = '';
	public static $registeredPlugins = array();
	public static $scriptRootDir = ROOT_DIR;
	public static $scriptRootUrl = ROOT_URL;

	static function setRootPaths($dir=null,$url=null){
		if( null !== $dir)
			self::$scriptRootDir = $dir;
		if( null !== $url)
			self::$scriptRootUrl = $url;
	}

	function js($datas=null,$pluginToLoad=null){
		if( $pluginToLoad !== null)
			$this->loadPlugin($pluginToLoad);
		if( null === $datas)
			return $this->getPending();
		if( is_array($datas) || preg_match('!\.(js|css)$!',$datas) )
			return $this->includes($datas);
		return $this->script($datas);
	}
	/**
	* preload jsPlugins
	* @param mixed         $plugins array or string list of plugins to load (string item delimiter is '|')
	* @return includes string
	*/
	function loadPlugin($plugins){
		if( ! is_array($plugins) )
			$plugins = explode('|',$plugins);
		foreach($plugins as $p){
			$this->view->helperLoad($p);
		}
	}

	function registerPlugin(jsPlugin_viewHelper $plugin){
		$pluginName = strtolower(str_replace('_viewHelper','',get_class($plugin)));
		self::$registeredPlugins[$pluginName]=true;
	}

	function getRegisteredPlugins(){
		return array_keys(self::$registeredPlugins);
	}

	function isRegistered($pluginName){
		return isset(self::$registeredPlugins[$pluginName])?true:false;
	}

	function script($script){
		self::$pendingScript .= "\n$script\n";
	}

	function getPending(){
		static $calledTime;
		if( ! strlen(self::$pendingScript) )
			return $this->getIncludes();
		if( $this->isRegistered('jquery') ){
			$script = "jQuery().ready(function(){\n".self::$pendingScript."\n});";
		}else{
			$calledTime = isset($calledTime)?$calledTime+1:0;
			$script = "function jsReady$calledTime(){".self::$pendingScript."};\n"
				."if(window.addEventListener){ window.addEventListener('load',jsReady$calledTime,false); }else if(window.attachEvent){ window.attachEvent('onload', jsReady$calledTime); }";
		}

		self::$pendingScript = '';

		return $this->getIncludes()."\n<script type=\"text/javascript\">/*<![CDATA[*/\n$script\n/*]]>*/</script>\n";
	}

	/**
	* include js and css files only once.
	* By default it takes relative path to self::$scriptRootDir/self::$scriptRootUrl and make them absolute path,
	* no check will be made if you pass true as $absolutePath. This can be usefull to include external files for example.
	* @param  string $file         relative path to self::$scriptRootDir or absolute path with second parameter set to true.
	* @param  bool   $absolutePath $file will be considered to already be an absolute path and so no check would be done.
	* @return bool
	*/
	function includes($file,$absolutePath=false){
		if( is_array($file) ){
			$success = true;
			foreach($file as $f)
				$success &= $this->includes($f);
			return $success;
		}
		#- check paths
		if(! $absolutePath ){
		if( ! is_file(self::$scriptRootDir.'/'.$file) )
			return false;
			$file = self::$scriptRootUrl.'/'.$file;
		}
		if( isset(self::$includedFiles[$file]) )
			return true;
		self::$includedFiles[$file]=false;
		return true;
	}

	function getIncludes(){
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

/**
* dummy jsPlugin that load jquery usefull for jsPlugins that require jquery
* @class jquery_viewHelper
*/
class jquery_viewHelper extends jsPlugin_viewHelper{
	public $requiredFiles = array('js/jquery.js');
}
