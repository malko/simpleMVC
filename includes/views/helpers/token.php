<?php
/**
* helper to implement token for web forms to fight against XSS CRSF injections
* @sample usage:
*   get a token in your form view: $this->token('formIdentifier');
*   check your token in your controller if( $this->_token_check($timeToLive,'formIdentifier')){ continue; }
* tokens are automaticly droped when checked Ok
* @changelog
*            -2012-08-09- add  a useReferrer parameter to get method
*
*/
class token_viewHelper extends abstractViewHelper{
	static public $md5Salt = TOKEN_SALT;
	static public $maxTTL = 1800; // seconds
	static public $autoClear = true;

	function __construct(viewInterface $view){
		parent::__construct($view);
		if( null === self::$md5Salt ){
			throw new RuntimeException("You must initialize your token_viewHelper::md5Salt");
		}
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
	* @param string $uid optional lookup id for the token
	* @param boolean $useReferrer set to true if you want the token based on the referrer instead of the requested url
	*                             (usefull when token is created while executing a redirection)
	*/
	function get($uid=null,$useReferrer=false){
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
		$uri = $useReferrer ? preg_replace('!^https?://!','',$_SERVER['HTTP_REFERER']) : $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$token = ($uid?"$uid-":'').md5($uri.self::$md5Salt.$_SERVER['HTTP_USER_AGENT'].$time);
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