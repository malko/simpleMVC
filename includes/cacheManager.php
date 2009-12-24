<?php
/**
* @since 2009-11-19
* @package cacheManager
*/

if(! defined('CACHE_MANAGER_ENABLE') )
	define('CACHE_MANAGER_ENABLE',true);
if(! defined('CACHE_MANAGER_AUTOCLEAR') )
	define('CACHE_MANAGER_AUTOCLEAR',true);
if(! defined('CACHE_MANAGER_TTL') )
	define('CACHE_MANAGER_TTL','30 minutes');
if(! defined('CACHE_DB_AUTOCREATE')){
	$dbCacheBackendAutoCreate	= constant('DEVEL_MODE')?true:false;
	define('CACHE_DB_AUTOCREATE',$dbCacheBackendAutoCreate);
}
class cacheManager{
	/** storage backend to use */
	static public $useBackend='dbCacheBackend';
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

	private function __construct(){}

	/**
	* initialize the backend to use and eventually clean too old items from storage
	* normally you don't need to call this method on your own.
	*/
	static function init(){
		if( ! self::$backend instanceof self::$useBackend){
			self::$backend = new self::$useBackend();
			if( self::$autoClear )
				self::clear(self::$ttl);
		}
	}
	/**
	* start to cache one item (be aware that this method use output buffering methods)
	* cached items must be start/end in a FirstInLastOut order
	*/
	static function setStart($name){
		ob_start();
	}
	/**
	* set the previously started item and stop buffering the output.
	* as thoose methods use output buffering they must be ended in the reverse order they was started
	* so the first started must be the last ended.
	* In the same way you really should avoid to use output buffering inside the portion of code you try to cache.
	*/
	static function setEnd($name){
		return self::set($name,ob_get_clean());
	}
	/**
	* return a cacheItem from used storage backend
	* @param string $name the cacheItem name
	* @param string $maxAge the max age of the item to be return
	* @return cacheItem or null
	*/
	static function get($name,$maxAge=null){
		if(! self::$enable )
			return null;
		self::init();
		$maxAge = self::_ttl(null===$maxAge?self::$ttl : $maxAge);
		$i = self::$backend->getItem($name);
		#- check for validity
		if( empty($i->cacheTime) && empty($i->content) )
			return cacheItem::dropInstance($name);
		$time = strtotime($i->cacheTime) + $maxAge;
		if( $time < time() )
			return cacheItem::dropInstance($name);
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
		$i->cacheTime = date('Y-m-d H:i:s');
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
	function saveItem(cacheItem $item);
	function removeItem($item);
	function getItem($name);
}

/**
* cache backend to store items in database.
* @require class-db
* @class dbCacheBackend
*/
class dbCacheBackend implements cacheBackend {
	/** database connection string to use for the backend @see class-db::getInstance() */
	static public $connectionStr = DB_CONNECTION;
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
	/**
	* remove all cache items
	* @param int $olderThan if given only items olde than the time given will be remove
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
	* @param dateTime $olderThan if given then only cacheItem instance olderThan the time given will be removed from memory.
	* @return true
	*/
	static function clearMemory($olderThan=null){
		if( null === $olderThan ){
			self::$_instances = array();
			return true;
		}
		foreach(self::$_instances as $i){
			if( $i->cacheTime < $olderThan )
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
}
