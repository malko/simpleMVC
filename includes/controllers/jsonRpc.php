<?php
/**
* @changelog
*           - 2011-05-23 - add actionStack management
*/
class jsonRpcController extends abstractController{

	public $bindedMethods = array();
	public $jsonRpc=null;

	function init(){
		parent::init();
		jsonRpc::$falseIsError=false;
		jsonRPC::$autoCleanMagicQuotes=false;
		$this->jsonRpc = new jsonRPC();
		$className = get_class($this);
		$rclass = new ReflectionClass($className);
		$methods = $rclass->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach($methods as $m){
			if( preg_match('!^(.*?)Action$!',$m->name,$match) && ! isset($this->bindedMethods[$match[1]]) && $m->getDeclaringClass()->name===$className){
				$this->bindedMethods[$match[1]] = $m->name;
			}
		}
		$this->jsonRpc->bindClass($this,empty($this->bindedMethods)?null:$this->bindedMethods);
	}
	//-- return the jqueryProxy javascript --//
	protected function indexAction($proxyname=null){
		$proxyname = $proxyname?preg_replace('![^a-zA-Z0-9_]!','',$proxyname):$this->getName();
		smvcShutdownManager::unregister(null);
		return $this->jsonRpc->jqueryProxy($proxyname,$this->url(':'));
	}

	function __call($m,$a=null){
		$rawDatas  = strlen($rawDatas=file_get_contents('php://input'))?json_decode($rawDatas):null;
		if( preg_match("!^[^:]+:$m$!",DEFAULT_DISPATCH)){
			#- check for a method params
			$_m = false;
			if( isset($_REQUEST['method'])){
				$_m = $_REQUEST['method'];
			}elseif( isset($_REQUEST['jsonrpc']) ){
				if( ($request = json_decode($_REQUEST['jsonrpc'])) && !empty($request->method)){
					$_m = $request->method;
				}
			}elseif( $rawDatas && !empty($rawDatas->method)){
				$_m = $rawDatas->method;
			}
			if( $_m && isset($this->bindedMethods[$_m]) || in_array($_m,array('discovery','htmlDiscovery','jqueryProxy'),true))
				$m = $_m;
		}
		if(isset($this->bindedMethods[$m]) || in_array($m,array('discovery','htmlDiscovery','jqueryProxy'),true) ){
			#- check if we need to append method to request and where to add
			if( isset($_REQUEST['jsonrpc']) ){
				if(! isset($_REQUEST['method'])){
					$jsonrpc = json_decode($_REQUEST['jsonrpc']);
					if(! isset($jsonrpc->method) ){
						$jsonrpc->method = $m;
						$_REQUEST['jsonrpc'] = json_encode($jsonrpc);
					}
				}
			}else if($rawDatas){
				$rawDatas->method = $m;
				$_REQUEST['jsonrpc'] = json_encode($rawDatas);
			}else{
				$_REQUEST['method'] = $m;
				if( (! isset($_REQUEST['params'])) && !empty($a) ){
					$_REQUEST['params'] = $a;
				}
			}
			smvcShutdownManager::unregister(null);
			$this->_currentActionStart($m);
			$res = $this->jsonRpc->response($this->jsonRpc->processRequest());
			$this->_currentActionEnd($m);
			return $res;
		}

		if( in_array($m,array('error','response'),true) && ! method_exists($this,$m.'Action') ){
			smvcShutdownManager::unregister(null);
			$this->_currentActionStart($m);
			$res =  call_user_func_array(array($this->jsonRpc,$m),$a);
			$this->_currentActionEnd($m);
			return $res;
		}

		return parent::__call($m,$a);
	}

}
