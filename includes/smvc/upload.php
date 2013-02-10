<?php
/**
* @file
* ease upload file management
* @author jonathan gotti <jgotti at modedemploi dot fr> for agence-modedemploi
* @since 2012-07-31
*/
class smvcUploadException extends Exception{
	public static $UPLOAD_ERRORS = array(
		UPLOAD_ERR_OK            => ""
		,UPLOAD_ERR_INI_SIZE     => "Uploaded file exceeds upload_max_filesize directive specified server side."
		,UPLOAD_ERR_FORM_SIZE    => "Uploaded file exceeds MAX_FILE_SIZE directive specified client side."
		,UPLOAD_ERR_PARTIAL      => "Uploaded file was partially uploaded."
		,UPLOAD_ERR_NO_FILE      => "No file uploaded"
		,UPLOAD_ERR_NO_TMP_DIR   => "No temporary directory."
		,UPLOAD_ERR_CANT_WRITE   => "Can't write uploaded file to disk."
		,UPLOAD_ERR_EXTENSION    => "An extension stopped the file upload."
		,'INVALID_FILE_NAME'     => "Try to work on an invalid uploaded file name."
		,'DEST_DIR_NOT_WRITEABLE'=> "Destination directory isn't writeable."
		,'DEST_DIR_NOT_CREATED'  => "Destination directory can't be created."
		,'BAD_FILE_EXT'          => "Uploaded file has an unauthorized file name extension."
		,'MOVE_ERROR'            => "Uploaded file can't be moved."
	);
	public function __construct( $message = "", $code = 0, Exception $previous = NULL ){
		if( isset(self::$UPLOAD_ERRORS[$message]) ){
			if($code === 0 && is_int($message) ){
				$code = $message;
			}
			$message = self::$UPLOAD_ERRORS[$message];
		}
		return ( $previous !== null ) ? parent::__construct( $message, $code, $previous ) : parent::__construct( $message, $code );
	}
}

/**
* class to ease uploaded file treatment
*/
class smvcUpload{
	public static $umask = 0022;
	public static $executable = false;
	private static $propIndexes = array('name','type','tmp_name','error','size');

	private $file = null;
	private $_umask = null;
	private $_isMultiple = false;
	private $_executable = false;
	private $_authorizedExtensions = false;
	private $_uploaded = array();


	/**
	 * @see getInstance() method instead
	 * @param string $fileName name of the input file
	 * @param string $authorizedExtensions pipe separated list of allowed extension (not a regexp)
	 * @param int $umask umask octal value
	 */
	public function __construct($fileName,$authorizedExtensions=null,$umask=null){
		// look up for the file
		$this->stdFileArrayByName($fileName);
		$this->setUmask($umask);
		if( null === $this->file ){
			throw new smvcUploadException('INVALID_FILE_NAME');
		}
		$this->_authorizedExtensions = '!\.('.$authorizedExtensions.')$!i';
	}

	/**
	* factory method for chaining purpose
	* @param string $fileName input file name as declared in the name attribute filename[0] can be used
	* @param string $authorizedExtensions pipe separated of authorized file extensions
	* @param int    $umask    umask for uploaded file and dirs creation
	* @return smvcUpload
	*/
	static function getIntance($fileName,$authorizedExtensions=null,$umask=null){
		return new self($fileName,$authorizedExtensions,$umask);
	}

	function setUmask($umask=null){
		$this->_umask = $umask !== null ? $umask : self::$umask;
	}

