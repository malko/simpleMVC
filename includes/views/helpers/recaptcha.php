<?php

if(! defined('RECAPTCHA_API_SERVER') ){
	require LIB_DIR.'/recaptchalib.php';
}
class recaptcha_viewHelper extends abstractViewHelper{
	static $publicKey = "6LeaFNISAAAAACKs1x01V1hxt8NNJnVyN-tyzU2G";
	static $privateKey = "6LeaFNISAAAAAJPHPnpG3O_aN2FhkH_2JPcGtBLT";
	static $useSSL = false;


	function recaptcha($error=null,$forcedUseSSL = null){
		return recaptcha_get_html(
			self::$publicKey
			,$error
			,$forcedUseSSL!==null ? $forcedUseSSL : self::$useSSL
		);
	}

	/**
	* return true if good or a string containing the error code you can eventually use when calling recaptcha method to display an error message to the user
	*/
	function check(){
		if( isset($_POST['recaptcha_challenge_field'],$_POST["recaptcha_response_field"]) ){
			$challenge = $_POST['recaptcha_challenge_field'];
			$response  = $_POST["recaptcha_response_field"];
		}else if( isset($_GET['recaptcha_challenge_field'],$_GET["recaptcha_response_field"]) ){
			$challenge = $_GET['recaptcha_challenge_field'];
			$response  = $_GET["recaptcha_response_field"];
		}
		$resp = recaptcha_check_answer(
			self::$privateKey
			,$_SERVER["REMOTE_ADDR"]
			,$challenge
			,$response
		);
		return $resp->is_valid ? true : $resp->error;
	}
}