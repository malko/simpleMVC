<?php
/**
* @class fileManager
* @changelog
*            - 2010-05-24 - uploaded files are passed in listfilters.
*            - 2010-01-12 - set umask to 0 before upload and restore it.
*/
/*
if((! isset($_POST['dir'])) && isset($_GET['dir']))
	$_POST = $_GET;

$fm = new fileManager(dirname(dirname(__file__)).'/test',dirname(dirname($_SERVER['PHP_SELF'])).'/test');
echo $fm->processRequest();
*/
class fileManager{

	/** define the physic path to root dir */
	public $docRoot = null;
	/** define url path to root dir */
	public $urlRoot = null;
	/**
	* define a callback function to check accessibility of requested directory.
	* this value must be a callable callback and will receive requested path as first parameter.
	*/
	public $accessCB= null;

	public $elementId='';
	protected $_listFilters = array(
		'hidden'     => '!^[^\.]!',
		'file_extensions' => '!.*$!i',
	);

	public $options=array(
		'chmodDir'=>0775,
		'chmodFile'=>0664
	);

	function __construct($docRoot=null,$urlRoot=null,$accessCB=null){
		if( is_null($docRoot))
			$docRoot = dirname(__file__);
		if( is_null($urlRoot))
			$urlRoot = dirname($_SERVER['PHP_SELF']);
		if( substr($docRoot,-1)==='/')
			$docRoot = substr($docRoot,0,-1);
		if( substr($urlRoot,-1)==='/')
			$urlRoot = substr($urlRoot,0,-1);
		$this->docRoot = $docRoot;
		$this->urlRoot = $urlRoot;
		if(!is_null($accessCB))
			$this->accessCB = $accessCB;
	}

	function configure(array $options){
		foreach($options as $k=>$v)
			$this->options[$k] = $v;
	}
	/**
	* helper to ease the request processing will look possible commands and return appropriate result
	* @param array $requestDatas by default requested infos are checked from _POST datas, but you can override this by passing your own prepare request.
	* @return json response code
	*/
	function processRequest(array $requestDatas = null){
		//error_reporting(E_ALL^E_NOTICE^E_WARNING);
		if( null === $requestDatas)
			$requestDatas = $_POST;
		$additionalDatas = array();

		if( isset($requestDatas['fmanager']) )
			$additionalDatas['fmanager'] = $_REQUEST['fmanager'];

		if(! is_dir($this->docRoot))
			return $this->response($additionalDatas,array('error'=>"badConnectorConfig"));

		if( isset($_FILES['newfile']) && isset($requestDatas['basepath']))
			return $this->newfile($requestDatas['basepath'],true,$additionalDatas);

		if( isset($requestDatas['getinfos'])) //-- attempting directory creation
			return $this->getinfos($requestDatas['getinfos'],true,$additionalDatas);

		if( isset($requestDatas['newdir'])) //-- attempting directory creation
			return $this->newdir($requestDatas['newdir'],true,$additionalDatas);

		if( isset($requestDatas['unlink'])) //-- attempting file deletion
			return $this->unlink($requestDatas['unlink'],true,$additionalDatas);

		if( isset($requestDatas['listdir']) ) //-- ask a listing
			return $this->listDir($requestDatas['listdir'],true,$additionalDatas);

		return $this->response($additionalDatas,array('error'=>'bad request'));
	}

	function response(){
		$datas = array();
		$args = func_get_args();
		foreach($args as $a)
			$datas = array_merge($datas,$a);
		return json_encode($datas);
	}

	/**
	* check using callback function if current script has access or not to given path
	* @param string $path
	*/
	function checkAccess($path=''){
		if(is_null($this->accessCB))
			return true;
		if( strlen($path) && $path[0] !=='/')
			$path = "/$path";
		$res = call_user_func($this->accessCB,$this->docRoot.$path);
		return $res;
	}

	###--- FILTERING METHODS ---###
	/**
	* list of allowed extensions without '.' and separated by '|'
	* @param string $exts
	* @return this for method chaining
	*/
	public function listSetAllowedExtension($exts=null){
		$this->listSetFilter('file_extensions',empty($exts)?null:"!\.($exts)!i");
		return $this;
	}
	/**
	* set a filter that must be matched by dirname/filename to be included in the response list
	* @param string $filterName name for the filter
	*                          filter names starting by file_ or dir_ will only apply to files or directories
	*                          other will be applied to both files and dirs
	* @param string filterExp a valid PCRE RegExp
	* @return this for method chaining
	*/
	public function listSetFilter($filterName,$filterExp=null){
		if( null === $filterExp ){
			if( isset($this->_listFilters[$filterName]) )
				unset($this->_listFilters[$filterName]);
			return $this;
		}
		$this->_listFilters[$filterName] = $filterExp;
		return $this;
	}

	/**
	* apply filter to given item
	* @param string $item
	* @param bool  $isDir true if filtering a directory item
	* @return bool where false mean rejected
	*/
	public function listApplyFilter($item,$isDir=false){
		if( empty($this->_listFilters) )
			return true;
		foreach($this->_listFilters as $filterName=>$filterExp){
			if( $isDir ){
				if(strpos($filterName,'file_')===0)
					continue;
			}elseif(strpos($filterName,'dir_')===0){
				continue;
			}
			if(! preg_match($filterExp,$item) )
				return false;
		}
		return true;
	}

