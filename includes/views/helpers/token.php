<?php
/**
* helper to implement token for web forms to fight against XSS CRSF injections
* @sample usage:
*   get a token in your form view: $this->token('formIdentifier');
*   check your token in your controller if( $this->_token_check($timeToLive,'formIdentifier')){ continue; }
* tokens are automaticly droped when checked Ok
*
*/
class token_viewHelper extends abstractViewHelper{
	static public $md5Salt = '&)çà,xw:!;<';
	static public $maxTTL = 1800; // seconds
	static public $autoClear = true;

	function __construct(viewInterface $view){
		parent::__construct($view);
		if( self::$autoClear ){
			$this->clear(self::$maxTTL);
		}
	}
	/**
	* return an input with the current token
	*/
	function token($uid=null){
		#- show($_SERVER);
		list($token,$time) = $this->get($uid);
		return '<input type="hidden" name="'.$token.'" value="'.$time.'" />';
	}
	/*
	* return a token and it's time and store it in session so a token max time to leave is the session validity
	*/
	function get($uid=null){
		$time = time();
		// remove existing token for this uid if given
		if($uid && ! empty($_SESSION['tokens'])){
			foreach($_SESSION['tokens'] as $k=>$v){
				if( strpos($k,"$uid-")===0 ){
					unset($_SESSION['tokens'][$k]);
					break;
				}
			}
		}
		$token = ($uid?"$uid-":'').md5($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].self::$md5Salt.$_SERVER['HTTP_USER_AGENT'].$time);
		#- add token to list
		$_SESSION['tokens'][$token] = $time;
		return array($token,$time);
	}
	/**
	* check for a valid token
	*/
	function check($ttl=null,$uid=null,$tokenPlace='POST'){
		if( empty($_SESSION['tokens']) ){
			return false;
		}
		switch($tokenPlace){
			case 'GET':
			case '_GET':
				$origin = &$_GET;break;
			case 'COOKIE':
			case '_COOKIE':
				$origin = &$_COOKIE;break;
			case 'POST':
			case '_POST':
			default:
				$origin = &$_POST;break;
		}

		#- show($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].self::$md5Salt.$_SERVER['HTTP_USER_AGENT'].$time);
		foreach($_SESSION['tokens'] as $k=>$v){ //-- look for every token stored in session
			if( $uid && ! strpos($k,"$uid-")===0 )
				continue;
			if( isset($origin[$k]) ){
				#- found a token check it's validity for referer
				$tokenCheck = ($uid?"$uid-":'').md5(preg_replace('!^https?://!','',$_SERVER['HTTP_REFERER']).self::$md5Salt.$_SERVER['HTTP_USER_AGENT'].$v);
				if( $tokenCheck === $k ){
					#- now check it's time validity if required
					if( $ttl === null) {
						$ttl = self::$maxTTL;
					}
					if( $ttl > (time() - $v) ){
						unset($_SESSION['tokens'][$k]);
						return true;
					}
				}
			}
		}
		return false;
	}
	function clear($olderThan=null){
		if(empty($_SESSION['tokens'])){
			return $this;
		}
		if( null===$olderThan){ //-- clear all tokens
			$_SESSION['tokens'] = array();
		}else{ //-- clear only expired tokens
			$time = time();
			foreach($_SESSION['tokens'] as $k=>$v){
				if( $olderThan < ($time-$v) ){
					unset($_SESSION['tokens'][$k]);
				}
			}
		}
		return $this;

	}
}