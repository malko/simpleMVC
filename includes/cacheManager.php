<?php
/**
* @since 2009-11-19
* @package cacheManager
* @changelog
* - 2010-11-10 - cacheManager::setEnd() doesn't need name parameter any more as we use $cacheStack property to get it
* - 2010-10-06 - fileCacheBackend now manage file extensions and constructor can take parameters
* - 2010-03-22 - add fileCacheBackend
*              - new methods removeMatchingItem()
* - 2010-02-10 - add methods cacheManager::(set|get)Backend()
*/
if(! defined('CACHE_MANAGER_DEFAULT_BACKEND'))
	define('CACHE_MANAGER_DEFAULT_BACKEND','fileCacheBackend');

if(! defined('CACHE_MANAGER_ENABLE') )
	define('CACHE_MANAGER_ENABLE',true);
if(! defined('CACHE_MANAGER_AUTOCLEAR') )
	define('CACHE_MANAGER_AUTOCLEAR',true);
if(! defined('CACHE_MANAGER_TTL') )
	define('CACHE_MANAGER_TTL','30 minutes');
if(! defined('CACHE_DB_CONNECTION') ){
	$dbCacheBackendConnection = defined('DB_CONNECTION')?DB_CONNECTION:null;
	define('CACHE_DB_CONNECTION',$dbCacheBackendConnection);
}
if(! defined('CACHE_DB_AUTOCREATE')){
	$dbCacheBackendAutoCreate	= constant('DEVEL_MODE')?true:false;
	define('CACHE_DB_AUTOCREATE',$dbCacheBackendAutoCreate);
}
if(! defined('CACHE_FILE_ROOT_DIR'))
	define('CACHE_FILE_ROOT_DIR','/tmp/cacheManager');
if(! defined('CACHE_FILE_USE_SUBDIRS') ){
	define('CACHE_FILE_USE_SUBDIRS',false);
}
class cacheManager{
	/** storage backend to use */
	static public $useBackend=CACHE_MANAGER_DEFAULT_BACKEND;
	/** default time to live settings */
	static public $ttl=CACHE_MANAGER_TTL;
	/** boolean value does the cache try to clear too old items from storage */
	static public $autoClear=CACHE_MANAGER_AUTOCLEAR;
	/** does the cache manager is enable or not, if not then bypass set/get methods */
	static public $enable=CACHE_MANAGER_ENABLE;
	/**
	* hold the used backend instance
	* @private
	*/
	static private $backend=null;

	static private $cacheStack = array();

	private function __construct(){}

	/**
	* initialize the backend to use and eventually clean too old items from storage
	* normally you don't need to call this method on your own.
	*/
	static function init(){
		if( ! self::$backend instanceof self::$useBackend){
			$backend = new self::$useBackend();
			self::setBackend($backend);
			if(! $backend instanceof cacheBackend)
				throw new RuntimeException(__class__.'::init() invalid cacheBackend');
			if( self::$autoClear )
				self::clear(self::$ttl);
		}
	}
	static function setBackend(cacheBackend $backend){
		self::$backend = $backend;
	}
	static function getBackend(){
		return self::$backend;
	}
	/**
	* start to cache one item (be aware that this method use output buffering methods)
	* cached items must be start/end in a FirstInLastOut order
	*/
	static function setStart($name){
		array_push(self::$cacheStack,$name);
		ob_start();
	}
	/**
	* set the previously started item and stop buffering the output.
	* as thoose methods use output buffering they must be ended in the reverse order they was started
	* so the first started must be the last ended.
	* In the same way you really should avoid (at least be carefull) to use output buffering inside the portion of code you try to cache.
	*/
	static function setEnd(){
		if( empty(self::$cacheStack)){
			throw new BadMethodCallException(__class__."::setEnd() called with no previous matching call to ".__class__."setStart() method");
		}
		$name = array_pop(self::$cacheStack);
		return self::set($name,ob_get_clean());
	}
	/**
	* return a cacheItem content from used storage backend
	* @param string $name the cacheItem name
	* @param string $maxAge the max age of the item to be return
	* @return string or null
	*/
	static function get($name,$maxAge=null){
		if(! self::$enable )
			return null;
		self::init();
		$maxAge = self::_ttl(null===$maxAge?self::$ttl : $maxAge);
		$i = self::$backend->getItem($name);
		#- check for validity
		if( ! $i->checkValididty($maxAge) ){
			return cacheItem::dropInstance($name);
		}
		return $i->content;
	}
	/**
	* set a cacheItem datas and return the cached content
	* @param string $name the cacheItem name
	* @param string $content the content to put in cache
	* @return string content pushed to cache
	*/
	static function set($name,$content){
		if(! self::$enable )
			return $content;
		self::init();
		$i = cacheItem::getInstance($name);
		$i->content = $content;
		self::$backend->saveItem($i);
		return $content;
	}
	/**
	* remove a cacheItem from the used storage backend
	* @param mixed $item cacheItem or list of cacheItem (may be cacheItem or cacheItem's name)
	* @return void
	*/
	static function remove($item){
		self::init();
		if( is_array($item)){
			foreach($item as $i)
				self::remove($i);
			return;
		}
		self::$backend->removeItem($item);
		cacheItem::dropInstance($item);
	}
	/*
	* remove all cacheItems which name matching the given expression
	* @param string $exp posixRegex the name must match to be dropped
	* @return void
	*/
	static function removeMatchingItem($exp){
		self::init();
		self::$backend->removeMatchingItem($exp);
	}
	static function clear($olderThan=null){
		self::init();
		return self::$backend->clear(self::_ttl($olderThan));
	}
	/**
	* internal methods to translate time to live in seconds
	* @internal
	* @protected
	*/
	static protected function _ttl($ttl){
		if( is_int($ttl) || preg_match('!^\d+$!',$ttl))
			return (int) $ttl;
		if(! is_string($ttl))
			false;
		return strtotime($ttl) - time();
	}
}

