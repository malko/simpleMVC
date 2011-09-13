<?php
/**
* definition des fonctions communes
* @package simpleMVC
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
* - 2011-01-06 - little rework on autoloading
* - 2010-09-28 - new error handling
* - 2010-01-04 - add cli support for show
* - 2009-06-22 - autoloading viewHelpers will now check in active view setted _viewDirs
* - 2009-04-20 - now more show when not in DEVEL_MODE
* - 2009-03-16 - use spl_register_autoload for better autoload support when included in other application.
* - 2009-02-06 - move javascript and style from show to sMVCdevelBar
* - 2008-10-21 - autoload modification to check modelCollection extended classes in corresponding model file
* - 2008-09-12 - now try to load a specific config file for the current used front
* - 2008-05-06 - more understandable show output for trace
* - 2008-05-01 - add modelAddons lookup to __autoload
* - 2008-04-12 - new function html_substr and trace option for show
* - 2008-03-23 - add abstractModels lookup to __autoload
*/

#- define common paths
define('LIB_DIR',dirname(__file__));

#- load configurtations files
require LIB_DIR.'/fx-conf.php';
require LIB_DIR.'/smvc/shutdownManager.php';

if( defined('FRONT_NAME') && file_exists(CONF_DIR.'/'.FRONT_NAME.'_config.php') ) #- specific front config
	parse_conf_file(CONF_DIR.'/'.FRONT_NAME.'_config.php');
if(file_exists(CONF_DIR.'/config.php')) #- general config
	parse_conf_file(CONF_DIR.'/config.php');

#- remove possible unwanted magic_quotes
if(get_magic_quotes_gpc()){
	function stripslashes_deep($value) {
		$value = is_array($value) ?array_map('stripslashes_deep', $value) :stripslashes($value);
		return $value;
	}
	foreach(array('POST', 'GET', 'REQUEST', 'COOKIE') as $gpc)
		$GLOBALS["_$gpc"] = array_map('stripslashes_deep', $GLOBALS["_$gpc"]);
}

#- display and prepare error to go into simpleMVC toolbar if we're on devel_mode
if( defined('DEVEL_MODE') && DEVEL_MODE){
	#- ini_set('error_prepend_string','<div class="php_error">'.preg_replace('!<br\s*/?'.'>!','',ini_get('error_prepend_string')));
	#- ini_set('error_append_string',preg_replace('!<br\s*/?'.'>!','',ini_get('error_append_string')).'</div>');
	error_reporting(E_ALL | E_STRICT);
	//-- devel bar management;
	if( PHP_SAPI !== 'cli' ){
		$_SMVC_BENCH_ = array(
			'start'=> microtime(true),
			'initMem' => memory_get_usage(true)
		);
		ob_start();
		function smvcPrintDevelBar(){
			if( class_exists('baseView',false) && baseView::hasLivingInstance() && baseView::getInstance()->getController() && ! simpleMVCdevelBar_viewHelper::$disable ){
				$develBar = baseView::getInstance()->simpleMVCdevelBar();
			}else{
				return ob_end_flush();
			}
			$out = ob_get_clean();
			if( $tmp = strripos($out,'</body>') ){
				echo substr_replace($out, $develBar, $tmp, 0);
			}else{
				echo $out, $develBar;
			}
		}
		#- register_shutdown_function('smvcPrintDevelBar');
		smvcShutdownManager::register('smvcPrintDevelBar');
	}
}

/** manage jsplugin pendings to always be included in the header */
if( defined('JS_TO_HEAD') && JS_TO_HEAD){
	ob_start();
	function smvcJsPendingToHeader(){
		if(! class_exists('baseView',false)){
			return ob_end_flush();
		}
		$view = baseView::hasLivingInstance(true);
		if( false === $view ){
			return ob_end_flush();
		}
		$jsHelper = $view->helperLoaded('js',true);
		if(! $jsHelper instanceof viewHelperInterface){
			return ob_end_flush();
		}
		$out = ob_get_clean();
		if(preg_match('!</(?:head|body)>\s*!i',$out)){
			echo preg_replace('!</(?:head|body)>\s*!i',$jsHelper->getPending().'$0',$out,1);
		}else{
			echo $out.$jsHelper->getPending();
		}
	}
	#- register_shutdown_function('smvcJsPendingToHeader');
	smvcShutdownManager::register('smvcJsPendingToHeader');
}
//*/

