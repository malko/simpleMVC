<?php

/** internal exception thrown by jsonRPC */
class jsonRpcException extends Exception{
	protected  $code = 32098;
}

/** exception to throw in your methods to be treated as jsonRPC error response */
class jsonRpcMethodException extends jsonRpcException{
	protected  $code = 32099;
}

/**
* class to ease creation of jsonRPC services
* @author jgotti at modedemploi dot fr
* @licence LGPL
* @since 2011-02-25
* @changelog
*            - 2012-08-21 - syncRequest() remove unwanted use of urlencode for curl request
*                         - now syncRequest throw jsonRpcException instead of RuntimeException on error
*            - 2012-08-20 - add unbindMethod() method
*            - 2012-07-25 - add optional constructor parameter $endPoint
*                         - now htmlDiscovery allow to test the service directly
*            - 2012-06-20 - correctly check discovery / htmlDiscovery allowed when changed after init time
*                         - some little work on htmlDiscovery
*            - 2012-05-29 - some rewrite in jqueryProxy now binded methods are exposed directly.
*            - 2011-07-22 - make use of curl in syncRequest when enable else default to fsockopen
*            - 2011-05-30 - add htmDiscovery method and some more information on discovery
*                         - add a $noCache and $allowDiscovery static properties
*                         - if $allowDiscovery is true and no request is passed then will return the result of htmlDiscovery
*                         - bug correction in init() that avoided to bind static class at init time
*            - 2011-04-27 - add $autoCleanMagicQuotes property
*/
class jsonRPC{
	static public $falseIsError=false;
	static public $autoCleanMagicQuotes = true;
	static public $noCache = false;
	static public $allowDiscovery=true;
	private $callback = null;
	private $methods = array();
	private $processingRequest = null;
	public $endPoint = null;

	static public $errors= array(
		'PARSE_ERROR'   => array('code'=>-32700,'message'=>'Parse error.','data'=>'Invalid JSON. An error occurred on the server while parsing the JSON text.'),
		'REQUEST_ERROR' => array('code'=>-32600,'message'=>'Invalid Request.','data'=>'The received JSON is not a valid JSON-RPC Request.'),
		'METHOD_ERROR'  => array('code'=>-32601,'message'=>'Method not found.','data'=>'The requested remote-procedure does not exist / is not available.'),
		'PARAMS_ERROR'  => array('code'=>-32602,'message'=>'Invalid params.','data'=>'Invalid method parameters.'),
		'INTERNAL_ERROR'=> array('code'=>-32603,'message'=>'Internal error.','data'=>'Internal JSON-RPC error.'),
		'SERVER_ERROR'  => array('code'=>-32099,'message'=>'Server error.')//,'data'=>'Reserved for implementation-defined server-errors.'),
			//array(-32099<->-32000','Server error.','Reserved for implementation-defined server-errors.');//
	);