/**
* common cacheBackend interface
*/
interface cacheBackend{
	function clear($olderThan=null);
	/** must set the item->cacheTime */
	function saveItem(cacheItem $item);
	function removeItem($item);
	function removeMatchingItem($exp);
	function getItem($name);
}

class fileCacheBackend implements cacheBackend{
	static public $dfltCacheRootDir = CACHE_FILE_ROOT_DIR;
	static public $dfltUseSubDirs = CACHE_FILE_USE_SUBDIRS;

	protected $cacheRootDir = null;
	protected $useSubDirs = null;
	protected $fileSuffix = null;

	/**
	* return a fileCacheBackend instance
	* @param string $cacheRootDir  null to use default or path to the root dir of cached files (omit trailing slash)
	* @param bool   $useSubDirs    null to use default settings
	* @param string $fileSuffix    string to append to the end of the files (may be used to specify file extension)
	*/
	function __construct($cacheRootDir=null,$useSubDirs=null,$fileSuffix=null){
		$this->cacheRootDir = ( null !== $cacheRootDir )? $cacheRootDir : self::$dfltCacheRootDir;
		$this->useSubDirs = ( null !== $useSubDirs)? $useSubDirs: self::$dfltUseSubDirs;
		$this->fileSuffix = $fileSuffix;
		if(! is_dir($this->cacheRootDir)){
			$umask = umask(0);
			$res= mkdir($this->cacheRootDir,0755,true);
			umask($umask);
			if(! $res ){
				throw new ErrorException("Can't create directory ".$this->cacheRootDir,0,E_USER_WARNING);
			}
		}
	}
	function getItem($name){
		$i = cacheItem::getInstance($name);
		if( empty($i->cacheTime) && empty($i->content) ){
			$path = $this->getItemPath($name);
			if( file_exists($path) && $tmp = file_get_contents($path) ){
				$i->content = $tmp;
				$i->cacheTime = $this->getFileTime($path);
			}
		}
		return $i;
	}
	function saveItem(cacheItem $item){
		if(empty($item->content))
			$this->removeItem($item);
		$item->cacheTime = date('Y-m-d H:i:s');
		$tmp = $this->getItemPath($item);
		if( is_file($tmp) )
			unlink($tmp);
		$tmp = $this->getItemPath($item,false).'/'.$item->name.'_'.preg_replace('!\D!','',$item->cacheTime).$this->fileSuffix;
		$umask = umask(0);
		if(! is_dir(dirname($tmp))){
			mkdir(dirname($tmp),0775,true);
		}
		if(false===file_put_contents($tmp,$item->content,LOCK_EX) ){
			umask($umask);
			return false;
		}
		chmod($tmp,0664);
		umask($umask);
		return true;
	}
	function removeItem($item){
		if( $item instanceof cacheItem)
			$tmp = $this->getItemPath($item,false).'/'.$item->name.'_'.preg_replace('!\D!','',$item->cacheTime);
		else
			$tmp = $this->getItemPath($item);
		cacheItem::dropInstance($item);
		if( is_file($tmp) ){
			unlink($tmp);
			if( $this->useSubDirs ){
				$res = glob($tmp=dirname($tmp).'/*');
				if( empty($res) )
					rmdir(dirname($tmp));
				$res = glob($tmp=dirname($tmp).'/*');
				if( empty($res) )
					rmdir(dirname($tmp));
			}
		}
	}
	function removeMatchingItem($exp){
		$files = glob($this->cacheRootDir.'/*'.($this->useSubDirs?'/*/*':''));
		cacheItem::clearMemory(null,$exp);
		foreach($files as $f){
			if( preg_match($exp,basename($f)))
				unlink($f);
		}
		return true;
	}
	function clear($olderThan=null){
		$files = glob($this->cacheRootDir.'/*'.($this->useSubDirs?'/*/*':'').$this->fileSuffix);
		$olderThan=date('Y-m-d H:i:s',time()-(int) $olderThan);
		cacheItem::clearMemory($olderThan);
		foreach($files as $f){
			if( $this->getFileTime($f) < $olderThan){
				unlink($f);
			}
		}
		return true;
	}

