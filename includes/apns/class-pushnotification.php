<?php
/** helper class to connect to our push notification server **/


define('PUSH_APP_ID','23');
define('PUSH_REVERSE_FQDN','fr.modedemploi.playground');
define('PUSH_API_KEY','d013ee2bc487d8001f9244eff56baa6b5fe2718ee394963f2a8f262d645e57f9');
define('PUSH_API_ENDPOINT','http://mdedev2/~malko/hec/2012-08-push/conception/api');

class pushNotificationException extends Exception{}

class pushNotification{

	static public $appId = PUSH_APP_ID;
	static public $reverseFQDN = PUSH_REVERSE_FQDN;
	static public $apiKey = PUSH_API_KEY;
	static public $apiEndpoint = PUSH_API_ENDPOINT;

	private $sound = null;
	private $badge = null;
	private $alert = null;
	private $filter= null;

	protected function __construct(){}


	static public function getNew($alert=null,$sound=null,$badge=null,$filter=null){
		$n = new pushNotification();
		if( $alert ){
			$n->setAlert($alert);
		}
		if( $sound ){
			$n->setSound($sound);
		}
		if( $badge ){
			$n->setBadge($badge);
		}
		if( $filter ){
			$n->setFilter($filter);
		}
		return $n;
	}

	public function __set($k,$v){
		if( method_exists($this,"set$k") ){
			return $this->{"set$k"}($v);
		}
		throw new pushNotificationException('Bad method call');
	}

	function setAlert($alert){
		if( is_string($alert)){
			$this->alert = $alert;
		}else if( is_array($alert) ){
			$this->alert = $alert;
		}else if( is_null($alert)){
			$this->alert = null;
		}else{
			throw new pushNotificationException(__class__.'::setAlert() called with invalid alert value.');
		}
		return $this;
	}

	function setBadge($int){
		if(! (is_numeric($int) || is_null($int)) ){
			throw new pushNotificationException(__class__.'::setBadge() called with invalid value.');
		}
		$this->badge = (int) $int;
		return $this;
	}

	function setSound($sound){
		if(! is_string($sound)){
			throw new pushNotificationException(__class__.'::setSound() called with invalid sound value.');
		}
		$this->sound = $sound;
		return $this;
	}

	function setFilter($filter){
		if(! (is_string($filter) && json_decode($filter) !== null) ){
			throw new pushNotificationException(__class__.'::setFilter() called with malformed filter value.');
		}
		$this->filter = $filter;
	}

	function send($timeOut=15){
		$payload = array();
		foreach(array('sound','badge','alert') as $key){
			if( null !== $this->{$key} ){
				$payload[$key] = $this->{$key};
			}
		}
		if( empty($payload)){
			throw new pushNotificationException(__class__.'::send() Trying to send an empty request');
		}
		$payload = json_encode(array('aps'=>$payload));
		if( mb_strlen($payload,'latin1') > 256){
			throw new pushNotificationException(__class__.'::send() payload request exceed the 256bytes size limitation.');
		}

		$uri = self::$apiEndpoint.(substr(self::$apiEndpoint,-1)!=='/'?'/':'').'send';
		$signature = hash_hmac('md5',self::$reverseFQDN.$payload,self::$apiKey);
		$request = json_encode(array(
			'method'=>'send'
			,'params'=>$this->filter ? array(self::$appId,$signature,$payload,$this->filter) : array(self::$appId,$signature,$payload)
			,'id'=>uniqid()
		));
		#- check payload before sending
		if( function_exists('curl_setopt_array') ){
			$curl = curl_init();
			curl_setopt_array($curl,array(
				CURLOPT_RETURNTRANSFER=>true         # get result as string instead of stdout printing
				,CURLOPT_HEADER => true 		         # no header in the results
				,CURLOPT_URL => $uri                 # calling url
				,CURLOPT_POST => true                # this is a post request
				,CURLOPT_POSTFIELDS => $request      # here's the jsonrpc request
				,CURLOPT_FOLLOWLOCATION => true 		 # allow redirect
				,CURLOPT_MAXREDIRS => 5 	         	 # max redirect
				,CURLOPT_CONNECTTIMEOUT => $timeOut  # max connection time
				,CURLOPT_TIMEOUT => $timeOut         # max get time
				,CURLOPT_FAILONERROR => true
				,CURLOPT_SSL_VERIFYPEER => false
			));
			$response = curl_exec($curl);
			if( false===$response ){
				throw new pushNotificationException(__class__.':'.__function__.
				" Error while requesting ".self::$apiEndpoint." : ".curl_error($curl));
			}
			curl_close($curl);
		}else{
			preg_match('!^http(s?)://([^/]+)(.*)$!',$uri,$m);
			$fp = fsockopen(($m[1]?'ssl://':'').$m[2],$m[1]?443:80,$errno,$errstr,$timeOut);
			if(! $fp ){
				throw new pushNotificationException(__class__."::send() $errno - $errstr");
			}
			$requestContent = array(
				"POST $m[3] HTTP/1.1",
				"Host: $m[2]",
				"User-Agent: pushNotificationPhpClient",
				"Content-Type: application/x-www-form-urlencoded",
				"Content-Length:".strlen($request),
				"\r\n$request",
			);
			fwrite($fp,implode("\n",$requestContent));
			$response='';
			while(!feof($fp))$response.= fread($fp,1024);
			fclose($fp);
			$reponse = preg_replace('!^.*(?:\r?\n){2}(.*)$!s','\\1',$response);
		}
		$decoded = json_decode($response);
		if( null === $decoded ){
			throw new pushNotificationException(__class__.'::send() bad server response >>>'.$response);
		}
		return $decoded;
	}
}
