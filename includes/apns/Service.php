<?php

class apnsServiceException extends Exception{}

class apnsService{
	const PRODUCION_SERVICE = 'ssl://gateway.push.apple.com:2195';
	const SANDBOX_SERVICE = 'ssl://gateway.sandbox.push.apple.com:2195';
	const PRODUCION_FEEDBACK_SERVICE = 'ssl://feedback.push.apple.com:2196';
	const SANDBOX_FEEDBACK_SERVICE = 'ssl://feedback.sandbox.push.apple.com:2196';

	static public $apnsErrorCodes = array(
		0 => 'No errors encountered'
		,1=> 'Processing error'
		,2 => 'Missing device token'
		,3 => 'Missing topic'
		,4 => 'Missing payload'
		,5 => 'Invalid token size'
		,6 => 'Invalid topic size'
		,7 => 'Invalid payload size'
		,8 => 'Invalid token'
		,255 => 'None (unknown)'
	);

	static public $rootCAFile = null;
	static public $backOffCallback =array(
		__class__
		,'_backoff'
	);

	public $timeout = 60;
	protected $cert;
	protected $serviceEndpoint;
	protected $socket;
	protected $context;

	protected function __construct($serviceEndpoint,$applicationPemCertFile,$passphrase){
		$this->serviceEndpoint = $serviceEndpoint;
		if(! (is_file($applicationPemCertFile) && is_readable($applicationPemCertFile)) ){
			throw new apnsServiceException('Certificate doesn\'t exists or isn\'t readable');
		}
		$this->cert = $applicationPemCertFile;

		$this->context = stream_context_create(array(
			'ssl'=>array(
				'local_cert' => $this->cert
			)
		));
		if( is_string($passphrase) ){
			stream_context_set_option($this->context,'ssl','passphrase',$passphrase);
		}
		//-- trying to use default root CA file if none set
		$rootCAFile = isset(self::$rootCAFile) ? self::$rootCAFile : dirname(__file__).'/entrust_root_certification_autorithy.pem';
		if( ! (is_file($rootCAFile) && is_readable($rootCAFile)) ){
				$rootCAFile = null;
		}
		if( $rootCAFile ){
			stream_context_set_option($this->context,'ssl','verify_peer',true);
			stream_context_set_option($this->context,'ssl','cafile',$rootCAFile);
		}
	}

	/**
	* @return apnsService for production environnement
	*/
	public function getProductionService($applicationPemCertFile,$passphrase){
		return new self(self::PRODUCION_SERVICE,$applicationPemCertFile,$passphrase);
	}

	/**
	* @return apnsService for sandbox environnement
	*/
	public function getSandboxService($applicationPemCertFile,$passphrase){
		return new self(self::SANDBOX_SERVICE,$applicationPemCertFile,$passphrase);
	}
	/**
	* @return apnsService for production  environnement feedback
	*/
	public function getProductionFeedbackService($applicationPemCertFile,$passphrase){
		return new self(self::PRODUCION_FEEDBACK_SERVICE,$applicationPemCertFile,$passphrase);
	}
	/**
	* @return apnsService for sandbox  environnement feedback
	*/
	public function getSandboxFeedbackService($applicationPemCertFile,$passphrase){
		return new self(self::SANDBOX_FEEDBACK_SERVICE,$applicationPemCertFile,$passphrase);
	}

	/**
	* you should use openWithRetry() method instead
	* @return $this for method chaining or Throw an exception on error
	*/
	public function open(){
		$this->socket = stream_socket_client(
			$this->serviceEndpoint
			,$errNo
			,$errString
			,$this->timeout
			,STREAM_CLIENT_CONNECT//|STREAM_CLIENT_PERSISTENT
			,$this->context
		);
		if(! $this->socket ){
			throw new apnsServiceException("apnsService connection error($errNo): $errString",$errNo);
		}
		if(! strpos($this->serviceEndpoint,'feedback') ){
			stream_set_blocking($this->socket,0);
		}else{
			stream_set_blocking($this->socket,1);
		}
		stream_set_write_buffer($this->socket,0);
		return $this;
	}