	protected function getFileTime($filePath){
		preg_match('!^.*_(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)'.str_replace('.','\.',$this->fileSuffix).'$!',$filePath,$m);
		return "$m[1]-$m[2]-$m[3] $m[4]:$m[5]:$m[6]";
	}

	protected function checkPath($path){
		if(! is_dir($path)){
			$umask = umask(0);
			mkdir($path,0775,true);
			umask($umask);
		}
	}

	function getItemPath($item,$full=true){
		if( $item instanceof $item){
			$item = $item->name;
		}
		if(! $this->useSubDirs ){
			$path = preg_replace('!/$!','',$this->cacheRootDir);
		}else{
			list(,$subPath1,$subPath2) = array_pad(preg_split('//',substr(preg_replace('![^a-zA-Z0-9_]!','_',$item),0,2),3),3,'_');
			$path  = preg_replace('!/$!','',$this->cacheRootDir)."/$subPath1/$subPath2";
		}
		if(! $full)
			return $path;
		$tmp = glob("$path/$item*".$this->fileSuffix);
		if( empty($tmp) )
			return $path;
		return $tmp[0];
	}
}


/**
* cache backend to store items in database.
* @require class-db
* @class dbCacheBackend
*/
class dbCacheBackend implements cacheBackend{
	/** database connection string to use for the backend @see class-db::getInstance() */
	static public $connectionStr = CACHE_DB_CONNECTION;
	/** in database table name to store the cached items */
	static public $tableName = '_cache';
	/** does the table need to be created automaticly if not already existing in database */
	static public $autoCreate=CACHE_DB_AUTOCREATE;
	private $db = null;
	function __construct(){
		$this->db = db::getInstance(self::$connectionStr);
		if( DEVEL_MODE )
			$this->db = new dbProfiler($this->db);
		#- check for table existence
		if( self::$autoCreate && false === $this->db->get_count(self::$tableName) ){
			$this->db->query("
				CREATE TABLE ".$this->db->protect_field_names(self::$tableName)."(
					name varchar(255) NOT NULL,
					content longtext NOT NULL,
					cacheTime datetime NOT NULL,
					PRIMARY KEY (name),
					KEY cacheTime (cacheTime)
				);"
			);
		}
	}
	/**
	* return a cacheItem from backend Storage by it's name
	* @param string $name
	* @return cacheItem
	*/
	function getItem($name){
		$i = cacheItem::getInstance($name);
		if(empty($i->cacheTime) && empty($i->content) ){
			$datas = $this->db->select_row(self::$tableName,'*',array('WHERE name=?',$name));
			if( false !== $datas)
				$i->setDatas($datas);
		}
		return $i;
	}
	/**
	* save a cacheItem to backend storage
	* @param cacheItem $Item
	* @return bool
	*/
	function saveItem(cacheItem $item){
		if(empty($item->content))
			$this->removeItem($item);
		$item->cacheTime = date('Y-m-d H:i:s');
		$values = $this->db->process_conds(array('(?)',$item->datas));
		return $this->db->query('REPLACE INTO '.self::$tableName.' (name,content,cacheTime) VALUES '.$values);
	}
	/**
	* remove a cacheItem from backend storage
	* @param mixed $Item cacheItem or item name
	* @return bool
	*/
	function removeItem($item){
		if( $item instanceof cacheItem)
			$item = $item->name;
		cacheItem::dropInstance($item);
		return $this->db->delete(self::$tableName,array('WHERE name=?',$item));
	}

