<?php
/**
* @since 2010-09-27
* @changelog
* - 2010-11-25 - add E_RECOVERABLE_ERROR
*/
//-- error handling
class smvcErrorHandler{
	public $errors = array();
	public $ignoreNativeHandler = false;
	static $instance = null;
	static $formatStrings = array(
		'cli'=> "%2\$s: %3\$s %4\$s %5\$s\n%6\$s'",
		'default'=>'
			<div class="php_error tk-border tk-content tk-state-%7$s">
				<div onclick="var e = document.getElementById(\'smvcErrorHandler%1$s\');e.style.display=e.style.display==\'block\'?\'none\':\'block\';return false;" title="show context" style="cursor:pointer"><strong>%2$s</strong>: %3$s in <strong>%4$s</strong> on line <strong>%5$s</strong></div>
				<pre id="smvcErrorHandler%1$s" style="display:none;"><xmp>Context: %6$s</xmp></pre>
			</div>',
		'mail'=>'
			<div class="php_error">
				<strong>%2$s</strong>: %3$s in <strong>%4$s</strong> on line <strong>%5$s</strong>
				<pre><xmp>Context:%6$s</xmp></pre>
			</div>'
	);
	static function init($ignoreNativeHandler=false){
		if(! self::$instance){
			self::$instance = new smvcErrorHandler();
			//-- restore stored errors
			if( !empty($_SESSION['smvcErrorHandlerStorage'])){
				self::$instance->errors = $_SESSION['smvcErrorHandlerStorage'];
				unset($_SESSION['smvcErrorHandlerStorage']);
			}
			//-- set handlers
			set_error_handler(array(self::$instance,'handler'));
			set_exception_handler(array(self::$instance,'exceptionHandler'));
			register_shutdown_function(array(self::$instance,'shutdown'));
		}
		self::$instance->ignoreNativeHandler = $ignoreNativeHandler;
	}

	static function getErrors($formatType=null){
		if( ! self::$instance){
			return '';
		}
		$errors = self::$instance->errors;
		if( null !== $formatType && isset(self::$formatStrings[$formatType])){
			foreach($errors as $k=>&$e){
				$e = sprintf(self::$formatStrings[$formatType],$k,$e['type'],$e['message'],$e['file'],$e['line'],print_r($e['context'],1),$e['state']);
			}
			return implode($formatType==='cli'?"\n":"\n<br />",$errors);
		}
		return $errors;
	}
	static function clear(){
		if( self::$instance){
			self::$instance->errors = array();
		}
	}

	static function store(){
		if( self::$instance){
			$errors = self::getErrors();
			foreach($errors as &$e){
				$e['message'] = '[stored from previous http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'] => '.$e['message'];
				unset($e['context']);
			}
			$_SESSION['smvcErrorHandlerStorage'] = isset($_SESSION['smvcErrorHandlerStorage'])?$_SESSION['smvcErrorHandlerStorage']+$errors:$errors;
		}
	}

	function handler($no,$str,$file,$line,$context=null){
		if (!(error_reporting() & $no)) { //-- only trigger codes included in error_reporting
			return;
		}
		switch ($no) {
			case E_USER_ERROR:
			case E_ERROR:
			case E_COMPILE_ERROR:
				$no = 'Fatal error';
				$state = 'error';
				break;
			case E_RECOVERABLE_ERROR:
				$no ="Fatal Recoverable error";
				$state = 'error';
				break;
			case E_USER_WARNING:
			case E_WARNING:
				$no='Warning';
				$state = 'warning';
				break;
			case E_USER_NOTICE:
			case E_NOTICE:
				$no='Notice';
				$state = 'info';
				break;
			case E_PARSE:
				$no='Parse error';
				$state = 'error';
				break;
			case E_STRICT:
				$no='Strict standards';
				$state = 'warning';
				break;
			default:
				$no = "Unknown($no)";
				$state = 'error';
				break;
		}
		$this->errors[] = array('type'=>$no,'message'=>$str,'file'=>$file,'line'=>$line,'context'=>$context,'state'=>$state);
		return $this->ignoreNativeHandler;
	}

	function exceptionHandler(Exception $e){
		return $this->handler(E_ERROR,"Uncaught exception '".get_class($e)."' with message '".$e->getMessage()."'",$e->getFile(),$e->getLine(),"\n".$e->getTraceAsString());
	}

	function shutdown(){
		$lastError = error_get_last();
		if( null!== $lastError){
			$this->handler($lastError['type'],$lastError['message'],$lastError['file'],$lastError['line']);
		}
		$hasErrors = (self::$instance && !empty(self::$instance->errors))?true:false;
		if(! $hasErrors){
			self::clear();
			return;
		}
		if( constant('DEVEL_MODE') ){
			echo $this->getErrors(PHP_SAPI==='cli'?'cli':'default');
		}
		if( constant('ERROR_REPORT_MAIL') ){
			$pageUrl = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
			$sender  = constant('ERROR_REPORT_MAIL_SENDER')?ERROR_REPORT_MAIL_SENDER:(empty($_SERVER['SERVER_ADMIN'])?'smvcreporter@'.$_SERVER['SERVER_NAME']:$_SERVER['SERVER_ADMIN']);
			$body = 'Error page: <a href="'.$pageUrl.'">'.$pageUrl.'</a><br /><br />'.$this->getErrors('mail');
			easymail::mailTpl(ERROR_REPORT_MAIL,"[$_SERVER[SERVER_NAME]] SMVC Error report",$body,$sender);
		}
		self::clear();
	}
}
