<?php
/**
* helper to manage jquery in your view (avoid multiple insertion)
* @package simpleMVC
* - dans le header du template appeller $this->jQuery('plugin1|plugin2|....');
* - faire ce que vous avez a faire dans le template
* - dans le footer appeller jQuery::getOnReadyDocument(); pour recuperer le javascript à executer
*/

class jQueryPlugin extends abstractViewHelper{
	protected $requiredFiles = array();
	protected $requiredPlugins = array();
	protected $loaded = false;
	protected $name   = '';
	function __construct(viewInterface $view){
		parent::__construct($view);
		$this->name = preg_replace('/_?viewHelper$/i','',get_class($this));
		//jQuery::pluginRegister($this);
		if(method_exists($this,'init')){
			$this->init();
		}
	}

	/** return bool true if load was already called */
	function loaded(){
		return $this->loaded;
	}
	/** @return array list of required files */
	function getRequiredPlugins(){
		return empty($this->requiredPlugins)?null:$this->requiredPlugins;
	}
	function getRequiredFiles(){
		return empty($this->requiredFiles)?null:$this->requiredFiles;
	}
	/** @return jQueryPlugin instance for chaining methods */
	function markAsLoaded(){
		$this->loaded = true;
		return $this;
	}
	/** @return string plugin dir url */
	function getPath(){
		return ROOT_URL.jQuery::$pluginPath.'/'.$this->name;
	}
	/** optional method getOnReadyDocument() must return the inobtrusive js code to put play at onload time */
	/** optional method init() permit to do all necessary job to init plugin such as set defaults setting for the plugin */
}

class jQuery_viewHelper extends abstractViewHelper{

	/** chemin de la librairie jquery relatif à ROOT_URL */
	static $mainPath   = '/js/jquery.js';
	/** chemin des plugins relatif à ROOT_URL */
	static $pluginPath = '/js/jqueryPlugins';
	static $included = false;
	/** pointer to loaded plugins to get their inobstrusive initializer on demand */
	static $loadedPlugins = array();
	static private $_pendingOnReadyDoc = '';

	/**
	* retourne la chaine de chargement de jQuery et des eventuels modules necessaire
	* @param mixed $pluginToLoad  liste des plugins jquery à charger sous forme de tableau ou de chaine séparé par des '|'
	* @param bool  $force         doit on forcer le chargement de la librairie et de a liste des plugins
	*/
	function jQuery($pluginToLoad=null,$force=false){
		if(self::$included && ! $force)
			$str = '';
		else
			$str = "<script type=\"text/javascript\" src=\"".ROOT_URL.self::$mainPath."\"></script>\n";
		self::$included = true;
		#- load plugins
		return $str.(empty($pluginToLoad)?'':$this->loadPlugin($pluginToLoad,$force));
	}

	/**
	* charge un plugin jquery et renvoie la chaine pour l'inclusion des fichiers necessaire.
	* @param  string $pluginName nom du ou des plugins à charger (séparés par '|')
	* @param  bool   $force      force ou non le chargement des plugins
	* @return string chaine d'inclusions des fichiers javascript et/ou css requis par le plugin
	*/
	public function loadPlugin($pluginName,$force=false){
		$plugins = explode('|',$pluginName);
		$str = '';
		foreach($plugins as $pName){
			if(! preg_match('!^jquery[A-Z]!',$pName) ){
				$pName = 'jquery'.strtoupper(substr($pName,0,1)).substr($pName,1);
			}
			$p     = $this->view->getHelper($pName,true);

			if($p->loaded() && !$force) #- don't load twice the same plugin unless asked for it
				continue;

			$pPath = $p->getPath();
			self::$loadedPlugins[] = $p;

			$reqPlugins = $p->markAsLoaded()->getRequiredPlugins();
			$reqFiles   = $p->getRequiredFiles();
			#- load required plugins
			if($reqPlugins){
				foreach($reqPlugins as $_p){
					$str .= $this->loadPlugin($_p,$force);
				}
			}
			#- then load each required files
			if( $reqFiles ){
				foreach($reqFiles as $f){
					if(preg_match('!\.css$!i',$f)){
						$str .= "<link href=\"$pPath/$f\" rel=\"stylesheet\" type=\"text/css\" />\n";
					}elseif(preg_match('!\.js$!i',$f)){
						$str .= "<script type=\"text/javascript\" src=\"$pPath/$f\"></script>\n";
					}
				}
			}
		}
		return $str;
	}


	/**
	* return the content of each loaded plugins onReadyDocument at once.
	* @param bool $noScriptTags set this to true to avoid embedding <script> tags
	* @retun string
	*/
	static public function getOnReadyDocument($noScriptTags=false){
		$str = '';

		if(! empty(self::$_pendingOnReadyDoc) ){
			$str .= self::$_pendingOnReadyDoc;
			self::$_pendingOnReadyDoc = '';
		}

		foreach(self::$loadedPlugins as $p){
			if(! method_exists($p,'getOnReadyDocument') )
				continue;
			$str .= ($tmp=$p->getOnReadyDocument())===''?'':"$tmp\n";
		}

		if( !strlen($str) )
			return false;

		if($noScriptTags)
			return $str;

		return "<script type=\"text/javascript\">\n/*<![CDATA[*/\njQuery().ready(function(){\n$str\n});\n/*]]>*/\n</script>";
	}

	static public function OnReadyDocument($jsCode){
		if( empty($jsCode) )
			return false;
		self::$_pendingOnReadyDoc .= $jsCode."\n";
	}
}

/** alias for easy call of jQuery_viewHelper statics methods */
class jQuery extends jQuery_viewHelper{}