<?php
$uniqueProcess = true;
require('cli-init.php');

define('PROXY_DISPATCHER_ADMIN_PASSWORD','admin');

/* methods tagged @remote are intended to be called by dispatcher clients not in code */
class proxyDispatcher{
	/** get php binary path for process launching */
	protected $PhpPath  = null;
	/** list of connected clients */
	protected $clients  = array();
	/** maximum application services running at once (any value less than 1 will allow unlimited services) */
	protected $maxActiveService = 0;
	/** list of service providers peername indexed by application Id */
	protected $appServices = array();
	/** list of lists service consumers peername indexed by application Id and consumers peername*/
	protected $appConsumers = array();
	/** list of services application Id for each consumers indexed by consumer peername */
	protected $consumerService = array();

	/** pointer to the smvcServer that manage transactions */
	protected $server = null;

	protected $uid = 0;
	protected $waitingFeedBack = array();

	/**
	* @private
	* @internal
	*/
	protected function __construct(){
		$this->PhpPath  = PHP_BINDIR.DIRECTORY_SEPARATOR.'php';
	}

	/**
	* create a new instance of the proxyDispatcher and start the service
	* @param string $bindedAdress ip:port
	* @return void
	*/
	static public function init($bindedAdress,$maxActiveService=5){
		$i = new self();
		logMsg('initializing server');
		$i->server = smvcServer::init(array(
				'incoming' => array($i,'onIncoming')
				,'start'   => array($i,'sanityCheck')
				,'connectionAccept' => array($i,'onConnectionAccept')
				,'connectionEOT' => array($i,'endOfTransmission')
				,'connectionClose' => array($i,'onConnectionClose')
				,'idle' => array($i,'sanityCheck')
			)
			,'tcp://'.$bindedAdress
			,10 // trigger idle event each n seconds of inactivity
		);
		logMsg('starting server');
		$i->server->start();
	}

	/**
	* on accepted connection keep a reference between peername and associated stream
	*/
	function onConnectionAccept($clientStream,smvcServer $server){
		logMsg('connection accepted',$clientStream);
		$peername = $server->getPeername($clientStream);
		$this->clients[$peername] = $clientStream;
	}
	/**
	* remove peername and stream reference from clients list
	*/
	function onConnectionClose($clientStream,$server){
		if( false !== ($peername=array_search($clientStream,$this->clients)) ){
			unset($this->clients[$peername]);
		}
		echo "CLOSING CONNECTION FOR $clientStream $peername\n";
		#- if( empty($this->clients) ){ // perform sanityCheck (removed now will be called at idle time)
			#- $this->sanityCheck();
		#- }
		logMsg('connection closed',"$clientStream");
	}
	/**
	* on client connection lost check if it's a service provider or consumer and remove reference to it appropriatly.
	*/
	function endOfTransmission($clientStream,$server){
		$peername = array_search($clientStream,$this->clients);
		echo "EOT FOR $clientStream $peername\n";
		logMsg('connection EOT',"$clientStream");
		if( $peername === false ){
			return;
		}
		if( isset($this->consumerService[$peername])){
			$this->unregisterClient($peername);
		}else if( false !== ($appId = array_search($peername,$this->appServices,true)) ){
			$this->unregisterService($appId);
		}
		unset($this->clients[$peername]);
	}
	/**
	* manage incoming transmission pre-parse the command and redirect to the requested cmd_ method
	*/
	function onIncoming($rawData,$clientStream,$server){
		echo "INCOMING $rawData from ".$server->getPeername($clientStream)."\n";
		logMsg("incomming $rawData FROM $clientStream ". $server->getPeername($clientStream));
		$rawData = trim($rawData);
		if( !strpos($rawData,' ')){
			$cmd = $rawData;
			$args=null;
		}else{
			list($cmd,$args) = explode(' ',$rawData,2);
			$args = trim($args);
		}

		if(! method_exists($this,"cmd_$cmd") ){
			$server->response($clientStream,'unknown command');
		}else{
			$res = $this->{"cmd_$cmd"}($clientStream,$server,trim($args) );
			if( $res ){
				$server->response($clientStream,$res);
			}
		}
	}

