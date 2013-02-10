<?php
class smvcServerClientException extends Exception{}
class smvcServerClient{
	protected $remoteSocket=null;
	protected $socket=null;
	protected $timeout=null;
	protected $connectionAttempt = 0;
	protected $blocking = 1;

	CONST DELAY_BASE_USEC = 1;
	CONST DELAY_BASE_MSEC = 1000;
	CONST DELAY_BASE_SEC = 1000000;

	protected function __construct(){}

	/**
	 * return a new smvcServiceClient instance
	 * @param string $remoteSocket tcp://ip:port
	 * @param int $timeout timeout in seconds use for connection and read/write opérations
	 * @param Boolean $autoConnect if true will try a simple connection (no backoff retry)
	 * @return smvcServerClient
	 */
	static public function getInstance($remoteSocket,$timeout=null,$autoConnect=false){
		$client = new smvcServerClient();
		$client->remoteSocket = $remoteSocket;
		$client->setTimeout($timeout);
		if( $autoConnect ){
			$client->connect();
		}
		return $client;
	}

	public function getRemoteSocketAdress(){ return $this->remoteSocket; }
	public function getStreamResource(){ return $this->socket; }
	public function eot(){
		return feof($this->socket);
	}

	public function connect(){
		$this->disconnect();
		$this->connectionAttempt++;
		$this->socket = @stream_socket_client($this->remoteSocket,$errno,$errstr,$this->timeout);
		if( false === $this->socket ){
			throw new smvcServerClientException($errstr,$errno);
		}
		$this->connectionAttempt = 0;
		$this->setTimeout($this->timeout);
		$this->setBlocking($this->blocking);
		stream_set_write_buffer($this->socket,0);
		return $this;
	}

	/**
	* like open but with exponential backoff retry
	* @param int $maxRetry max attempt to connect before throwing a connection error
	* @param int $maxDelay max time in seconds to wait between to calls 0 or negative values will not cap the exponential backoff time
	* @param int $delayUnitFactor use self::DELAY_BASE_* constants to make it work as sec, msec, µsec default
	* @return $this for method chaining or throw smvcServerClientException
	*/
	public function connectWithExponentialBackoff($maxRetry=10,$maxDelay=0,$delayUnitFactor=smvcServerClient::DELAY_BASE_MSEC){
		try{
			self::exponentialBackoffDelay($this->connectionAttempt,$maxDelay,$delayUnitFactor);
			$this->connect();
			return $this;
		}catch(smvcServerClientException $e){
			if( $this->connectionAttempt > $maxRetry ){
				$this->connectionAttempt=0; // reset connection attempt for next call
				throw $e;
			}
			return $this->connectWithExponentialBackoff($maxRetry,$maxDelay,$delayUnitFactor);
		}
	}

	/**
	* make a usleep exponentialy on each retry
	* @param int $retry number of failed previous attempt
	* @param int $maxDelay max time in seconds to wait between to calls 0 or negative values will not cap the exponential backoff time (may be a float)
	* @param int $delayUnitFactor use self::DELAY_BASE_* constants to make it work as sec, msec, µsec default
	*/
	static public function exponentialBackoffDelay($retry,$maxDelay=0,$delayUnitFactor=smvcServerClient::DELAY_BASE_MSEC){
		$randomBackoff = rand(0, 1000); // randomize additional delay between 0 to 1milli second
		if( $retry < 1){
			return;
		}
		$backoffTime = ((1 << ($retry-1))*$delayUnitFactor);
		if( $maxDelay > 0){
			$backoffTime = min($backoffTime,$maxDelay*smvcServerClient::DELAY_BASE_SEC);
		}
		usleep( $backoffTime+$randomBackoff );
	}

	public function disconnect(){
		if( is_resource($this->socket) ){
			#- fclose($this->socket);
			stream_socket_shutdown($this->socket,STREAM_SHUT_RDWR);
		}
		$this->socket = null;
		return $this;
	}
	public function setBlocking($blocking=1){
		if( $this->blocking !== $blocking ){
			$this->blocking = $blocking?1:0;
		}
		if( is_resource($this->socket) ){
			stream_set_blocking($this->socket,$this->blocking);
		}
		return $this;
	}
	public function setTimeout($timeout=null){
		$this->timeout = null!==$timeout? $timeout : ini_get("default_socket_timeout");
		if( null !== $this->socket ){
			stream_set_timeout($this->socket,$this->timeout);
		}
		return $this;
	}

	/**
	* send a message to the server
	* @param string $str message to send
	* @param Boolean $disconnect if true will automaticly disconnect the client
	* @return int bytes writed or false on error
	*/
	public function tell($str,$disconnect=false){
		if(! $this->socket ){
			$this->connect();
		}
		#- for( $writed=0,$expectedLength = self::_strlen($str); $writed < $expectedLength; ){
			$w = fwrite($this->socket,$str);
			if( $w === false || 0 === $w){
				throw new smvcServerClientException('Error while trying to write some data');
			}
			#- $writed += $w;
		#- }
		if( $disconnect ){
			$this->disconnect();
		}
		return $w;
	}

	/**
	* send a message and read the server response
	* @param string $str message to send
	* @param Boolean $disconnect if true will automaticly disconnect the client
	* @return string the server response or false on write error (throw an smvcServerClientException on reading error)
	*/
	public function talk($str,$disconnect=false){
		$res = $this->tell($str,false);
		if( $res === false ){
			return false;
		};
		$res = $this->read();
		if( $disconnect ){
			$this->disconnect();
		}
		return $res;
	}

	/**
	* read message from the server
	* @return string the server response or throw an smvcServerClientException on error
	*/
	public function read($length=512){
		$res = '';
		while(true){
			$buf = fread($this->socket,$length);
			if( false === $buf ){
				throw new smvcServerClientException("Error while reading from ");
			}
			$res.=$buf;
			if( $this->_strlen($buf) < $length ){
				break;
			}
		}
		#- if( $res === '' || $res === chr(4)){
			#- return false;
		#- }
		return $res;
	}

	/**
	* binary safe strlen replacement for mb_overloaded environments
	* @internal
	*/
	protected function _strlen($str){
		static $strlen;
		if(! isset($strlen)){ //-- detect environment only once
			if( function_exists('mb_strlen') && (ini_get('mbstring.func_overload') & 2) ){
				$strlen = create_function('$str','return mb_strlen($str,"8bit");');
			}else{
				$strlen = 'strlen';
			}
		}
		return $strlen($str);
	}

	function getName($remote=false){
		return stream_socket_get_name($this->socket,$remote);
	}
}
