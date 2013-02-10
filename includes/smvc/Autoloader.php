<?php
class smvcAutoloader{

	static private $registeredPaths = array(
		LIB_DIR
	);
	static private $suffixRegisteredPaths = array();
	static private $knownPaths = array();
	static private $instance  = null;
	static public $basicInit = false;

	private function __construct(){
		self::$instance = $this;
		spl_autoload_register(array($this,'autoload'),true);
		if( self::$basicInit ){
			return;
		}
		//-- register defaults paths of a standard simpleMVC installation --//
		self::addAppPath(LIB_DIR);
		if( defined('MODELS_DIR') ){
			self::addModelPath(explode(',',MODELS_DIR));
			self::addModelAddonPath(defined('MODELADDONS_DIR')?explode(',',MODELADDONS_DIR):LIB_DIR.'/modelAddons');
		}

	}

	/**
	* register the autoloader
	* @param array $knownPaths list of well know path that won't be found by the normal behaviour of the autoloader
	*                          array('lowercaseclassname'=>'path',...)
	*/
	static public function init(array $knownPaths=null){
		if( $knownPaths ){
			self::setKnownPath($knownPaths);
		}
		if( !isset(self::$instance) ){
			self::$instance = new self();
		}
	}

	/**
	* set a list of well know path usefull for class files that are hard to resolve by normal autoloading
	*/
	static public function setKnownPath($className,$classFile=null){
		if( is_array($className) ){
			foreach( $className as $cname=>$path){
				self::setKnownPath($cname,$path);
			}
			return self::$knownPaths;
		}
		$cname = strtolower($className);
		if( null !== $classFile ){
			self::$knownPaths[$cname] = $classFile;
		}elseif( isset(self::$knownPaths[$cname]) ){
			unset(self::$knownPaths[$cname]);
		}
		return self::$knownPaths;
	}

	/**
	* add a path to look in at autoloading time
	* @param mixed $path path or list of path to check class files for
	* @param string $suffix does we look for suffixed class names and if so check specifically thoose path only for matching suffixes
	*/
	static public function addPath($path,$suffix=null){
		if(is_array($path)){
			foreach($path as $p){
				self::addPath($p,$suffix);
			}
			return;
		}
		if(! is_dir($path) ){
			return false;
		}
		if( null === $suffix){
			if( $tmp=array_search($path,self::$registeredPaths,true)){
				unset(self::$registeredPaths[$tmp]);
				self::$registeredPaths = array_values(self::$registeredPaths);
			}
			self::$registeredPaths[]=$path;
			return;
		}
		if( isset(self::$suffixRegisteredPaths[$suffix]) && ($tmp = array_search($path,self::$suffixRegisteredPaths[$suffix],true)) !== false){
			unset(self::$suffixRegisteredPaths[$suffix][$tmp]);
			self::$suffixRegisteredPaths[$suffix] = array_values(self::$suffixRegisteredPaths[$suffix]);
		}
		self::$suffixRegisteredPaths[$suffix][] = $path;
		return;
	}

	/**
	* helper to add an app path in one call (add controllers / views and helpers pathes all at once)
	*/
	static public function addAppPath($path){
		self::addControllerPath($path.'/controllers');
		self::addViewPath($path.'/views');
		self::addViewHelperPath($path.'/views/helpers');
	}
	/**
	* ass a path for controller loading
	*/
	static public function addControllerPath($path){
		return self::addPath($path,'_?[Cc]ontroller');
	}
	/**
	* add a view path to list of abstractController::$defaultViewDirs and add them to current view instance
	*/
	static public function addViewPath($path){
		if( ! is_dir($path) )
			return false;
		if(! in_array($path,abstractController::$defaultViewDirs,true) ){
			abstractController::$defaultViewDirs[] = $path;
		}
		$viewInstance = abstractController::getCurrentViewInstance(false);
		if( $viewInstance instanceof viewInterface )
			$viewInterface->addViewDir($path);
	}
	/**
	* add a path for viewHelpers loading
	*/
	static public function addViewHelperPath($path){
		self::addPath($path,'(?:_v|V)iew(?:_h|H)elper(?:interface)?');
	}
	/**
	* add a path for models and collection loading
	*/
	static public function addModelPath($path){
		self::addPath($path,'((?:_m|M)odel|Collection)');
	}
	/**
	* add a path for modelAddon loading
	*/
	static public function addModelAddonPath($path){
		self::addPath($path,'(?:_m|M)odelAddon(?:Interface)?');
	}

	/**
	* here is the specific simpleMVC logic to resolve autoloading.
	* @param $className;
	* @return bool;
	*/
	private function autoload($className){
		$testedPaths = array();

		if( isset(self::$knownPaths[$cname = strtolower($className)]) ){
			require($testedPaths[]=self::$knownPaths[$cname]);
			return true;
		}
		foreach(self::$suffixRegisteredPaths as $exp=>$path){
			if( preg_match("/$exp$/",$className,$m) )
				if( $this->_autoload(preg_replace("/$m[0]$/",'',$className),$path,$testedPaths) ){
				return true;
			}
		}
		//-- if models are used we always try to load a model instead of a std class
		if( defined('MODELS_DIR') && isset(self::$suffixRegisteredPaths['((?:_m|M)odel|Collection)']) && $this->_autoload($className,self::$suffixRegisteredPaths['((?:_m|M)odel|Collection)'],$testedPaths) ){
			return true;
		}
		if( $this->_autoload($className,self::$registeredPaths,$testedPaths) ){
			return true;
		}
		if( function_exists('DEVEL_MODE_ACTIVE') &&  DEVEL_MODE_ACTIVE() ){
			show($testedPaths,"trace");
		}
		throw new Exception("class $className can't be found.");
		return false;
	}

	/**
	* this is generic internal autoloading inside a given path.
	* @internal
	* @param string $className
	* @param array $paths
	* @param array &$testedPaths used for debugging to keep trace of all passed tryied
	* @return bool
	*/
	private function _autoload($className,$paths,array &$testedPath=null){
		$paths = array_reverse($paths);
		if( null===$testedPath){
			$tmp = array();
			$testedPath = &$tmp ;
		}
		foreach( $paths as $dir){
			$cname = $className;
			do{
				$classFiles = array(
					"$dir/$cname.php",
					"$dir/class-".strtolower($cname).'.php'
				);
				if( preg_match('/^[A-Z]/',$cname) )
					array_splice($classFiles,1,0,"$dir/".strtolower(substr($cname,0,1)).substr($cname,1).'.php');

				foreach($classFiles as $classFile){
					$testedPath[] = $classFile;
					if( is_file($classFile)){
						require $classFile;
						return true;
					}
				}
				$split = preg_split('!(?<=[a-z])(?=[A-Z])|_!',$cname,2);
				if(! isset($split[1]) )
					break;
				list($_dir,$cname) = $split;
				$dir .= "/$_dir";
				if(! is_dir($dir))
					break;
			}while($cname);
		}
		return false;
	}

}
