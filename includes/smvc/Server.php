<?php
/**
* really basic server that will handle requests by calling a user defined callback
* here's a sample usage:
* @example
	smvcServer::init(
		function($clientRawDataRequest,$clientStream,$server){
			..handle requests here..
			fwrite($clientStream,$response);
			.. optionally close client Connection ..
			$server->closeConnection($clientStream);
		}
		,$localSocket (tcp://0.0.0.0:8080)
	)
*/
class smvcServerException extends Exception{}
class smvcServer{
	public $socket = null;
	public $localSocket = '';
	public $callBacks = array(
		'incoming'          => null // incoming datas from client => function($incomingData,$clientStream,$server)
		,'start'            => null // server just started to listen => function($server)
		,'connectionAccept' => null // accepted client connection => function($clientStream,$server)
		,'connectionClose'  => null // server close client connection => function($clientStream,$server)
		,'connectionEOT'    => null // client end of transmission (lost connection, empty incoming data or ctrl+d received) => function($clientStream,$server)
		,'idle'             => null // will happen each time stream_select return 0 (must set selectTimeout for the event to get triggered) good place for task that need to run in background function($server)
	);
	public $errno=null;
	public $errstr=null;
	public $watched = array();
	public $clients = array();

	protected $selectTimeout = null;

	/** force use of init() factory method */
	protected function __construct(){}

	/**
	* return an instance of a tcp server
	* @param mixed $callbacks may be a single callable arguments which will be used as incoming callback
	*                         function called on client received datas ie: function($datas,$clientStream,$server){}
	*                         or a list of callable callbacks as defined in smvcServer::$callbacks
	* @param string $localSocket @see stream_socket_server local_socket parameter (transport://target)
	* @param ing    $selectTimeout if null then will wait indefinitely on stream_select, else will trigger an idle event if stream_select return 0 after this time.
	* @return communicateServer
	*/
	public static function init($callbacks,$localSocket,$selectTimeout=null){
		$s = new self();
		if( is_callable($callbacks) ){
			$s->callBacks['incoming'] = $callbacks;
		}else{
			$validEvents = array_keys($s->callBacks);
			foreach($callbacks as $event=>$cb){
				if(! (is_callable($cb) && in_array($event,$validEvents)) ){
					throw new smvcServerException("attempt to init server with invalid $event callback $cb");
				}
				$s->callBacks[$event] = $cb;
			}
		}
		$s->selectTimeout = $selectTimeout;
		$s->localSocket = $localSocket;
		return $s;
	}

	/**
	* self explanatory
	*/
	public function start(){
		$this->socket = stream_socket_server($this->localSocket,$this->errno,$this->errstr);
		if( false === $this->socket ){
			echo "ERROR[$this->errno]: $this->errstr\n";
			return null;
		}
		stream_set_blocking($this->socket,0);
		$this->watched[] = $this->socket;
		echo "starting server\n";
		return $this->listen();
	}
	/**
	* close all connection and will stop listening
	* @return $this for method chainging
	*/
	public function stop(){
		echo "closing all active connection\n";
		foreach( $this->watched as $stream){
			$this->closeConnection($stream);
		}
		$this->socket = null;
		$this->watched = $this->clients = array();
		return $this;
	}