###--- AUTOLOAD ---###
if( function_exists('spl_autoload_register')){
	spl_autoload_register('smvcAutoload');
}else{
	function __autoload($className){
		return smvcAutoload($className);
	}
}
function smvcAutoload($className){
	$dirs[] = LIB_DIR;
	if(preg_match('!^(db|model[Gg]enerator|abstract[mM]odel)$!',$className))
		return require LIB_DIR.'/db/class-'.strtolower($className).'.php';
	if( defined('FRONT_NAME') ){
		if( preg_match('!(?:_c|C)ontroller$!',$className,$m) ){ #- look for controller
			$dirs[] = LIB_DIR.'/controllers';
			$dirs[] = ROOT_DIR.'/controllers';
			$className = str_replace($m[0],'',$className);
		}elseif( preg_match('!(?:_v|V)iew((?:_h|H)elper)?$!',$className,$m) ){ #- look for views and helpers
			if(empty($m[1])){
				$dirs[] = LIB_DIR.'/views';
				$dirs[] = ROOT_DIR.'/views';
			}else{
				foreach( abstractController::getCurrentViewInstance(true)->getViewDirs() as $d)
						$dirs[] = "$d/helpers";
			}
			$className = str_replace($m[0],'',$className);
		}elseif( preg_match('!(?:_m|M)odelAddon(?:Interface)?$!',$className,$m) ){#- look for modelAddons and their interface
			if( defined('MODELADDONS_DIR') )
				$dirs = array_merge($dirs,explode(',',MODELADDONS_DIR));
			$dirs[] = LIB_DIR.'/modelAddons';
			$dirs[] = ROOT_DIR.'/modelAddons';
			$className = str_replace($m[0],'',$className);
		}else{ #- default look for models
			if( preg_match('!Collection$!',$className) ) #- modelCollection must be looked in models files
				$className = preg_replace('!Collection$!','',$className);
			$dirs[] = LIB_DIR.'/models';
			$dirs[] = ROOT_DIR.'/models';
			if( defined('MODELS_DIR') )
				$dirs = array_merge($dirs,explode(',',MODELS_DIR));
		}
		#- add path corresponding to front controllers
		if( FRONT_NAME !== 'default' ){
			foreach($dirs as $d){
				if( (! preg_match('!^'.LIB_DIR.'!',$d)) && is_dir($tmp = str_replace(ROOT_DIR,APP_DIR,$d)) )
					$dirs[] = $tmp;
			}
		}
	}
	$dirs = array_reverse($dirs);
	foreach($dirs as $dir){
		$cname = $className;
		do{
			$classFiles = array(
				"$dir/$cname.php",
				"$dir/class-".strtolower($cname).'.php'
			);
			if( preg_match('/^[A-Z]/',$cname) )
				array_splice($classFiles,1,0,"$dir/".strtolower(substr($cname,0,1)).substr($cname,1).'.php');
			if( defined('DEVEL_MODE') && DEVEL_MODE )
				$_dbg[] = $classFiles;
			foreach($classFiles as $classFile){
				if( is_file($classFile)){
					require $classFile;
					return true;
				}
			}
			$split = preg_split('!(?<=[a-z])(?=[A-Z])|_!',$cname,2);
			if(! isset($split[1]) )
				break;
			list($_dir,$cname) = $split;
			$dir .= "/$_dir";
			if(! is_dir($dir))
				break;
		}while($cname);
	}
	if( defined('DEVEL_MODE') && DEVEL_MODE)
		show($_dbg,'trace');
	throw new Exception("classe $className introuvable.");
}
###--- SOME FORMAT AND CLEAN METHOD ---###
/** remove accented chars (iso-8859-1 and UTF8) */
function removeMoreAccents($str){
	static $convTable;
	# create conversion table on first call
	if(! isset($convTable) ){
		$tmpTable = array(
			'µ'=>'u',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'AE',
			'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E',
			'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ð'=>'D', 'Ñ'=>'N',
			'Ò'=>'O', 'Œ'=>'OE', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O',
			'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'ß'=>'s',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'ae',
			'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
			'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ñ'=>'n',
			'ð'=>'o', 'œ'=>'oe', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o',
			'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ÿ'=>'y',
			'’'=>'\'','`'=>'\'',
		);
		$keys  = array_keys($tmpTable);
		$values= array_values($tmpTable);
		# check internal encoding
		if(ord('µ')===194){ # we are already in utf 8
			$utf8keys = $keys;
			$keys     = array_map('utf8_decode',$keys);
		}else{
			$utf8keys = array_map('utf8_encode',$keys);
		}
		if(function_exists('array_combine')){
			$convTable = array_merge(array_combine($utf8keys,$values),array_combine($keys,$values));
		}else{
			foreach($utf8keys as $n=>$k){
				$convTable[$k] = $convTable[$keys[$n]] = $values[$n];
			}
		}
	}
	if(is_array($str)){
		foreach($str as $k=>$v)
			$str[$k] = strtr($v,$convTable);
		return $str;
	}
	return strtr($str,$convTable);
}
/** format de date */
function dateus2fr($date,$noTime=false){
	if( empty($date) )
		return '00/00/0000';
	if(! strpos($date,' '))
		return implode('/',array_reverse(preg_split('!/|-!',$date)));
	list($date,$time) = explode(' ',$date);
	return dateus2fr($date).($noTime?'':' '.$time);
}
function datefr2us($date,$noTime=false){
	if( empty($date) )
		return '0000-00-00';
	if(! strpos($date,' '))
		return implode('-',array_reverse(preg_split('!/|-!',$date)));
	list($date,$time) = explode(' ',$date);
	return dateus2fr($date).($noTime?'':' '.$time);
}