	#### SERVICE PROVIDERS RELATED METHODS ####
	/**
	* check there's no more services watched than maxActiveService, eventually if limit is reached it will try to free unused slots
	* @return bool
	*/
	function checkServiceSlotAvailability(){
		logMsg('checking service availability');
		if( $this->maxActiveService < 1){
			return true;
		}
		if( count($this->appServices) < $this->maxActiveService ){
			return true;
		}
		#- check if a service is not in used in which case we try to unregister him
		$unusedSlot = 0;
		foreach($this->appServices as $appId=>$serviceStream){
			if( empty($this->appConsumers[$appId]) ){
				$this->unregisterService($appId,'freeing unused service slot');
				$unusedSlot++;
			}
		}
		return $unusedSlot ? true : false;
	}
	/**
	* remote client asking to register as service provider for given appId
	* @remote
	*/
	function cmd_registerService($clientStream,$server,$appId){
		if(! is_numeric($appId) ){
			return 'bad command argument';
		}
		if( !empty( $this->appServices[$appId] )){
			return "service($appId) already registered";
		}
		$this->appServices[$appId] = $server->getPeername($clientStream);
		//$this->serviceBroadcast($appId,'service ready',$server);
		return 'registered';
	}
	/**
	* remote client registered as service provider asking to unregister itself
	* @remote
	*/
	function cmd_unregisterService($clientStream,$server){
		$peername = $server->getPeername($clientStream);
		$appId = array_search($peername,$this->appServices);
		if( false === $appId){
			$server->response($clientStream,'not a registered service',true);
			return;
		}
		$this->unregisterService($appId);
	}
	/**
	* remove service from appServices lists and disconnect it and all its registered clients
	* @param int $appId service appId
	* @param string $additionalInfos optional contextual info to send to client with unregister command
	* @return bool
	*/
	function unregisterService($appId,$additionalInfos=null){
		if(! isset($this->appServices[$appId]) ){
			return false;
		}
		//-- tell all client service that service is now down it is their job to eventually reconnect
		if( ! empty($this->appConsumers[$appId]) ){
			foreach($this->appConsumers[$appId] as $clientName){
				$this->unregisterClient($clientName);
			}
		}
		unset($this->appConsumers[$appId]);
		$serviceName = $this->appServices[$appId];
		$serviceStream = $this->clients[$serviceName];
		$this->server->response($serviceStream,'unregistered'.($additionalInfos?" $additionalInfos":''),true);
		unset($this->appServices[$appId]);
		if(! in_array($appId,$this->waitingFeedBack,true) ){
			$this->waitingFeedBack[] = $appId;
		}
	}
	function cmd_registerServiceError($clientStream,$server,$appId){
		if( ! empty($this->appConsumers[$appId]) ){
			foreach($this->appConsumers[$appId] as $clientName){
				$this->unregisterClient($clientName,'ERROR');
			}
		}
		unset($this->appConsumers[$appId]);
		$server->closeConnection($clientStream);
	}
	#### SERVICE CONSUMERS RELATED METHODS ####
	/**
	* remote client asking for registering itself as service consumer for given appId
	* @remote
	*/
	function cmd_registerClient($clientStream,$server,$appId){
		if(! is_numeric($appId) ){
			return 'bad command argument';
		}
		$peername = $server->getPeername($clientStream);
		$this->appConsumers[$appId][$peername] = $peername;
		$this->consumerService[$peername]=$appId;
		if( isset($this->appServices[$appId]) ){
			$this->cmd_service($clientStream,$server,'isready');
			return;
			#- return 'service ready';
		}else{
			#- check for slots available
			if(! $this->checkServiceSlotAvailability() ){
				logMsg('retry service not available');
				return 'retry service not available';
			}
			execBackground("$this->PhpPath ./proxyService.php --appId $appId");
			logMsg('retry service launching');
			return 'retry service launching';
		}
	}
	/**
	* remote client previously registered as service consumer asking for unregistration
	* @remote
	*/
	function cmd_unregisterClient($clientStream,$server){
		$peername = $server->getPeername($clientStream);
		if(! isset($this->consumerService[$peername]) ){
			return 'not a registered client';
		}
		$this->unregisterClient($peername);
	}
	/**
	* remove client from consumerService and appConsumers lists and disconnect it
	* @param string $clientName client peer name (ip:port)
	* @param string $additionalInfos optional contextual info to send to client with unregister command
	* @return bool
	*/
	function unregisterClient($clientName,$additionalInfos=null){
		#- does clientName is watched
		if(! isset($this->consumerService[$clientName]) ){
			return false;
		}
		$appId = $this->consumerService[$clientName];
		$clientStream = $this->clients[$clientName];
		// remove client from watched lists
		unset($this->consumerService[$clientName],$this->appConsumers[$appId][$clientName]);
		$this->server->response($clientStream,'unregistered'.($additionalInfos?" $additionalInfos":''),true);
		return true;
	}

