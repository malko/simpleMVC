<?php
$uniqueProcess = false;
require('cli-init.php');

$app = new console_app();
$app->define_arg('appId',null,null,'application id to work on','is_numeric');
$app->define_arg('timeout',null,30,'timeout for connection and read/write operations','is_numeric');
//$app->define_arg('stepCount',null,100,'max number of message to send for a single process','is_numeric');

$args = $app->get_args();

try{

	#-- CHECK APPLICATION IS ACTIVE
	$db = db::getInstance(DB_CONNECTION);
	$db->beverbose=1;
	$app = $db->select_row('applications','*',array('WHERE applicationId=?',$args['appId']));
	if( empty($app) ){
		#- $proxyDispatcher->tell('registerServiceError '.$args['appId'],true);
		trigger_error("[SERVICE ERROR] No application exists for $args[appId].",E_USER_WARNING);
		exit(1);
	}
	$app = (object) $app;
	$UPTIME = date('Y-m-d H:i:s');
	if(  ($app->startDate > $UPTIME ) || ($app->endDate < $UPTIME && $app->endDate !== '0000-00-00 00:00:00') || ! $app->active ){
#- 		$proxyDispatcher->tell('registerServiceError '.$args['appId'],true);
		trigger_error("[SERVICE ERROR] application is not active $args[appId].",E_USER_WARNING);
		exit(1);
	}

	#-- LAUNCH APNS SERVICE CONNECTION
	$pemFile=USER_DATAS_DIR."/certs/$args[appId].pem";
	if(! is_file($pemFile)){
#- 		$proxyDispatcher->tell('registerServiceError '.$args['appId'],true);
		trigger_error("[SERVICE ERROR] application certificate not found for $args[appId].",E_USER_WARNING);
		exit(3);
	}
	try{
		$apnsService = apnsService::getProductionFeedbackService($pemFile,'')->openBackOff();
	}catch(apnsServiceException $e){
		#- $proxyDispatcher->tell('registerServiceError '.$args['appId'],true);
		trigger_error("[SERVICE ERROR] application $args[appId] error while connecting to apns: ".$e->getMessage(),E_USER_WARNING);
		exit(4);
	}

}catch(smvcServerClientException $e){
	trigger_error("[SERVICE CONNECTION ERROR] ".$e->getMessage(),E_USER_WARNING);
	exit(3);
}catch(Exception $e){
	trigger_error("[SERVICE STARTING ERROR] ".$e->getMessage(),E_USER_ERROR);
	exit(3);
}


$feedbacks = $apnsService->getFeedback();
$apnsService->close();

if( empty($feedbacks )){
	$db->close();
	exit();
}

$feedbacks = smvcCollection::init($feedbacks)
	->_removeCol('tokenLength') // remove unused column
	->_map(create_function('$v','return date("Y-m-d H:i:s",$v);'),'timestamp') // make date usable
	->_getIndexedBy('deviceToken',false,true) // make it a key value pair of deviceToken => unregistrationDate
;
// get corresponding registrations in database
$serviceRegistrations = $db->select_rows(
	'serviceRegistrations'
	,'registrationId,token,registrationDate'
	,array('WHERE application= ? AND platform="ios" AND token IN (?)',$args['appId'], $feedbacks->keys())
);
if( empty( $serviceRegistrations )){
	$db->close();
	exit();
}
foreach( $serviceRegistrations as $rowId=>$row){
	# token was registered after feedback time probably the client reinstall the app in between we don't remove him
	if( $row['registrationDate'] > $feedbacks[$row['token']] ){
		unset($serviceRegistrations[$rowId]);
	}
	echo json_encode($row)."\n";
}

if( !empty($serviceRegistrations)){
	$db->delete('serviceRegistrations',array('WHERE registrationId in (?)',smvcCollection::init($serviceRegistrations)->registrationId));
}

$db->close();
exit();