<?php
/**
* @package simpleMVC
* @class smvcShutdownManager
* @since 2010-12-20
* @licence dual licence LGPL / MIT
* @author jonathan gotti <jgotti at jgotti dot net>
*/
class smvcShutdownManager{

	static private $_registeredCallbacks = array();
	static private $_byPassCallBacks = false;
	static private $_registered = false;
	static private $_id = 0;

	/**
	* Check that given callable is registered by shutdown manager. It also allow to check that smvcShutdownManager is registered itself
	* @param callback $callBack       check that the given callback (or id of registered callback return by register method) is registered (if null then check that smvcShutdownManager itself is registered)
	* @param bool     $returnPriority if true the actual priority level of the callback will be return instead of bool (be aware it may return 0 in such case that doesn't mean false)
	* @return bool or integer if $returnPriority is passed to true
	*/
	static public function isRegistered($callBack=null,$returnPriority=false){
		if( null === $callBack ){
			return self::$_registered;
		}

		if( is_int($callBack) ){
			return isset(self::$_registeredCallbacks[$callBack])?($returnPriority?self::$_registeredCallbacks[$callBack][1]:true):false;
		}

		foreach(self::$_registeredCallbacks as $cb){
			if( $cb[0]===$callBack)
				return $returnPriority?$cb[1]:true;
		}
		return false;
	}

	/**
	* register a callback function to be executed as a shutdown fucntion
	* @param callback $callBack the callback to register, if already registered then it
	* @param int      $priority the priority level of the callback (higher level means later call)
	* @param mixed    $param    you can add as many optionnal parameter as  you want to the callback
	*/
	static public function register($callBack,$priority=0){
		if(! self::$_registered ){
			register_shutdown_function(__class__.'::_registered_shutdown');
			self::$_registered = true;
		}
		$params = func_get_args();
		self::$_registeredCallbacks[++self::$_id] = array($callBack,(int) $priority,self::$_id,array_slice($params,2));
	}

	/**
	* unregister previously registered callback
	* @param callback $callBack the callback to unregister (or the callback id returned by register method )
	* @return bool return true if successfully removed else return false
	*/
	static public function unregister($callBack){
		if( is_int($callBack) ){
			if( !isset(self::$_registered[$callBack]))
				return false;
			unset(self::$_registered[$callBack]);
			return true;
		}
		foreach(self::$_registeredCallbacks as $k=>$cb){
			if( $cb[0]===$callBack){
				unset(self::$_registeredCallbacks[$k]);
				return true;
			}
		}
		return false;
	}

	/**
	* shutdown the script by calling exit.
	* @param mixed $status may be a string as in die or a status code (@see exit)
	* @param boo   $byPassCallBacks if true then will do a normal exit without calling any of the registered callbacks
	*/
	static public function shutdown($status=0,$byPassCallBacks=false){
		self::$_byPassCallBacks = $byPassCallBacks;
		exit($status?$status:0);
	}

	/**
	* THIS IS NOT INTENTED TO BE CALLED OTHER THAN INTERNALLY
	* the only reason for this to be public is that it's a necessity for register_shutdown_function to see it
	* there's no reason at all for you to call this
	* @internal
	*/
	static public function _registered_shutdown(){
		if( self::$_byPassCallBacks )
			return;
		#- first sort the stack
		uasort(self::$_registeredCallbacks,__class__.'::_compare');
		foreach( self::$_registeredCallbacks as $cb){
			call_user_func_array($cb[0],$cb[3]);
		}
	}

	/**
	* used to sort callbacks by priority respecting their registering order
	* @internal
	* @private
	*/
	static private function _compare($a,$b){
		if( $a[1] === $b[1] ){
			return $a[2] > $b[2]?1:-1;
		}
		return $a[1] > $b[1]?1:-1;
	}
}