	/**
	* @param string $endPoint url of the service endPoint if not the one of the actual displaying page. (use for proxy and discovery methods)
	*/
	function __construct($endPoint=null){
		if( !empty($_GET['callback'])){
			$this->callback = $_GET['callback'];
		}
		if( null === $endPoint ){
			$endPoint = (stripos($_SERVER['SERVER_PROTOCOL'],'HTTPS')!==false?'https':'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
		}
		$this->endPoint = $endPoint;
		$this->bindMethod('discovery',array($this,'discovery'))
			->bindMethod('htmlDiscovery',array($this,'htmlDiscovery'))
			->bindMethod('jqueryProxy',array($this,'jqueryProxy'))
		;
		set_error_handler(array($this,'phpErrorHandler'));
	}

	static function init($serviceDefinition,$classMapping=null,$bindParentMethodsToo=false){
		$i = new self;
		if( is_object($serviceDefinition) || (is_string($serviceDefinition) && class_exists($serviceDefinition,false)) ){
			$i->bindClass($serviceDefinition,$classMapping,$bindParentMethodsToo);
		}else{
			$i->bindMethod($serviceDefinition);
		}
		return $i->response($i->processRequest());
	}


	function bindClass($class,$mapping=null,$bindParentMethodsToo=false){
		$static = is_string($class)?true:false;
		$rclass = new ReflectionClass($class);
		$className = $rclass->name;
		$methods = $rclass->getMethods(ReflectionMethod::IS_PUBLIC);
		if(! $methods ){
			return $this;
		}
		foreach($methods as $m){
			$name = $m->name;
			if( (! $bindParentMethodsToo) && $m->getDeclaringClass()->name !== $className ){
				continue;
			}
			if( $mapping ){
				if(!( $k = array_search($name,$mapping,true)) ){
					continue;
				}
				$name = is_numeric($k)?$name:$k;
			}
			if( $static === $m->isStatic()){
				$this->bindMethod($name,array($class,$m->name));
			}
		}
		return $this;
	}

	/**
	* register a new service method
	* @param string $methodName name of the public exposed method You may also pass a list of methods in form array(methodName => $callback,...) or array(methodName,...) for quick setup.
	* @param callable $callback the function or object method to call to bind to this methodName (if null will use $methodName as $callback)
	* return $this for method chaining
	*/
	function bindMethod($methodName,$callback=null){
		if( null===$callback){
			if(! is_array($methodName) ){
				return $this->bindMethod($methodName,$methodName);
			}
			foreach($methodName as $m=>$cb){
				if( is_numeric($m) && $cb){ $m = $cb;}
				$this->bindMethod($m,$cb);
			}
			return $this;
		}
		if( ! is_callable($callback)){
			return $this->response($this->error('SERVER_ERROR','Attempting to bind uncallable method.'));
		}
		$this->methods[$methodName] = $callback;
		return $this;
	}

	/**
	* unregister a previously registered service method
	* @param string $methodName name of the public exposed method may be a list of method names to unbind
	* return $this for method chaining
	*/
	function unbindMethod($methodName){
		if( is_array($methodName) ){
			foreach($methodName as $m){
				$this->unbindMethod($m);
			}
			return $this;
		}
		if(! isset($this->methods[$methodName])){
			return $this->response($this->error('INTERNAL_ERROR','trying to unbind unknown method.'));
		}
		unset($this->methods[$methodName]);
		return $this;
	}


	function getRequest(){
		$request = $_REQUEST;
		if( self::$autoCleanMagicQuotes && get_magic_quotes_gpc() ){
			static $cleanMQ;
			if( !isset($cleanMQ)){
				$cleanMQ = create_function('&$v,$k','$v=stripslashes($v);');
			}
			array_walk_recursive($request,$cleanMQ);
		}
		if( isset($request['jsonrpc']) ){
			$request = (object) array_merge(array('id'=>null,'method'=>null,'params'=>null),(array) json_decode($request['jsonrpc']));
		}elseif(isset($_REQUEST['method'])){
			$params = isset($request['params'])?(is_array($request['params'])?$request['params']:(array)json_decode($request['params'])):null;
			$request = (object) array(
				'id' => empty($request['id'])?null:$request['id'],
				'method'=>$request['method'],
				'params'=>(isset($request['params']) && null===$params)?(array)$request['params']:$params,
			);
		}elseif( strlen($rawDatas = file_get_contents('php://input')) ){
			$request = json_decode(get_magic_quotes_gpc()?stripslashes($rawDatas):$rawDatas);
			if( null === $request)
				return $this->response($this->error('PARSE_ERROR'));
		}else{
			$request= null;
		}
		return null!==$request?$request:(self::$allowDiscovery?$this->htmlDiscovery():$this->response($this->error('REQUEST_ERROR')));
	}

	function processRequest($request=null){
		$request = $this->getRequest();
		if( is_array($request) ){
			$responses = array();
			foreach( $request as $r){
				$r = $this->processRequest($r);
				if( is_object($r) )
					$responses[] = $r;
			}
			return $responses;
		}
		$this->processingRequest = $request;
		# -check method validity
		if(! isset($request->method,$this->methods[$request->method]) ){
			$e = $this->error('METHOD_ERROR');
			$this->processingRequest =null;
			return $e;
		}
		#- preparing parameters
		if(! ( isset($request->params) && ! in_array($request->params,array('',null,false),true)) ){
			$request->params = array();
		}elseif(is_object($request->params) ){
			$request->params = (array) $request->params;
		}elseif( ! is_array($request->params) ){
			$request->params = array($request->params);
		}
		#- preparing response object
		$response = new stdClass;
		if( isset($request->id) )
			$response->id = $request->id;
		#- if( isset($request->jsonrpc) && $request->jsonrpc == "2.0")
			#- $response->jsonrpc = "2.0";
		#- call request method
		try{
			$response->result = call_user_func_array($this->methods[$request->method],$request->params);
		}catch(jsonRpcException $e){
			$e = $this->error($e->getCode(),$e instanceof jsonRpcMethodException ?"method result error":"Server method error",$e->getMessage());
			$this->processingRequest =null;
			return $e;
		}catch(Exception $e){
			$e = $this->error(-32098,"Server method error",$e->getMessage());
			$this->processingRequest =null;
			return $e;
		}
		if( $response->result === null && ! $request->id ){
			$this->processingRequest =null;
			return null;
		}else if( self::$falseIsError && $response->result === false){
			$e = $this->error("SERVER_ERROR");
			$this->processingRequest =null;
			return $e;
		}
		$this->processingRequest =null;
		return $response;
	}

	function phpErrorHandler($no,$str,$file,$line,$context=null){
		if (!(error_reporting() & $no)) { //-- only trigger codes included in error_reporting
			return;
		}
		$errorsNo = array(
			E_USER_ERROR => 'Fatal error',
			E_ERROR => 'Fatal error',
			E_COMPILE_ERROR => 'Fatal error',
			E_RECOVERABLE_ERROR => 'Fatal Recoverable error',
			E_USER_WARNING => 'Warning',
			E_WARNING => 'Warning',
			E_USER_NOTICE => 'Notice',
			E_NOTICE => 'Notice',
			E_PARSE => 'Parse error',
			E_STRICT => 'Strict standards',
		);
		$errorType = isset($errorsNo[$no])?$errorsNo[$no]:"Unknown error($no)";
		throw new jsonRpcException("$errorType: $str in $file on line $line");
		return true;
	}

	function error($error,$msg=null,$data=null){
		if( is_array($error) || is_object($error)){
			if( null !== $msg )
				$error['message'] = $msg;
		}else{
			if(isset(self::$errors[$error]) ){
				$error = self::$errors[$error];
			}else{
				$error = array_merge(self::$errors['SERVER_ERROR'],array(is_numeric($error)?'code':'message'=>$error));
			}
		}
		if( null!==$msg)
			$error['message'] = $msg;
		if( null!==$data)
			$error['data'] = $data;
		$error = array("error"=>$error);
		if( $this->processingRequest && $this->processingRequest->id)
			$error['id'] = $this->processingRequest->id;
		return (object) $error;
	}

	function response($response){
		static $notNull;
		if( ! isset($notNull) ){
			$notNull = create_function('$v','return $v!==null?true:false;');
		}
		if( jsonRPC::$noCache ){
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // set a past date
		}
		header('Content-type: application/'.($this->callback?'javascript':'json'));
		$response = json_encode(array_filter((array) $response,$notNull));
		echo $this->callback?"$this->callback($response);":$response;
		exit;
	}
	/**
	* return the jsonRPC server method descriptions in a json format.
	* @return stdclass
	*/
	function discovery(){
		if(! self::$allowDiscovery ){
			return $this->error('METHOD_ERROR');
		}
		$doc = array();
		foreach($this->methods as $m=>$cb){
			if( is_string($cb) && strpos($cb,':')){
				$cb = explode('::',$cb);
			}
			$ref = is_array($cb)?new ReflectionMethod($cb[0],$cb[1]):new ReflectionFunction($cb);
			$doc[$m]['params'] = $ref->getParameters();
			if( $comment = $ref->getDocComment()){
				$doc[$m]['comment'] = preg_replace(array('!^(\s*/\*|\s*\*|\s*\*+/)!m','!^\*\s*|\s*/?$!'),'',$comment);
				if( preg_match('!@return\s+\b(\S+)\b!i',$comment,$returnDoc)){
					$doc[$m]['return'] = $returnDoc[1];
				}
			}
			foreach($doc[$m]["params"] as $k=>$v){
				$doc[$m]['params'][$k] = array_filter(array(
					'name'=>$v->name,
					'optional'=>$v->isOptional()?true:null,
				));
				if( $v->isDefaultValueAvailable()){
					$doc[$m]['params'][$k]['default'] = $v->getDefaultValue();
				}
				if( $v->getClass()){
					$doc[$m]['params'][$k]['type'] = $v->getClass()->name;
				}else if($v->isArray()){
					$doc[$m]['params'][$k]['type'] = 'Array';
				}else if(preg_match('!@param\s+(int(eg[ea]r)?|array|mixed|str(ing)?|float|double|stdobject|object|bool)\s+\$?'.$v->name.'\b!i',$comment,$paramDoc)){
					$doc[$m]['params'][$k]['type'] = $paramDoc[1];
				}else{
					$doc[$m]['params'][$k]['type'] = 'scalar';
				}
			}
		}
		return $doc;
	}
	/**
	* return the page you're probably looking at
	* @return text/html
	*/
	function htmlDiscovery(){
		if(! self::$allowDiscovery ){
			return $this->error('METHOD_ERROR');
		}
		$docs = $this->discovery() ;
		if( empty($docs) ){
			echo "No public API";exit;
		}
		ksort($docs);
		?><!DOCTYPE HTML>
		<html>
		<head>
		<style type="text/css">
			body{font-size:.81em;font-family:Helvetica, Arial;}
			dt{font-weight:bold; background:#e0e0e0;color:#333;margin:1em 0 0 0;padding:.4em .8em;}
			dd{background:#d0d0d0;margin:0 1em;padding:.4em .8em;border-bottom-right-radius:.3em;border-bottom-left-radius:.3em; cursor: pointer;}
			dd form{ display:none;border: solid #444 1px;padding:1em;margin:.5em; border-radius:.4em;}
			dd label{ width:150px; vertical-align: middle; display: inline-block;}
			h1,h2{font-size:1.6em;color:#444;background:#e5e5e5;padding:.4em;}
			h2{ font-size:1em;}
			.section{ margin-left:1em; }
			.footer{ font-size:.8em; text-align: center; }
			a{ color:#333; text-decoration: none;}
		</style>
		</head>
		<body>
		<h1>How to use this service</h1>
		<div class="section">
			here’s a list of different ways to call a service method in same order the service will try to handle them.
			<ul>
				<li>
					Using a full jsonrpc request passed in as a single parameter of a GET/POST request.
					If a GET/POST parameter named jsonrpc contains a full jsonrpc request (for more infos, search the web for jsonrpc definition)
					here’s a example:
					<br /><code>jsonrpc={id:"requestUniqueId",method:"methodname",params:["param1","param2"]})</code>
					then it will proceed with that
				</li>
				<li>
					Using a partial jsonrpc request passed as multiple GET/POST parameters this is the way we use to try our service in this overview,
					each part of the request is passed as GET/POST parameters.
					<br />Here’s a GET example:
					<br /><code>http://myserver.com/service.php?id=requestUniqueID&amp;method=methodName&amp;params=["param1","param2"]</code>
				</li>
				<li>
					Using a true jsonrpc request as raw POST datas this is the way a jsonrpc is intended to work
					search the web for jsonrpc service definition for some examples, it’s the harder but better way to go.
				</li>
			</ul>
			<h2>JSONP</h2>
			If you prefer to get a JSONP response simply add a callback parameter to the queryString of the requested service url.
			For example with this service:
			<br /><code>http://<?php echo $this->endPoint; ?>?method=discovery&callback=mycallback</code>
			will return <code>mycallback({"result":{...this discovery as object...}});</code>
		</div>
		<h1>Methods available for this service</h1>
		<div class="section">
			<dl>
		<?php
		foreach($docs as $m=>$doc){
			$params = array();
			$testParams = array();
			if( isset($doc['params'])){
				foreach($doc['params'] as $k=>$p){
					$p['name'] = "$p[type] $p[name]".(isset($p['default'])?" = ".var_export($p['default'],1):'');
					$params[] = (!isset($p['optional'])?$p['name']:"[$p[name]]");
					$testParams[] = "<label for=\"[$m[$k]]\">$p[name]</label> <input name=\"params[]\" id=\"[$m[$k]]\"/>";
					//$params[] = $p['toString'];
				}
			}
			$comment = empty($doc['comment'])?'':'<dd title="click to test this method">'.nl2br($doc['comment'])
				.'<form method="get" action="'.$this->endPoint.'" target="jsonRpcTest"><input type="hidden" name="method" value="'.$m.'"/>'.(empty($testParams)?'':implode('<br />',$testParams).'<br />').'<button type="submit">testService</button></form>'
				.'</dd>'
			;
			echo "<dt>".(isset($doc['return'])?"( $doc[return] ) ":'')."$m( ".implode(', ',$params)." )$comment";
		}
		?>
			</dl>
		</div>
		<div class="footer"><a href="http://projects.jgotti.net/blog/15" target="_blank">service implemented using class-jsonrpc.php</a></div>
		<script>
			(function(){
			var dds = document.getElementsByTagName('dd')
				, l=dds.length
				, i=0
				, toggleForm=function(){ var f = this.getElementsByTagName('form')[0].style; f.display = f.display==='block'?'none':'block'}
				, disable = function(e){
					e.stopImmediatePropagation();
				}
			;
			for(;i<l;i++){
				dds[i].onclick=toggleForm;
				dds[i].getElementsByTagName('form')[0].onclick=disable
			}
			})()
		</script>
		</body></html>
		<?php
		exit;
	}
	/**
	* return a javascript proxy class to call directly this jsonRPC server. It require jquery to make calls
	* In your html page add a &lt;script src="http://exemple.com/myjsonrpcserver.php/?method=jqueryProxy"&gt;&lt;/script&gt;
	* then call jsonrpc.request('method',[param1,param2...],function(result){ success code },function(result){error code});
	* @param string $proxyName name of the javascript variable containing the proxy
	* @param string $endPoint  optionnal endpoint will default to this server page
	* @return text/javascript
	*/
	function jqueryProxy($proxyName='jsonrpc',$endPoint=null){
		if( null !== $endPoint ){
			$this->endPoint = $endPoint;
		}
		header('Content-type: application/javascript');
		foreach( $this->methods as $k=>$v){
			if( ! in_array($k,array('jqueryProxy','htmlDiscovery','discovery'),true) ){
				$exposedMethods[] = "$k: function(){Array.prototype.splice.call(arguments,0,0,'$k');return this.request.apply(this,arguments);}";
			}
		}
		echo "(function($){
	var RID = '".$proxyName."0'
	, callbacks={}
	, generateId = function (){return (RID = RID.replace(/\d+$/,function(m){return parseInt(m,10)+1;}));}
	window.$proxyName={
		endpoint:'".$this->endPoint."'
		,request: function(method,params,success,error){
			var id=generateId();
			if(success||error){callbacks[id]=[success,error];}
			//if( ! params instanceof Array ){ params = [params]; };
			\$.ajax({url:this.endpoint,dataType:'jsonp',data:{id:id,method:method,params:params,callback:'$proxyName.callback'}});
		}
		".(empty($exposedMethods)?"":','.implode("\n\t\t,",$exposedMethods))."
		,callback:function(r){
			if( ! (r && r.id && callbacks[r.id]) ){
				return false;
			}
			var cbs=callbacks[r.id],cb = r.error?(cbs[1]?cbs[1]:false):(cbs[0]?cbs[0]:false),res=(typeof r.result !== 'undefined')? r.result : (r.error?r.error:null);
			delete callbacks[r.id];
			if($.isFunction(cb)){
				return cb(res);
			}else if( cb) {
				return (new Function('r','return '+cb+'(r);'))(res);
			}
			return false;
		}
	};
})(jQuery);";
		exit(0);
	}

	static function syncRequest($uri,$request,array $params=null,$timeOut=15){
		if( is_string($request) ){
			$request = array('method'=>$request,'params'=>$params,'id'=>uniqid());
		}else{
			$request = (object) $request;
			if($params)
				$request->params = array_merge((isset($request->params)?(array) $request->params:array()),$params);
			if( !isset($request->id) )
				$request->id = uniqid();
		}
		if( function_exists('curl_setopt_array') ){
			$curl = curl_init();
			curl_setopt_array($curl,array(
				CURLOPT_RETURNTRANSFER=>true         # get result as string instead of stdout printing
				,CURLOPT_HEADER => false 		         # no header in the results
				,CURLOPT_URL => $uri                 # url d'appel
				,CURLOPT_POST => true                # this is a post request
				,CURLOPT_POSTFIELDS => json_encode($request) # here's the jsonrpc request
				,CURLOPT_FOLLOWLOCATION => true 		 # allow redirect
				,CURLOPT_MAXREDIRS => 5 	         	 # max redirect
				,CURLOPT_CONNECTTIMEOUT => $timeOut  # max connection time
				,CURLOPT_TIMEOUT => $timeOut         # max get time
				,CURLOPT_FAILONERROR => true
				,CURLOPT_SSL_VERIFYPEER => false
			));
			$response = curl_exec($curl);
			if( false===$response ){
				throw new jsonRpcException(__class__.':'.__function__.
				"Error while requesting $uri : ".curl_error($curl));
			}
			curl_close($curl);
		}else{
			preg_match('!^http(s?)://([^/]+)(.*)$!',$uri,$m);
			$fp = fsockopen(($m[1]?'ssl://':'').$m[2],$m[1]?443:80,$errno,$errstr,$timeOut);
			if(! $fp ){
				throw new jsonRpcException("$errno - $errstr");
			}
			$request=json_encode($request);
			$requestContent = array(
				"POST $m[3] HTTP/1.1",
				"Host: $m[2]",
				"User-Agent: authentificationApi",
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
		return json_decode($response);
	}
}