	/**
	 * save file or files to given destination.
	 * if error appends it will automaticly roll back and remove eventually part of file uploaded
	 * @param string $path physical path where to save files
	 * @param mixed $fileName optional filename to use may be:
	 *                        - null to keep original filename
	 *                        - a string containing full filename
	 *                        - a string containing filename without extension which will then be appended the original filename extension
	 *                        - a callable callback which will receive filename and file Index as first and second parameters
	 */
	public function saveTo($path,$fileName=null){
		if( substr($path,-1) !== '/' ){
			$path .= '/';
		}
		if( ! is_dir($path) ){ // try to create directory
			$this->mkdir($path);
		}
		if(! is_writable($path) ){
			throw new smvcUploadException('DEST_DIR_NOT_WRITEABLE');
		}
		if(! $this->_isMultiple ){
			if( !preg_match($this->_authorizedExtensions,$this->file['name']) ){
				throw new smvcUploadException('BAD_FILE_EXT');
			}
			$destFileName = $path.$this->fileName($this->file,$fileName);
			if(! move_uploaded_file($this->file['tmp_name'],$destFileName) ){
				throw new smvcUploadException('MOVE_ERROR');
			}
			chmod($destFileName,0777-$this->_umask-($this->_executable?0111:0));
			$this->_uploaded[] = $destFileName;
		}else{
			foreach($this->file as $k=>$v){
				if( $v['error'] && $v['error'] !== UPLOAD_ERR_NO_FILE ){
					return $this->rollback($v['error']);
				}else if( $v['error'] === UPLOAD_ERR_NO_FILE ){
					continue; // think of an option about empty files
				}
				if( !preg_match($this->_authorizedExtensions,$v['name']) ){
					return $this->rollback('BAD_FILE_EXT');
				}
				$destFileName = $path.$this->fileName($v,$fileName,$k);
				if( move_uploaded_file($v['tmp_name'],$destFileName) ){
					chmod($destFileName,0777-$this->_umask-($this->_executable?0111:0));
					$this->_uploaded[] = $destFileName;
				}else{
					return $this->rollback('MOVE_ERROR');
				}
			}
		}
	}

	/**
	 * @see saveTo method for more info
	 * @internal
	 * @param array $file standardized file array @see stdFileArrayByName
	 * @param mixed $fileName * @see parameter $fileName of saveTo method for more info
	 * @param int $fileIndex optional file index in case of multiple file upload
	 * @return string
	 */
	protected function fileName($file,$fileName=null,$fileIndex=null){
		if( null === $fileName ){
			return $fileIndex!==null? $file['name'][$fileIndex] : $file['name'];
		}else if( is_callable($fileName) ){
			$fileName = $fileName($fileIndex!==null? $file['name'][$fileIndex] : $file['name'], $fileIndex);
		}else if( !preg_match('!\.[a-z0-9]{2,}!i',$fileName) ){
			$ext = preg_replace('!.*(?=\.[a-z0-9]{2,}$)!','',$fileIndex!==null? $file['name'][$fileIndex] : $file['name']);
			return $fileName.$ext;
		}else{
			return $fileName;
		}
	}

	/**
	 * standardize file array from given input file name
	 * @internal
	 * @param string $fileName input file name
	 * @return array
	 */
	protected function stdFileArrayByName($fileName){
		if( ! isset($_FILES) ){
			return false;
		}
		if( $this->isStdFileArray($_FILES[$fileName]) ){
			$this->file = $_FILES[$fileName];
			if( is_array($this->file['name']) ){
				$this->_isMultiple = true;
			}
		}
		if( strpos($fileName,'[') !== false){
			$fileIndex= explode('[',str_replace(']','',substr($fileName,strpos($fileName,'[')+1)));
			$fileName = substr($fileName,0,strpos($fileName,'['));
			if( ! $this->isStdFileArray($_FILES[$fileName]) ){
				return false;
			}

			$file = $_FILES[$fileName];
			foreach( $fileIndex as $key){
				foreach(self::$propIndexes as $propName){
					$file[$propName] = $file[$propName][$key];
				}
			}
			if( $this->isStdFileArray($file) ){
				$this->file = $file;
				if( is_array($this->file['name']) ){
					$this->_isMultiple = true;
				}
			}
		}
	}

	/**
	 * @internal
	 */
	protected function isStdFileArray(&$array){
		foreach( self::$propIndexes as $propName ){
			if(! isset($array[$propName]) ){
				return false;
			}
		}
		return true;
	}

	/**
	 * umasked mkdir for internal usage
	 * @internal
	 */
	protected function mkdir($path){
		$umask = umask($this->_umask);
		if(! mkdir($path,0777,true) ){
			throw new smvcUploadException('DEST_DIR_NOT_CREATED');
		}
		umask($umask);
		return $this;
	}

	/**
	 * delete any file previously saved by the instance
	 * it is used internally if an error occured but you can also use it in your own logic.
	 * @param string $err optional error message to raise a smvcUploadException
	 * @return $this for method chaining
	 */
	function rollback($err=null){
		foreach($this->_uploaded as $u){
			unlink($u);
		}
		$this->uploaded = array();
		if( null !== $err){
			throw new smvcUploadException($err);
		}
		return $this;
	}
}