function html_substr($htmlStr,$start=0,$length=null,$appendStr='...'){
	if(strlen($htmlStr) < $length)
		return $htmlStr;
	$blockTags = '!t[rdhfba](?:ble|oot|ead|ody)?|div|[bh][r1-6]|form|p!i'; #- tags where space are not counting
	$tagStack = array();
	$outStr   = '';
	$pos = 0;
	while(strlen($htmlStr) > 0){
		if( $length!==null && ($pos-$start)>=$length){
			$outStr.=$appendStr;
			break;
		}
		++$pos;
		# first deal with tag or entities
		if(preg_match('!^(?:</?([a-zA-Z]+)(?:[^">]+|"[^"]*")*/?>|&[^\s;]+;)!s',$htmlStr,$m)){
			$match = $m[0];
			$htmlStr = substr($htmlStr,strlen($match));
			if($match[0]==='&'){ #- deal with entities
				if($pos > $start) $outStr.=$match;
				continue;
			}else{  #- deal with tag
				if(preg_match($blockTags,$m[1]))
					$htmlStr = ltrim($htmlStr);
				#- check self closed tag (hr, br, img and so on ...)
				if(substr($match,-2,1)==='/'){
					if($pos > $start) $outStr.=$match;
					continue;
				}
				$tag = strtolower($m[1]);
				--$pos;
				#- opening tag
				if($match[1]!=='/'){
					if($pos >= $start){
						array_unshift($tagStack,$tag);
						$outStr.=$match;
					}
					continue;
				}else{
					if( isset($tagStack[0]) && $tagStack[0]===$tag ){
						array_shift($tagStack);
					}elseif($k = array_search($tag,$tagStack,true)){
						unset($tagStack[$k]);
						$tagStack = array_values($tagStack);
					}else{ #- tag wasn't opened just drop it
						continue;
					}
					if($pos>=$start) $outStr.=$match;
					continue;
				}
			}
		}

		#- then deal with space
		if(preg_match('!^\s\s+!',$htmlStr,$m)){
			$htmlStr = ltrim($htmlStr);
			if($pos>$start) $outStr.=$m[0];
			#- @todo manage space that are not to be count
			continue;
		}
		#- then consider all as single char
		$outStr .= substr($htmlStr,0,1);
		$htmlStr = substr($htmlStr,1);
	}
	#- close opened tags
	if(count($tagStack)){
		foreach($tagStack as $tag)
			$outStr .= "</$tag>";
	}
	return $outStr;
}