	###--- LISTING METHOD ---###
	/**
	* main listing method, return list of items inside the given directory
	* @param string $path        path relative to $this->docRoot to list
	* @param bool   $decodeFirst set this to true if path is urlencoded
	* @return string HTML list of elements
	*/
	public function listdir($path,$decodeFirst = false,array $additionalDatas=null){
		$path = self::cleanPath($decodeFirst?urldecode($path):$path);
		if(! $this->checkAccess($path) )
			return $this->response($additionalDatas,array('error'=>'access denied'));
		#- return directory contents:
		if( strlen($path) && substr($path,-1) !=='/' )
			$path.='/';
		$res = scandir($fullPath ="$this->docRoot$path");
		$dirs = array();
		$files= array();
		foreach($res as $e){
			$isDir = is_dir("$fullPath$e");
			if(! $this->listApplyFilter($e,$isDir))
				continue;
			$url = "$this->urlRoot$path$e";
			if($isDir)
				$dirs[] = array('name'=>$e,'fullpath'=>"$path$e",'url'=>$url,'locked'=>!is_readable("$fullPath$e"));
			elseif(is_file("$fullPath$e"))
				$files[] = array('name'=>$e,'fullpath'=>"$path$e",'url'=>$url,'locked'=>!is_readable("$fullPath$e"),'ext'=>preg_replace('!^.*?\.([^.]+)$!','$1',$e));
		}
		return $this->response($additionalDatas,$dirs,$files);
	}


	function getinfos($item,$decodeFirst=false,array $additionalDatas=null){
		clearstatcache();
		$item = self::cleanPath($decodeFirst?urldecode($item):$item);
		if(! $this->checkAccess($item) )
			return $this->response($additionalDatas,array('error'=>'access denied'));
		$realPath = "$this->docRoot$item";
		$ret['fullpath']=$item;
		$ret['basepath']=dirname($item);
		$ret['mtime']=filemtime($realPath);
		$ret['name']=basename($item);
		$ret['user'] =fileowner($realPath);
		$ret['group']=filegroup($realPath);
		if( function_exists('posix_getpwuid')){
			$ret['user']=posix_getpwuid($ret['user']);$ret['user']=$ret['user']['name'];
			$ret['group']=posix_getpwuid($ret['group']);$ret['group']=$ret['group']['name'];
		}
		$ret['perms']= substr(decoct(fileperms($realPath)),-4);
		if(is_file($realPath)){
			$ret['ext'] = preg_replace('!^.*?\.([^.]+)$!','$1',$item);
			$ret['size'] = self::byteConvert(filesize($realPath));
			$ret['url'] = "$this->urlRoot$item";
		}else{
			$ret['size'] = count(glob($realPath.(strrpos($realPath,'/')!==false?'/*':'*'))).' éléments';
		}
		return $this->response($additionalDatas,$ret);
	}

	function newdir($path,$decodeFirst=false,array $additionalDatas=null){
		$path = self::cleanPath($decodeFirst?urldecode($path):$path);
		if(! $this->checkAccess($path) )
			return $this->response($additionalDatas,array('error'=>'access denied'));
		$realPath="$this->docRoot$path";
		if( is_dir($realPath))
			return $this->response($additionalDatas,array('error'=>'dirAlreadyExists'));
		$res =  mkdir($realPath,0777,true);
		if( $res && !empty($this->options['chmodDir']))
			chmod($realPath,$this->options['chmodDir']);
		return $this->response(
			$additionalDatas,
			$res?array('success'=>"new directory created.",'basepath'=>dirname($path)):array('error'=>"operation failed.")
		);
	}

	function unlink($path,$decodeFirst=false,array $additionalDatas=null){
		$path = self::cleanPath($decodeFirst?urldecode($path):$path);
		if(! $this->checkAccess($path) )
			return $this->response($additionalDatas,array('error'=>'access denied'));
		$realPath = "$this->docRoot$path";
		$res = is_dir($realPath)?rmdir($realPath):unlink($realPath);
		return $this->response(
			$additionalDatas,
			$res? array('success'=>"file deleted.",'basepath'=>dirname($path)):array('error'=>"operation failed.")
		);
	}

	function newfile($path,$decodeFirst=false,array $additionalDatas=null){
		$path = self::cleanPath($decodeFirst?urldecode($path):$path);
		if(! $this->checkAccess($path) )
			$this->response($additionalDatas,array('error'=>'access denied'));
		$realPath = "$this->docRoot$path";
		if( substr($realPath,-1) !== '/')
			$realPath.='/';
		if( ! is_writable($realPath))
			return $this->response($additionalDatas,array('error'=>"directoryNotWritable",'basepath'=>$path));
		$destPath = $realPath.$_FILES['newfile']['name'];
		if(! $this->listApplyFilter($destPath) ){
			return $this->response($additionalDatas,array('error'=>"badFileType",'basepath'=>$path));
		}
		if( file_exists($destPath))
			return $this->response($additionalDatas,array('error'=>"fileAlreadyExists",'basepath'=>$path));
		$uMask = umask(0);
		$res = move_uploaded_file($_FILES['newfile']['tmp_name'],$destPath);
		umask($uMask);
		return $this->response(
			$additionalDatas,
			array('basepath'=>$path),
			$res?array('sucess'=>'file upload succeed'):array('error'=>'operation failed')
		);

	}

	static function cleanPath($path){
		$path = preg_replace('/(^\.+)|(?<!^)\/$/','',$path);//-remove trailing slash
		$path = preg_replace(array('!\.\.+!','!//+!'),'',$path);//- remove more than one successive dots
		if( (!strlen($path)) || $path[0] !=='/' ) #- no starting slash
			$path = "/$path";
		return $path;

	}

	/** from php manual */
	static function byteConvert($bytes){
		if( $bytes <= 0)
			return '0 o';
		$s = array('o', 'Ko', 'Mo', 'Go', 'To', 'Po');
		$e = floor(log($bytes,1024));
		return sprintf('%.1f '.$s[$e], ($bytes/pow(1024,$e)));
	}


}
