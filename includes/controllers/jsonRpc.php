<?php
class jsonRpcController extends abstractController{

	public $bindedMethods = array();
	public $jsonRpc=null;

	function init(){
		parent::init();
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
			if( $_m && isset($this->bindedMethods[$_m]) || in_array($_m,array('discovery','jqueryProxy'),true))
				$m = $_m;
		}
		if(isset($this->bindedMethods[$m]) || in_array($m,array('discovery','jqueryProxy'),true) ){
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
			}
			smvcShutdownManager::unregister(null);
			return $this->jsonRpc->response($this->jsonRpc->processRequest());
		}

		if( in_array($m,array('error','response'),true) && ! method_exists($this,$m.'Action') ){
			return call_user_func_array(array($this->jsonRpc,$m),$a);
		}

		return parent::__call($m,$a);
	}

}