	/**
	* server listening loop
	*/
	protected function listen(){
		$execStartCallback = true;
		while($this->socket){
			$read = $this->watched;
			$write = $except = null;
			$except = $read;
			if( $execStartCallback){
				$this->_do_event('start',array($this));
				$execStartCallback = false;
			}
			$selected = stream_select( $read, $write, $except, $this->selectTimeout );
			if( $selected === false ){
				echo "stream selection failed...\n";
				break;
			}
			if( $selected===0 ){
				$this->_do_event('idle',array($this));
				continue;
			}
			foreach($read as $clientStream){ # all changed socket
				if($clientStream === $this->socket){ # this case is new connection
					$peer = null;
					$clientStream = stream_socket_accept($this->socket,ini_get("default_socket_timeout"),$peer);
					$this->watched[] = $clientStream;
					end($this->watched);
					$this->clients[key($this->watched)] = array($peer,$clientStream);
					stream_set_blocking($clientStream,0);
					$this->_do_event('connectionAccept',array($clientStream,$this));
					continue;
				}
				$data = stream_get_contents($clientStream);
				if( empty($data) || trim($data)===chr(4) ){ # empty packet are considered as end of transmission from the client as is Ctrl+d
					$this->_do_event('connectionEOT',array($clientStream,$this));
					$this->closeConnection($clientStream);
				}else{ # process request if datas where sent
					if( trim($data) === 'dbg'){
						$this->response($clientStream,print_r($this,1));
					}
					$this->_do_event('incoming',array($data,$clientStream,$this));
				}
			}
			foreach( $except as $exceptStream){
				throw new smvcServerException("ExceptStream:\n".print_r($exceptStream,1)."\n".$this->getPeername($exceptStream));
			}
		}
		return $this;
	}

	protected function _do_event($eventName,$args=null){
		if( empty($this->callBacks[$eventName]) ){
			return;
		}
		call_user_func_array($this->callBacks[$eventName],$args);
	}

	/**
	* send response to client
	* @param resource $clientStream clientStream as returned by stream_socket_accept()
	* @param string   $response raw response to send to the client
	* @param bool     $endOfTransmission if true close client connection right after the response
	*/
	public function response($clientStream,$response,$endOfTransmission=false){
		echo "SENDING RESPONSE $response to ".$this->getPeername($clientStream)."\n";
		if( function_exists('logMsg')){ logMsg("SENDING RESPONSE $response to ".$this->getPeername($clientStream));}
		if( is_resource($clientStream)){
			fwrite($clientStream,$response);
			if( $endOfTransmission ){
				$this->closeConnection($clientStream);
			}
		}
		return $this;
	}

	/**
	* close given socket if in watched streams.
	* @param resource $clientStream clientStream as returned by stream_socket_accept()
	* @return $this for method chaining
	*/
	public function closeConnection($clientStream){
		echo "CLOSING RECEIVED $clientStream\n";
		if( false === ($k = array_search($clientStream,$this->watched,true)) ){
			echo "$clientStream not found in watch list\n";
			if( is_resource($clientStream) ){
				stream_socket_shutdown($clientStream,STREAM_SHUT_RDWR);
			}
			return $this;
		}
		$this->_do_event('connectionClose',array($clientStream,$this));
		#- fclose($clientStream);
		stream_socket_shutdown($clientStream,STREAM_SHUT_RDWR);
		unset($this->watched[$k],$this->clients[$k]);
		return $this;
	}
	/**
	* retrieve peername from a given clientStream
	* @param resource $clientStream clientStream as returned by stream_socket_accept()
	* @return string or false
	*/
	public function getPeername($clientStream){
		if( is_resource($clientStream)){
			if( $name = stream_socket_get_name($clientStream,true) ){
				return $name;
			}
		}
		echo "ALERNATIVE PEERNAME METHOD\n";
		#- perhaps a disconnected peer try a lookup in our watched clients
		foreach( $this->clients as $k=>$v ){
			if( $v[1] === $clientStream ){
				return $v[0];
			}
		}
		return false;
	}

	/**
	* retrieve a clientStream from given peername
	* @param string $peerName client peername as returned by getPeername() method
	* @return resource $clientStream if available false otherwise
	*/
	public function getClientStreamFromPeername($pearName){
		foreach( $this->clients as $k=>$v ){
			if( $v[0] === $pearName ){
				return $v[1];
			}
		}
		return false;
	}

	/**
	* send a broadcast message to all connected peer
	* @param string $msg broadcast message
	* @param Boolean $endOfTransmission if true close clients connection right after the broadcase message is sent
	* @return $this for method chaining
	*/
	public function broadcast($msg,$endOfTransmission=false){
		foreach( $this->clients as $v){
			$this->response($v[1],$msg,$endOfTransmission);
		}
		return $this;
	}
}