/**
*renvoie la correspondance d'une parenthese capturante d'une expression reguliere ou FALSE
*@param string     $pattern expression réguliere perl
*@param string     $str chaine sur laquelle porte la recherche
*@param int|array  $id identifiant de la parenthese capturante dont on veut récuperer le motif default 1 (peut etre une liste d'id)
*@param bool       $all renvoie toutes les occurences dans string default FALSE
*@return mixed     (array si $id est une liste ou si $all==TRUE, string dans les autres cas et FALSE en cas d'erreur)
*/
function match($pattern,$str,$id=1,$all=FALSE){
	if($all){
		if(! preg_match_all($pattern,$str,$m) )
			return FALSE;
	}else{
		if(! ($r = preg_match($pattern,$str,$m)) ){
			if($r === FALSE){
				show("REGEX ERROR: ",$pattern);
			}
			return FALSE;
		}
	}
	if(is_array($id)){
		foreach($id as $v)
			$res[] = isset($m[$v])?$m[$v]:false;
		return $res;
	}elseif(isset($m[$id]) && ! in_array($m[$id],array(false,null),1) ){
		return $m[$id];
	}
	return FALSE;
}

###--- DEBUG HELPER ---###
function show(){
	if( ! (defined('DEVEL_MODE') && DEVEL_MODE) )
		return false;
	$args  = func_get_args();
	$argc  = func_num_args();
	$param = $args[$argc-1];
	$halt  = false;
	$getTrace  = false;
	$separator = "\n".str_repeat('——',50)."\n";
	if(is_string($param)){
		if(preg_match('!(^|;)trace(;|$)!',$param))
			$getTrace = true;
		if(preg_match('!(^|;)exit(;|$)!',$param))
			$halt = true;
		$color = match('!(?:^|;)color:([^;]+)(?:;|$)!',$param,1);
		if( $color || $halt || $getTrace)
			array_pop($args);
	}
	if(empty($color))
		$color = 'red';

	$str = array();
	foreach($args as $arg){
    $str[]= smvc_print_r($arg,1);
	}

	$str = implode($separator,$str);
	$trace = debug_backtrace();
	if($getTrace){
		$str.=$separator."↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ FOLLOWING IS BACKTRACE LAST CALL FIRST ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓$separator"
		 .implode("$separator",formatted_backtrace("in %location:\n   %call(%_args)",1,null,$trace));
	}
	$preStyle = 'style="color:'.$color.';border:dashed '.$color.' 1px;max-height:350px;overflow:auto;margin-top:0;"';
	$bStyle   = 'style="color:'.$color.';text-decoration:underline;margin-bottom:0"';
	$trace = formatted_backtrace("%location",0,0,$trace);

	if( PHP_SAPI === 'cli' ){
		require_once(LIB_DIR.'/db/scripts/libs/class-console_app.php');
		if(! preg_match('!black|red|green|brown|blue|magenta|cyan|grey!',$color) ){
			$color = 'red';
		}
		console_app::tagged_string("Show ( $trace )\n $str",$color,true);
		if( $halt ){
			console_app::msg('SCRIPT EXITED BY SHOW','bold|red');
			exit();
		}
		return false;
	}
	echo "<div class=\"show\" style=\"background:#F0F0F0;text-align:left;font-size:12px;\"><strong $bStyle>Show ( $trace )</strong><br />"
	#- ."<pre $preStyle>".highlight_string("<?php\n$str",1)."</pre></div>";
	."<pre $preStyle><xmp>$str</xmp></pre></div>";
	if($halt){
		echo "<h5 style=color:red;text-align:center;>SCRIPT EXITED BY SHOW</h5>";
		flush();
		exit();
	}
	return false; # just for convenience
}
function smvc_print_r($var,$return=false,$avoidedObjects=''){
	$res = print_r($var,true);
	$cleanExps = array(
		'!^\r?\n!m',
		'!^([\t ]+)!me', //-- reduce tab size
		'!((db|dbprofiler|mysqli?db|sqlitedb3?'.($avoidedObjects?"|$avoidedObjects":'').') Object)\n(\s+)\(.*?\n\3\)\n!si', //-- avoid dbs object printing
		'/(?!<\s|^)Object\s*\*RECURSION\*\s*\n/', //-- reduce recursion to one line
		'/(?!<\s|^)Array\s*\(\s*\)\n/', //-- reduce empty array to single line
	);
	$cleanReplace = array(
		'',
		'str_repeat(" ",strlen("$1")/2);',
		"$1 (#--> HIDDEN BY SMVC_PRINT_R <--#)\n",
		"Object (#--> RECURSION <--#)\n",
		"Array()\n"
	);
	$res = preg_replace($cleanExps,$cleanReplace,$res);
	if( $return )
		return $res;
	echo $res;
}
/*
highlight.string	"#DD0000"	PHP_INI_ALL
highlight.comment	"#FF8000"	PHP_INI_ALL
highlight.keyword	"#007700"	PHP_INI_ALL
highlight.bg	"#FFFFFF"	PHP_INI_ALL	Cette fonctionnalité obsolète sera certainement supprimée dans le futur.
highlight.default	"#0000BB"	PHP_INI_ALL
highlight.html	"#000000"	PHP_INI_ALL
*/
/**
* pre formatted version of debug_backtrace.
* @param string $formatString  string used to format the trace if null will use: "%location\n%call(%_args)"
*                              replacement values are %file,%line,%object,%class,%function,%type,%args (@see debug_backtrace doc for more info)
*                              added %call that will be replace by the object class name followed by the type and the method or the single function name
*                              %location equal to "%file(%line)"
*                              %_args that is a simplified representation of the parameters passed to call
*                              %id is the id of the trace in the stack
* @param int    $skippedLevel  number of level to remove from the begining of the stack (usefull to embed in more complex debug function call)
* @param int    $maxDepth      optional limit of the output size. if value is 0 then will return the last call trace as a string
* @param array  $trace         optional array to use instead of getting result of debug_backtrace (usefull to format exception trace)
* @return array of string or single string if $maxDepth<=0
*/
function formatted_backtrace($formatString=null,$skippedLevel=0,$maxDepth=null,array $trace=null){
	#- create format callback on first call
	static $doReplace;
	if(! isset($doReplace) ){
		$doReplace = create_function(
			'$str,array $replaces',
			'$keys = array_keys($replaces);$vals = array_values($replaces);foreach($keys as $key=>$k)$keys[$key]="%$k";return str_replace($keys,$vals,$str);'
		);
	}
	#- check format string
	if( empty($formatString) ){
		$formatString = "#%id %location: %call(%_args)";
	}
	#- prepare the trace stack we will use

	$trace = null!==$trace?$trace:debug_backtrace();
	$trace = array_slice($trace,$skippedLevel,$maxDepth>0?$maxDepth:count($trace));

	$returnAsString = (null!==$maxDepth && $maxDepth<=0)?true:false;
	if( empty($trace)){
		return $returnAsString?'':array();
	}
	if( null !== $maxDepth){
		$trace = array_slice($trace,0,$returnAsString?1:$maxDepth);
	}
	#- check for required additional treatments
	$withArgs = (strpos($formatString,'%args')!==false?1:0)+(strpos($formatString,'%_args')!==false?2:0);

	foreach($trace as $k=>$v){
		$v['id'] = $k;
		if(!isset($v['type'])){
			$v['call'] = $v['function'];
		}else if( isset($v['object']) ){
			$v['call']   = get_class($v['object']).$v['type'].$v['function'];
			$v['object'] = print_r($v['object'],1);
		}else{
			$v['call']   = $v['class'].$v['type'].$v['function'];
			$v['object'] = '';
		}
		if(empty($v['file']) ){
			$v['file'] = '';
			$v['line'] = '';
			$v['location'] = '[internal function]';
		}else{
			if( constant('ROOT_DIR') ){
				$v['file'] = preg_replace(';^'.constant('ROOT_DIR').'/;','',$v['file']);
			}
			$v['location'] = "$v[file] at line $v[line]";
		}

		switch($withArgs){
			case false:
				unset($v['args']);
				break;
			case 1:
				$v['args'] = print_r($v['args'],1);
				break;
			case 2:
			case 3:
				$v['_args'] = array();
				if( !empty($v['args'])){
					foreach($v['args'] as $ka=>$va){
						switch(gettype($va)){
							case 'array':
								$a = "Array(".count($va)." elements)";break;
							case 'object':
								$a = 'Object(instanceOf '.get_class($va).')';break;
							#- case 'string':
								#- $a = var_export(strlen($va)<=55?$va:substr($va,0,27).'…'.substr($va,-27),1);break;
							default:
								$a = var_export($va,1);
						}
						$v['_args'][] = $a;
					}
				}
				if( $withArgs===2){
					unset($v['args']);
				}else{
					$v['args'] = print_r($v['args'],1);
				}
				$v['_args'] = implode(", ",$v['_args']);
				break;
		}
		$trace[$k] = $doReplace($formatString,$v);
	}
	return $returnAsString?$trace[0]:$trace;
}
