<?php
/**
* @todo gerer la configuration propre au module
* @todo automatiser les imports de fichiers du module
* @todo gerer l'uninstall au moment de la désactivation (sur demande)
*/
abstract class smvcModule{

	public $path = null;
	public $name = null;
	public $_configureDispatch=null;
	public $_configureOptions=null;

	static private $modulConfKeys = array(
		'active','weight','installed'
	);

	protected function __construct(){
		$this->name = self::getInstanceName($this);
		$this->path = MODULES_DIR.'/'.$this->name;
	}

	/**
	* @return smvcModule
	*/
	static function getInstance($moduleName){
		self::checkModName($moduleName);
		$className = $moduleName.'Module';
		if( ! class_exists($moduleName.'Module',false) ){
			if(is_file(MODULES_DIR."/$moduleName/module.php") ){
				require MODULES_DIR."/$moduleName/module.php";
			}else{
				eval('class '.$className.' extends '.__class__.'{};');
			}
		}
		return new $className();
	}

	function getConfig($confProp=null){
		$conf = self::modulesConfig();
		return $confProp?$conf[$this->name][$confProp]:$conf[$this->name];
	}


	/**
	* @return string
	*/
	static function getInstanceName(smvcModule $instance){
		return preg_replace('!_?[mM]odule$!','',get_class($instance));
	}

	function setActive($active=true){ return self::moduleSetActive($this->name,$active); }
	function hasInstaller(){ return method_exists($this,'install'); }
	function hasUnInstaller(){ return method_exists($this,'uninstall'); }
	function isActive(){ return $this->getConfig('active'); }
	function isInstalled(){ return $this->getConfig('installed'); }
	function setInstalled($installed=true){
		$conf = self::modulesConfig();
		$conf[$this->name]['installed'] = $installed;
		self::saveModulesConfig($conf);
	}
	/**
	* @return int
	*/
	function getWeight(){ return $this->getConfig('weight'); }



	/**
	* @return smvcCollection modules config
	*/
	static function modulesConfig($onlyConfigured=false){
		$conf = smvcCollection::init(defined('MODULES_CONF') ? json_decode(MODULES_CONF,true) : array())
			->combine(self::$modulConfKeys)
			->sort('weight')
		;
		if( $onlyConfigured ){
			return $conf;
		}
		$modulesIterator = new DirectoryIterator(MODULES_DIR);
		$modules = array();
		foreach( $modulesIterator as $mod ){
			if( $mod->isDot() || ! $mod->isDir() )
				continue;
			$modules[] = $mod = $mod->getFileName();
			$maxWeight = $conf->max('weight');
			if(! isset($conf[$mod]) ){
				$conf[$mod] = array(
					'active'=> false
					,'weight'=> ++$maxWeight
					,'installed'=> false
				);
			}
		}
		return $conf;
	}

	/**
	* @return void
	*/
	static function moduleSetActive($moduleName,$active=true){
		self::checkModName($moduleName);
		$conf = self::modulesConfig();
		$conf[$moduleName]['active'] = (bool) $active;
		self::saveModulesConfig($conf);
	}

	/**
	* @return bool
	*/
	static private function checkModName($modName){
		if(! (is_dir(MODULES_DIR.'/'.$modName)  && preg_match('!^[a-zA-Z0-9_-]+$!',$modName) ) ){
			throw new UnexpectedValueException("\"$modName\"is not a valid module name");
		}
		return true;
	}

	/**
	* @return bool
	*/
	static private  function saveModulesConfig($conf){
		return write_conf_file(
			CONF_DIR.'/'.FRONT_NAME.'_config.php'
			,array('MODULES_CONF'=>json_encode($conf->combine(array_keys(self::$modulConfKeys))))
			,true
		);
	}

}
