<?php
$uniqueProcess = false;
require('cli-init.php');

function parseDispatcherResponse($serviceResponse){
	if( preg_match('!\s!',$serviceResponse) ){
		return preg_split('!\s+!',$serviceResponse,2);
	}else{
		return array($serviceResponse,'');
	}
}

function endingProcess(){
	global $total,$msg,$args,$myStartTime;
	logMsg("ending process: with total $total and msgTreated: $msg->treated after ".(time() - $myStartTime).' s');
	if( $total > $msg->treated ){
		execBackground(PHP_BINDIR.DIRECTORY_SEPARATOR."php messageProcess.php msgId=$args[msgId] timeout=$args[timeout] stepCount=$args[stepCount] dispatcher=$args[dispatcher]");
	}
}

function requeue($fromUid){
	global $msg,$apnsMessagesDict;
	$keys = array_keys($apnsMessagesDict);
	$pos = array_search($fromUid,$keys);
	if(false === $pos){
		return;
	}
	$requeued = (count($keys) - $pos);
	logMsg("requeue from $msg->treated treated to",$msg->treated - $requeued);

	$msg->treated -= $requeued;
	return $requeued;
}

$myStartTime = time();

function treatApnsError($serviceResponseArgs){
	global $db,$proxyDispatcher,$msg,$apnsMessagesDict;
	logMsg('treating apnsError',$serviceResponseArgs);
	if( strpos($serviceResponseArgs,':') ){
		list($apnsErrorMsgId,$apnsErrorMsgStatus,$apnsErrorMsgDesc) = explode(':',$serviceResponseArgs,3);
		if( !isset($apnsMessagesDict[$apnsErrorMsgId]) ){ // not concerned by the error
			return;
		}
		//-- this is a token problem silently remove it from database and continue
		logMsg('this error is for me',$apnsMessagesDict,$apnsErrorMsgId,$apnsErrorMsgDesc);
		#-- need to decrement the queue
		$requeued = requeue($apnsErrorMsgId);
		if( $requeued ){
			$db->query('UPDATE pushMessages SET requeuedMessages = requeuedMessages +'.$requeued.' WHERE messageId='.$msg->messageId);
		}
		if( in_array($apnsErrorMsgStatus,array('2','5','8'),true) ){
			logMsg('this error is token related',$apnsErrorMsgId,$apnsMessagesDict[$apnsErrorMsgId]);
			$db->delete('serviceRegistrations',array('WHERE application=? AND token=?',$msg->application,$apnsMessagesDict[$apnsErrorMsgId]));
			return;
		}
	}
	//-- this is probably a problem linked to the message himself so put it in error state
	trigger_error('[MESSAGE APNSERROR] received error from service '.$serviceResponseArgs,E_USER_WARNING);
	$db->update(
		'pushMessages'
		,array(
			'jobLastTime'=>date('Y-m-d H:i:s')
			,'jobStatus'=>'error'
			,'treated'=>$msg->treated
			,'sent'=>$msg->sent
		)
		,array('WHERE messageId=?',$msg->messageId)
	);
	$db->close();
	$proxyDispatcher->disconnect();
	endingProcess();
	exit(0);
}


$app = new console_app();
$app->define_arg('msgId',null,null,'message id to work on','is_numeric');
$app->define_arg('dispatcher',null,APNS_PROXYDISPATCHER_ADDRESS,'ip:port of the proxyDispatcher');
$app->define_arg('timeout',null,30,'timeout for connection and read/write operations','is_numeric');
$app->define_arg('stepCount',null,100,'max number of message to send for a single process','is_numeric');
$app->define_arg('comment','c',false,'allow a comment on the line');


$args = $app->get_args();

$maxRetry = 5;
$maxDelay = 2;

logMsg('starting new messageProcess with parameters:',$args);

#- connecting and registering to dispatcher
$proxyDispatcher = smvcServerClient::getInstance('tcp://'.$args['dispatcher'],$args['timeout']);

