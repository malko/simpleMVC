<?php
$uniqueProcess = false;
require('cli-init.php');

$app = new console_app();
$app->define_arg('appId',null,null,'tell the proxy the which app will be used to connect apns service','is_numeric');
$app->define_arg('dispatcher',null,APNS_PROXYDISPATCHER_ADDRESS,'ip:port of the proxyDispatcher who launch the process');
$app->define_arg('timeout',null,15,'timeout for connection and read/write operations','is_numeric');
$app->define_arg('ttl',null,30,'time to stay alive in seconds if we have no jobs in queue','is_numeric');

$args = $app->get_args();

logMsg('starting new proxyService with parameters',$args);
#- connecting and registering to dispatcher
$proxyDispatcher = smvcServerClient::getInstance('tcp://'.$args['dispatcher'],$args['timeout']);

try{
	$proxyDispatcher
		->setBlocking(true)
		->connectWithExponentialBackoff(20,5,smvcServerClient::DELAY_BASE_MSEC)
		//->connect()
	;

	#-- CHECK APPLICATION IS ACTIVE
	$db = db::getInstance(DB_CONNECTION);
	$app = $db->select_row('applications','*',array('WHERE applicationId=?',$args['appId']));
	$db->close();
	unset($db);
	if( empty($app) ){
		$proxyDispatcher->tell('registerServiceError '.$args['appId'],true);
		trigger_error("[SERVICE ERROR] No application exists for $args[appId].",E_USER_WARNING);
		exit(1);
	}
	$app = (object) $app;
	$UPTIME = date('Y-m-d H:i:s');
	if(  ($app->startDate > $UPTIME ) || ($app->endDate < $UPTIME && $app->endDate !== '0000-00-00 00:00:00') || ! $app->active ){
		$proxyDispatcher->tell('registerServiceError '.$args['appId'],true);
		trigger_error("[SERVICE ERROR] application is not active $args[appId].",E_USER_WARNING);
		exit(1);
	}

	#-- REGISTER TO DISPATCHER
	$registerResponse = $proxyDispatcher->talk('registerService '.$args['appId']);
	logMsg('register response',$registerResponse);
	if( $registerResponse !== 'registered' ){
		$proxyDispatcher->disconnect();
		trigger_error("[SERVICE INFO] application is not active $args[appId].",E_USER_NOTICE);
		exit(2);
	}

	#-- LAUNCH APNS SERVICE CONNECTION
	$pemFile=USER_DATAS_DIR."/certs/$args[appId].pem";
	if(! is_file($pemFile)){
		$proxyDispatcher->tell('registerServiceError '.$args['appId'],true);
		trigger_error("[SERVICE ERROR] application certificate not found for $args[appId].",E_USER_WARNING);
		exit(3);
	}
	try{
		$apnsService = apnsService::getProductionService($pemFile,'')->openBackOff();
	}catch(apnsServiceException $e){
		$proxyDispatcher->tell('registerServiceError '.$args['appId'],true);
		trigger_error("[SERVICE ERROR] application $args[appId] error while connecting to apns: ".$e->getMessage(),E_USER_WARNING);
		exit(4);
	}


	#-- TELL CLIENTS WE ARE READY
	#- $proxyDispatcher->tell('serviceBroadcast ready');

}catch(smvcServerClientException $e){
	trigger_error("[SERVICE CONNECTION ERROR] ".$e->getMessage(),E_USER_WARNING);
	exit(3);
}catch(Exception $e){
	trigger_error("[SERVICE STARTING ERROR] ".$e->getMessage(),E_USER_ERROR);
	exit(3);
}

$lastWorkTime = $startTime = time();
$endTime = $lastWorkTime + $args['ttl'];
//-- now starting doing the job
do{
	if( time() > $endTime ){ # if idle for ttl then stop
		break;
	}
	$cmd = $proxyDispatcher->read(); // wait for a command from dispatcher
	logMsg("Received raw $cmd from dispatcher\n");
	if( $cmd === false){
		trigger_error('[EOT] reading error',E_USER_WARNING);
		break;
	}else if( $cmd === '' && $proxyDispatcher->eot() ){
		trigger_error('[EOT] connection lost',E_USER_NOTICE);
		break ;
	}

	$remoteClient = '';
	$updateEndtime = false;
	list($remoteClient,$cmd,$cmdargs) = match('!^(?:\[([^\]]+)\]\s+)?(\w+)\s*([\s\S]*)?$!',$cmd,array(1,2,3));
	$commandPrefix = ($remoteClient?'serviceResponse '.$remoteClient.' ':'');
	logMsg("received from $remoteClient command=>'$cmd' with args=>'$cmdargs'");
	switch($cmd){
		case 'idletime':
			$proxyDispatcher->tell($commandPrefix.date('Y-m-d H:i:s',$lastWorkTime));
			break;
		case 'uptime':
			$proxyDispatcher->tell($commandPrefix.$UPTIME);
			break;
		case 'unregister':
			$proxyDispatcher->disconnect();
			exit(0);
			break 2;
		case 'send':
			$updateEndtime = true;
			try{
				$apnsService->send($cmdargs);
			}catch(apnsServiceException $e){
				$proxyDispatcher->tell($commandPrefix.'error');
				trigger_error($e->getMessage(),E_USER_NOTICE);
				$apnsService->close();
				usleep(500);
				$proxyDispatcher->tell('unregisterService',true);
				logMsg('exiting on apns write error');
				exit(5);
			}
			checkApnsError($apnsService,$proxyDispatcher);
			$proxyDispatcher->tell($commandPrefix.'sent');
			break;
		case 'isready':
			$updateEndtime = true;
			$proxyDispatcher->tell($commandPrefix.'ready');
			break;
		case 'checkApnsError':
			checkApnsError($apnsService,$proxyDispatcher,.5 * smvcServerClient::DELAY_BASE_SEC );
			$updateEndtime = true;
			break 2;

	}

	if( $updateEndtime ){
		$lastWorkTime = time();
		$endTime = $args['ttl'] + $lastWorkTime;
	}

}while(true);


function checkApnsError(apnsService $apnsService,smvcServerClient $dispatcher,$timeOut=0){
	try{
		$error = $apnsService->getError($timeOut);
		if( $error ){
			$dispatcher->tell(
				"serviceBroadcast apnsError $error[identifier]:$error[status]:"
				.(isset(apnsService::$apnsErrorCodes[$error['status']])?apnsService::$apnsErrorCodes[$error['status']] : 'UNKNOWN' )
			);
			$apnsService->close();
			usleep(500);
			$dispatcher->tell('unregisterService',true);
			logMsg("exiting on apns error($error[status]) received");
			exit(0);
		}
	}catch(apnsServiceException $e){
		$dispatcher->tell('serviceBroadcast apnsError '.$e->getMessage());
		$apnsService->close();
		usleep(500);
		$dispatcher->tell('unregisterService',true);
		logMsg('exiting on apns error received');
		trigger_error($e->getMessage(),E_USER_NOTICE);
		exit(5);
	}
}

//try to tell to the dispatcher that we've ended our job and realising the pool
$proxyDispatcher->tell('unregisterService',true);
$apnsService->close();