	#### COMMUNICATION BETWEEN SERVICE PROVIDERS AND CONSUMERS ####
	/**
	* command from a consumer to it's provider
	* @remote
	*/
	function cmd_service($clientStream,$server,$cmd){
		$peername = $server->getPeername($clientStream);
		if(! isset($this->consumerService[$peername])){
			return 'unregistered client';
		}
		$appId = $this->consumerService[$peername];
		if( ! isset($this->appServices[$appId]) ){
			return 'unregistered service';
		}
		$server->response($this->clients[$this->appServices[$appId]],"[$peername] $cmd");
	}

	/**
	* command from provider to a given consumer
	* @remote
	*/
	function cmd_serviceResponse($clientStream,$server,$args){
		list($client,$response) = explode(' ',$args,2);
		if( isset($this->clients[$client])){
			$server->response($this->clients[$client],"$response\n");
		}
	}
	/**
	* message from a provider to all of its consumers
	* @remote
	*/
	function cmd_serviceBroadcast($clientStream,$server,$msg){
		$serviceName = $server->getPeername($clientStream);
		$appId = array_search($serviceName,$this->appServices,true);
		if( false === $appId ){
			return 'unregistered service request rejected';
		}
		$this->serviceBroadcast($appId,$msg);
	}

	/**
	* send a message for all service consumers registered for given application id
	*/
	function serviceBroadcast($appId,$msg){
		if( !empty( $this->appConsumers[$appId]) ){
			foreach($this->appConsumers[$appId] as $peername){
				$this->server->response($this->clients[$peername],$msg);
			}
		}
	}

	#### BASIC REMOTE METHODS ####
	function cmd_list($clientStream,$server){
		return print_r(array(
			'appServices'=>$this->appServices
			,'appConsumers'=>$this->appConsumers
			,'consumerService'=>$this->consumerService
			,'clients'=>$this->clients
		),1);
	}
	function cmd_disconnect($clientStream,$server){
		$server->closeConnection($clientStream);
	}
	function cmd_stop($clientStream,$server){
		$server->stop();
	}
	/**
	* launch a messageProcess
	*/
	function cmd_apiMsg($clientStream,$server,$msgId){
		execBackground($this->PhpPath." messageProcess.php msgId=$msgId -c launch");
		return 'ok';
	}

	function cmd_uid(){
		if( $this->uid > 999999999 ){
			return $this->uid = 0;
		}
		return ++$this->uid;
	}

	function sanityCheck(){
		echo "SANITY CHECK \n";
		logMsg("SANITY CHECK");
		# check eventual pending messages , or working ones which  lastJobTime is too old
		$db = db::getInstance(DB_CONNECTION);
		$staledMessages = $db->select_rows('pushMessages','messageId',array('WHERE jobStatus="working" AND jobLastTime <= ?',date('Y-m-d H:i:s',time()-90)));
		if( !empty($staledMessages)){
			logMsg("launching ".count($staledMessages).' staled messages process');
			foreach($staledMessages as $m){
				execBackground($this->PhpPath." messageProcess.php msgId=$m[messageId] -c staled");
			}
		}
		$pendingMessages = $db->select_rows('pushMessages','messageId',array('WHERE jobStatus="pending" AND receivedTime <= ?',date('Y-m-d H:i:s',time()-30)));
		if( !empty($pendingMessages)){
			logMsg("launching ".count($pendingMessages).' pending messages process');
			foreach($pendingMessages as $m){
				execBackground($this->PhpPath." messageProcess.php msgId=$m[messageId] -c pendings");
			}
		}
		#launching awaiting feedback if necessary
		if( $wainting =  count($this->waitingFeedBack)){
			logMsg("launching feedback with ".count($this->waitingFeedBack).' feedback awaiting');
			for( $i=0,$l=min($wainting,3);$i<$l ;$i++){
				$appId = array_shift($this->waitingFeedBack);
				execBackground($this->PhpPath." feedbackProcess.php appId=$appId");
			}
		}
	}

	function cmd_name($clientStream,$server){
		$server->response($clientStream,stream_socket_get_name($clientStream,true).' / '.stream_socket_get_name($clientStream,false));
	}
}

proxyDispatcher::init(APNS_PROXYDISPATCHER_ADDRESS,5);