	/**
	* like open but with exponential backoff retry
	* @param int $maxRetry max attempt to connect before throwing a connection error
	* @param int $exponentialRetryDelay exponential delay in seconds to wait before retry
	* @return $this for method chaining or throw an exception
	*/
	public function openBackoff($maxRetry=10,$exponentialRetryDelay=2){
		static $attempt=0;
		try{
			if( $attempt > 0 ){
				call_user_func(self::$backOffCallback,(1 << ($attempt-1)) * $exponentialRetryDelay,$this);
			}
			$this->open();
			$attempt=0;
			return $this;
		}catch(apnsServiceException $e){
			if( ++$attempt > $maxRetry ){
				throw $e;
			}
			$this->openBackoff($maxRetry-1,$exponentialRetryDelay);
		}
	}

	/**
	* default method for backoff callback (simply a call to sleep)
	* @param int $amount number of seconds to wait
	* @param apnsService $serviceInstance service instance who originated the backoff
	* @param string $backOffType one of 'connection','message','feedback'
	*/
	private static function _backoff($amount,apnsService $serviceInstance,$backOffType){
		return sleep($amount);
	}

	/** close current connection
	* @return $this for method chaining
	*/
	public function close(){
		if( is_resource($this->socket) ){
			fclose($this->socket);
		}
		$this->socket = null;
		return $this;
	}

	public function send($stringMessage){
		echo "ok -> ";print_r($stringMessage);
		if( function_exists('logMsg')){
			logMsg('apns service ok -> '.$stringMessage .' '.$this->socket);
		}
		if(! $this->socket){
			throw new apnsServiceException('apnsService::send() you must have an open connection prior to call send method');
		}
		for($written=0, $msgLength=mb_strlen($stringMessage,'latin1'); $written < $msgLength; $written += $BWritten){
			$BWritten = fwrite($this->socket, substr($stringMessage, $written));
			if($BWritten === false || ($BWritten === 0 && $msgLength > $written)) {
				throw new apnsServiceException('apnsService::send() error while writting');
			}
		}
		#- $error = $this->getError(0);
		#- if( $error ){
			#- throw new apnsServiceException(
				#- "apnsService::send() message (id: $error[identifier]) encounter error '"
				#- .(isset(self::$apnsErrorCodes[$error['status']])?self::$apnsErrorCodes[$error['status']] : 'UNKNOWN' )
				#- ."' in delivery"
				#- ,isset($error['status'])?$error['status']:255
			#- );
		#- }
		return $this;
	}

	/**
	 * return last pending apns error since last call to this method.
	 */
	public function getError($uTimeout=0){
		//-- message is written now eventually try to read a response
		$read = array($this->socket);
		$write = $excepts = null;
		$modifiedStream =  stream_select($read,$write,$excepts,0,$uTimeout);
		if( false === $modifiedStream ){
			throw new apnsServiceException('apnsService::getError stream_select error');
		}else if( 0 === $modifiedStream ){
			return null;
		}
		$error = null;
		while( $apnsErrorMessage = fread($this->socket,6)){
			$apnsErrorMessage = unpack('Ccommand/Cstatus/Nidentifier', $apnsErrorMessage);
			if( isset($apnsErrorMessage['command']) ){
				if( $apnsErrorMessage['command'] !== 8 ){
					throw new apnsServiceException('Unknown apns server response');
				}
				$error = $apnsErrorMessage;
			}
		}
		return $error;
	}

	public function getFeedback(){
		$feedBackRows = array();
		while( $feedBackItem = fread($this->socket,38) ){
			$feedBackRows[] = unpack('Ntimestamp/ntokenLength/H*deviceToken', $feedBackItem);
		}
		return $feedBackRows;
	}

}
