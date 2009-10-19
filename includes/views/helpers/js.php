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
*            - 2009-09-03 - add new jsPlugins method support jsPlugins::_onGetPending()
*            - 2009-05-05 - add new js method scriptOnce same as script method but with a check that script isn't already appended
*            - 2009-03-27 - add jqueryUI plugin
*            - 2009-02-08 - loadPlugin now check for registeredPlugins before trying to load it
*                         - js and others methods now return $this for method chaining
*                         - add some documentation comments
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

	/**
	* multiple purpose and sort of shorthand method.
	* It can be call in different manner, as a shorthand for script / includes and/or loadPlugin, depending on parameters.
	* @param mixed $datas may be a string of script as in script() method
	*                     also can be a single js or css or a list of them to includes (ie: 'style.css' array('style.css','script.js'))
	*                     or it can be null in which case it will end by calling the getPending method instead of returning $this.
	* @param mixed        $plugins array or string list of plugins to load (string item delimiter is '|')
	* @return mixed $this for method chainging or string (result of getPending method) if $datas is null
	*/
	function js($datas=null,$pluginToLoad=null){
		if( $pluginToLoad !== null)
			$this->loadPlugin($pluginToLoad);
		if( null === $datas)
			return $this->getPending();
		if( is_array($datas) || preg_match('!\.(js|css)$!',$datas) ){
			$this->includes($datas);
			return $this;
		}
		return $this->script($datas);
	}
	/**
	* preload jsPlugins
	* @param mixed         $plugins array or string list of plugins to load (string item delimiter is '|')
	* @return $this for method chaining
	*/
	function loadPlugin($plugins){
		if( ! is_array($plugins) )
			$plugins = explode('|',$plugins);
		foreach($plugins as $p){
			if(! $this->isRegistered($p) ) #- dont load already registered plugin
			$this->view->helperLoad($p);
		}
		return $this;
	}

	/**
	* internal method used by jsPlugins helpers to register themselves and avoid multiple load.
	* you will probably never used this on your own.
	* @param jsPlugin_viewHelper $plugin the plugin instance to register
	* @return $this for method chaining
	*/
	function registerPlugin(jsPlugin_viewHelper $plugin){
		$pluginName = strtolower(str_replace('_viewHelper','',get_class($plugin)));
		self::$registeredPlugins[$pluginName]=true;
		return $this;
	}

	/**
	* return list of registeredPlugins
	* @return array
	*/
	function getRegisteredPlugins(){
		return array_keys(self::$registeredPlugins);
	}

	/**
	* check whether a plugin is registered or not
	* @param string $pluginName name of the plugin you want to now if it's registered
	* @return bool
	*/
	function isRegistered($pluginName){
		return isset(self::$registeredPlugins[$pluginName])?true:false;
	}

	/**
	* append script to be executed at window.onload time
	* @param string $script script to append
	* @return $this for method chaining
	*/
	function script($script){
		self::$pendingScript .= "\n$script";
		return $this;
	}
	/**
	* append script to be executed at window.onload time like script() method after checking script wasn't previously appended.
	* this method is particulary usefull when you want to be sure a particular set of javascript is inserted but don't know if it was already done before.
	* in fact this method will just have a check at
	* @param string $script           script to append
	* @param string $scriptIdentifier a unique identifier for this script or null for an automatic md5() script calculation as a scriptIdentifier (not recommended as it cost extra time.)
	* @return $this for method chaining
	*/
	function scriptOnce($script,$scriptIdentifier=null){
		static $ids;
		if(!isset($ids)) $ids = array();
		if( null===$scriptIdentifier )
			$scriptIdentifier = md5($script);
		if( isset($ids[$scriptIdentifier]) )
			return $this;
		$ids[$scriptIdentifier]=true;
		return $this->script($script);
	}

	/**
	* return script tag with pending script to be executed at window.onload time preceded by script/links tags for js and css files included.
	* @return string
	*/
	function getPending(){
		static $calledTime;
		if( ! strlen(self::$pendingScript) )
			return $this->getIncludes();

		//--append plugins _onGetPending;
		foreach($this->getRegisteredPlugins() as $plugin){
			if( method_exists($plugin.'_viewHelper','_onGetPending')){
				$this->view->getHelper($plugin)->_onGetPending();
			}
		}

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
				$success &= $this->includes($f,$absolutePath);
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

	/**
	* return script/links tags for js and css files includes
	* @return string
	*/
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