	function removeMatchingItem($exp){
		#- first lookup item in memories
		cacheItem::clearMemory(null,$exp);
		$names = $this->db->select_col(self::$tableName,'name');
		if( ! empty($names)){
			$rem = array();
			foreach( $names as $name ){
				if( preg_match($exp,$name) )
					$rem[] = $name;
			}
			if( ! empty($rem) )
				return $this->db->delete(self::$tableName,array('WHERE name IN (?)',$rem));
		}
		return true;
	}

	/**
	* remove all cache items
	* @param int $olderThan if given only items older than the time given will be remove
	* @return bool
	*/
	function clear($olderThan=null){
		if( empty($olderThan) ){
			cacheItem::clearMemory();
			return $this->db->query('TRUNCATE '.self::$tableName);
		}
		$olderThan = date('Y-m-d H:i:s',time()-$olderThan);
		cacheItem::clearMemory($olderThan);
		return $this->db->delete(self::$tableName,array(' WHERE cacheTime < ?',$olderThan));
	}
}

/**
* cacheItem object implementing multiton pattern so only one instance of a cacheItem at a time will exists
* @class cacheItem
*/
class cacheItem{
	private $datas = array(
		'name'=>'',
		'content'=>'',
		'cacheTime'=>0
	);
	static private $_instances = array();

	private function __construct($name){
		$this->name = $name;
	}
	/**
	* return unique instance for the given cacheItem name
	*/
	static function getInstance($name){
		if(! isset( self::$_instances[$name]))
			self::$_instances[$name] = new cacheItem($name);
		return self::$_instances[$name];
	}
	/**
	* remove cacheItem instance from memory
	* @return null
	*/
	static function dropInstance($name){
		if( $name instanceof cacheItem )
			$name = $name->name;
		if( isset( self::$_instances[$name]))
			unset(self::$_instances[$name]);
		return null;
	}
	/**
	* remove all cacheItem instance from memory
	* @param dateTime $olderThan    if given then only cacheItem instance olderThan the time given will be removed from memory.
	* @param string   $matchingExp  if given then only item matching with name matching the given PCRE RegExp will be removed from memory
	* @return true
	*/
	static function clearMemory($olderThan=null,$matchingExp=null){
		if( null === $olderThan && null===$matchingExp){
			self::$_instances = array();
			return true;
		}

		foreach(self::$_instances as $i){
			if( (null===$olderThan || $i->cacheTime < $olderThan) && (null==$matchingExp || preg_match($matchingExp,$i->name)) )
				self::dropInstance($i->name);
		}
		return true;
	}
	/**
	* set all cacheItem instance datas at once
	* @return cacheItem $this
	*/
	function setDatas(array $datas){
		foreach($datas as $k=>$v)
			$this->__set($k,$v);
		return $this;
	}
	//-- magical methods to get/set item datas --//
	function __isset($k){
		return isset($this->datas[$k]);
	}
	function __set($k,$v){
		if(! isset($this->datas[$k]))
			return false;
		$this->datas[$k] = $v;
	}
	function __get($k){
		if(! isset($this->datas[$k]))
			return $k==='datas'?$this->datas:false;
		return $this->datas[$k];
	}
	/**
	* check that this is a valid cache item
	* @param int $maxAge the maximum age expressed in seconds of the item to be valid
	* @return bool
	*/
	function checkValididty($maxAge=null){
		#- check for validity
		if( empty($this->cacheTime) && empty($this->content) ){
			return false;
		}
		if( null !== $maxAge && (strtotime($this->cacheTime) + $maxAge) < time()){
			return false;
		}
		return true;
	}
}