try{
	$proxyDispatcher
		->setBlocking(true)
		->connectWithExponentialBackoff(20,5,smvcServerClient::DELAY_BASE_MSEC)
		//->connect()
	;
	logMsg('connected to dispatcher');
	#-- GET MESSAGE AND CHECK ITS NEED OF WORK
	$db = db::getInstance(DB_CONNECTION);
	$msg = $db->select_row('pushMessages','*',array('WHERE messageId=?',$args['msgId']));
	if( empty($msg) ){
		$proxyDispatcher->tell('unknown message('.$args['msgId'].')',true);
		trigger_error("[MESSAGE ERROR] message doesn't exists for $args[msgId].",E_USER_WARNING);
		exit(1);
	}
	$msg = (object) $msg;
	if( in_array($msg->jobStatus ,array('done','error')) ){
		$proxyDispatcher->tell('message already sent('.$args['msgId'].')',true);
		trigger_error("[MESSAGE ERROR] message already sent for $args[msgId].",E_USER_WARNING);
		exit(1);
	}
	#-- REGISTER TO DISPATCHER AND ASK FOR A READY SERVICE
	$registerAttempt=0;
	do{
		smvcServerClient::exponentialBackoffDelay($registerAttempt,$maxDelay,smvcServerClient::DELAY_BASE_SEC);
		$registerResponse = $proxyDispatcher->talk('registerClient '.$msg->application);
		echo "registerResponse received $registerResponse\n";
		logMsg("registerResponse received ".var_export($registerResponse,1)."\n");
		if( false === $registerResponse ){
			$proxyDispatcher->disconnect();
			trigger_error("[MESSAGE ERROR] dispatcher connection lost for messageProcess $args[msgId].",E_USER_WARNING);
			exit(2);
		}
		if( $registerResponse === 'unregistered ERROR' ){ // service encounter an error at register time
			$db->update('pushMessages',array('jobStatus'=>'error'),array('WHERE messageId=?',$msg->messageId));
			$proxyDispatcher->disconnect();
			exit(2);
		}
		$registerResponse = trim($registerResponse);
	}while($registerResponse!=='ready' && $registerAttempt++<$maxRetry  );

	if( $registerResponse !=='ready' ){#-- REGISTRATION FAILURE LET SANITY CHECK DO THE JOB
		$db->update('pushMessages',array('jobLastTime'=>date('Y-m-d H:i:s')),array('WHERE messageId=?',$msg->messageId));
		$db->close();
		$proxyDispatcher->disconnect();
		exit(3);
	}
	#- START JOB AND
	$db->update('pushMessages',array('jobStatus'=>'working'),array('WHERE messageId=?',$msg->messageId));

	#- select clients tokens for this applications
	$db->beverbose=1;
	#- $total = $db->select_value('serviceRegistrations'
		#- ,'count(token)'
		#- ,array('WHERE application=? ORDER BY registrationId LIMIT '.$msg->treated.','.$args['stepCount'],$msg->application)
	#- );

	$tokens = $db->select_rows(
		'serviceRegistrations'
		,'token,properties'
		,array('WHERE application=? ORDER BY registrationId LIMIT '.$msg->treated.','.$args['stepCount'],$msg->application)
	);
	$db->freeResults();
	$tokens = smvcCollection::init($tokens)->_getIndexedBy('token',true,true)->_map('json_decode');
	$total = $tokens->count();
	if( $total < 1){
		logMsg('no more token awaiting this message');
		console_app::msg_error('no more token awaiting this message',true); // exit here
		exit;
	}
	logMsg('Having total :',$total,$tokens);

}catch(smvcServerClientException $e){
	console_app::msg_error($e->getMessage(),true); // exit here
}
#- then try to send each message one by one
$apnsMessage = new apnsMessage($msg->payload);
$apnsMessagesDict = array();
//print_r(jsonQueryClause::getInstance($msg->propFilter));
$jsonFilter = $msg->propFilter ? jsonQueryClause::getInstance($msg->propFilter) : null;
foreach($tokens as $token=>$properties){
	logMsg('treating',$token,$properties);
	$uid = $proxyDispatcher->talk('uid');
	$apnsMessagesDict[$uid] = $token;
	if( $jsonFilter && ! $jsonFilter->filterItem($properties) ){
		logMsg('filtered out ',$token);
		$msg->treated++;
		continue;
	}
	$sendCommand = 'service send '.$apnsMessage->getBinary($token,$uid);
	logMsg("sending $sendCommand to $token");
	$retry = 0;$cmdOk=false;
	do{
		logMsg("trying to send a message to (x $retry)",$token,$msg->messageId);
		if( $retry > 1 ){
			smvcServerClient::exponentialBackoffDelay($retry,$maxDelay,smvcServerClient::DELAY_BASE_MSEC);
		}
		if( $retry ){ //-- dont't launch the command multiple times
			list($serviceResponse,$serviceResponseArgs) = parseDispatcherResponse($proxyDispatcher->read());
		}else{
			list($serviceResponse,$serviceResponseArgs) = parseDispatcherResponse($proxyDispatcher->talk($sendCommand));
		}
		logMsg("treating service response (x $retry)",$serviceResponse,$serviceResponseArgs,$sendCommand);
		switch(trim($serviceResponse)){
			case 'sent':
				logMsg('sent to',$token);
				$msg->treated++;
				$msg->sent++;
				$cmdOk = true;
				break;
			case 'error': // stop process and put message as erroneous
				$db->update(
					'pushMessages'
					,array(
						'jobLastTime'=>date('Y-m-d H:i:s')
						,'jobStatus'=>'error'
						,'treated'=>$msg->treated
						,'sent'=>$msg->sent
					)
					,array('WHERE messageId=?',$msg->messageId)
				);
				$db->close();
				$proxyDispatcher->disconnect();
				$cmdOk=true;
				trigger_error('[MESSAGE ERROR] received error from service '.$serviceResponseArgs,E_USER_WARNING);
				exit(0);
				break 3;
			case 'apnsError':
				logMsg('apns error',$serviceResponseArgs);
				treatApnsError($serviceResponseArgs);
				$cmdOk=true;
				break 3;
			case 'unregisered':
				$cmdOk=true;
				break 3;
			default:
				// try again
				break;
		}
	}while(++$retry < $maxRetry && ! $cmdOk);
	#- if( $serviceResponse !== 'sent'){ // if last response is not sent we had a problem
	if( ! $cmdOk ){ // if last command wasn't ok we are in trouble
		break;
	}
}
sleep(1); // let apns enough time to trigger the error
# at the end check for last error
$error = $proxyDispatcher->talk('service checkApnsError');
if( preg_match('!^apnsError!',$error) ){
	list($serviceResponse,$serviceResponseArgs) = parseDispatcherResponse($error);
	treatApnsError($serviceResponseArgs);
}

$updateDatas = array(
	'jobLastTime'=>date('Y-m-d H:i:s')
	,'jobStatus'=> $msg->treated >= $total ? 'done' : 'working'
	,'sent'=>$msg->sent
	,'treated'=>$msg->treated
);
if( $msg->treated >= $total ){
	$updateDatas['sentTime'] = date('Y-m-d H:i:s');
}
$db->update(
	'pushMessages'
	,$updateDatas
	,array('WHERE messageId=?',$msg->messageId)
);
$db->close();
unset($db);
endingProcess();

#- finally update database to reflect the new sent count and launch a new process if required
//execBackground(PHP_BINDIR.DIRECTORY_SEPARATOR."php messageProcess.php msgId=$args[msgId] timeout=$args[timeout] stepCount=$args[stepCount] dispatcher=$args[dispatcher]");

//$tokens = $db->select_col('servicesRegistrations','token',array('WHERE application=? ORDER BY registrationId LIMIT '.$msg->sent.',',$msg->application));