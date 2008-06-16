<?php
/**
* definition des fonctions communes
* @changelog - 2008-05-06 - more understandable show output for trace
*            - 2008-05-01 - add modelAddons lookup to __autoload
*            - 2008-04-12 - new function html_substr and trace option for show
*            - 2008-03-23 - add abstractModels lookup to __autoload
*/

#- definition des chemins communs
define('ROOT_DIR',dirname(dirname(__file__)));
define('LIB_DIR',ROOT_DIR.'/includes');
define('CONF_DIR',dirname(ROOT_DIR).'/config'); #<- you should change this to put it outside the webserver dirs

#- load la conf générale
require dirname(__file__).'/fx-conf.php';
if(file_exists(CONF_DIR.'/config.php'))
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

###--- AUTOLOAD ---###
function __autoload($className){
	$dirs[] = LIB_DIR;
	if( defined('FRONT_NAME') ){
		if( preg_match('!(?:_c|C)ontroller$!',$className,$m) ){ #- look for controller
			$dirs[] = LIB_DIR.'/controllers';
			$dirs[] = ROOT_DIR.'/controllers';
			$className = str_replace($m[0],'',$className);
		}elseif( preg_match('!(?:_v|V)iew((?:_h|H)elper)?$!',$className,$m) ){ #- look for views and helpers
			$dirs[] = LIB_DIR.'/views';
			$dirs[] = ROOT_DIR.'/views';
			if(!empty($m[1])){
				$dirs[] = LIB_DIR.'/views/helpers';
				$dirs[] = ROOT_DIR.'/views/helpers';
			}
			$className = str_replace($m[0],'',$className);
		}elseif( preg_match('!(?:_m|M)odelAddon(?:Interface)?$!',$className,$m) ){#- look for modelAddons and their interface
			$dirs[] = LIB_DIR.'/modelAddons';
			$dirs[] = ROOT_DIR.'/modelAddons';
			$className = str_replace($m[0],'',$className);
		}else{ #- default look for models
			$dirs[] = LIB_DIR.'/models';
			$dirs[] = ROOT_DIR.'/models';
		}
		#- add path corresponding to front controllers
		if( FRONT_NAME !== 'default' ){
			foreach($dirs as $d){
				if( (! preg_match('!^'.LIB_DIR.'!',$d)) && is_dir($tmp = str_replace(ROOT_DIR,ROOT_DIR.'/'.FRONT_NAME,$d)) )
					$dirs[] = $tmp;
			}
		}
	}
	$dirs = array_reverse($dirs);
	foreach($dirs as $dir){
		$cname = $className;
		do{
			$_dbg[] = array("$dir/$cname.php","$dir/class-".strtolower($cname).'.php');
			if( is_file($classFile = "$dir/$cname.php") || is_file($classFile = "$dir/class-".strtolower($cname).'.php') ){
				require $classFile;
				return true;
			}
			$split = preg_split('!(?<=[a-z])(?=[A-Z])|_!',$cname,2);
			if(! isset($split[1]) )
				break;
			list($_dir,$cname) = $split;
			$dir .= "/$_dir";
		}while($cname);
	}
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
		return implode('/',array_reverse(explode('-',$date)));
	list($date,$time) = explode(' ',$date);
	return dateus2fr($date).($noTime?'':' '.$time);
}
function datefr2us($date,$noTime=false){
	if( empty($date) )
		return '0000-00-00';
	if(! strpos($date,' '))
		return implode('-',array_reverse(explode('/',$date)));
	list($date,$time) = explode(' ',$date);
	return dateus2fr($date).($noTime?'':' '.$time);
}

function html_substr($htmlStr,$start=0,$length=null,$appendStr='...'){
	if(strlen($htmlStr) < $length)
		return $htmlStr;
	$blockTags = '!t[rdhfba](?:ble|oot|ead|ody)?|div|[bh][r1-6]|form|h!i'; #- tags where space are not counting
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
					if( $tagStack[0]===$tag ){
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
		if(preg_match('!^\s+!',$htmlStr,$m)){
			$htmlStr = ltrim($htmlStr);
			if($pos>$start) $outStr.=$m[0];
			#- @todo manage space that are not to be count
			continue;
		}
		#- then consider all as single char
		$outStr .= $htmlStr[0];
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
			$res[] = @$m[$v];
		return $res;
	}elseif(! in_array(@$m[$id],array(FALSE,null),1) ){
		return $m[$id];
	}
	return FALSE;
}
###--- DEBUG HELPER ---###
function show(){
  static $jsDone, $nb;
  if( isset($jsDone) ){
    ++$nb;
  }else{
    $jsDone = true;
    $nb = 1;
    echo "
    <script type=\"text/javascript\"><!--//
      function toggleDbg(el){
        if(el.style.display==='none'){
          el.style.display = 'block';
        }else{
          el.style.display = 'none';
        }
      }
      //-->
    </script>
    <style type=\"text/css\">
      div.dbg strong  { font-size:12px;text-decoration:underline;cursor:pointer;margin-bottom:0; }
      div.dbg pre { background:#F0F0F0;border-style:dashed;border-width:1px;max-height:350px;overflow:auto;margin-top:0; }
    </style>
    ";
  }
  $args  = func_get_args();
  $argc  = func_num_args();
  $param = $args[$argc-1];
  $halt  = false;

	if($param!=='trace'){
		$getTrace = (is_string($param) && substr_count($param,'trace'))?array():false;
	}else{
		$getTrace = array();
		array_pop($args);
	}

  if($argc>1 && $param === 'exit'){
    $halt = true;
    array_pop($args);
  }
  if( is_string($param) && preg_match('!^color:([^;]+)!',$param,$m)){
    array_pop($args);
    $color = $m[1];
    if(substr_count($param,'exit')){
      $halt = true;
    }
  }else{
    $color = 'red';
  }

	$trace    = debug_backtrace();
	if(false!==$getTrace){
		$_trace = $trace;
		array_shift($_trace);
		foreach($_trace as $k=>$v){
			if(isset($v['object']))
				$v['object'] = get_class($v['object']);
			$traceArgs = array();
			foreach($v['args'] as $ka=>$va){
				if(is_object($va))
					$a = 'instanceof '.get_class($va);
				elseif(is_array($va))
					$a = "Array(".count($va)." elements)";
				else
					$a = var_export($va,1);
				$traceArgs[] = $a;
			}
			$traceArgs = count($traceArgs)?"\n\t".implode(",\n\t",$traceArgs)."\n":'';
			$traceFile = empty($v['file'])?'':'in '.str_replace(ROOT_DIR.'/','',$v['file'])." at line $v[line]\n";
			@$getTrace[$k] = $traceFile.(($v['object']||$v['class'])?$v[$v['object']?'object':'class'].$v['type']:'')."$v[function]($traceArgs);";
		}
		$args[]="↓↓↓↓-------------------------- FOLLOWING IS BACKTRACE LAST CALL FIRST --------------------------↓↓↓↓";
		$args[]=implode(str_repeat('__',49)."\n\n",$getTrace);
	}

  $str = array();
  foreach($args as $arg){
    $str[] = print_r($arg,1);
  }
  $str = implode("\n".str_repeat('--',50)."\n",$str);
  $preStyle = 'style="color:'.$color.';border-color:'.$color.';"';
  $bStyle   = 'style="color:'.$color.';"';
	$trace = str_replace(ROOT_DIR.'/','',$trace[0]['file'])
		.(empty($trace[1]['function'])? '' : ' in '.(isset($trace[1]['class'])?$trace[1]['class'].$trace[1]['type']:'').$trace[1]['function'].'()')
		.':'.$trace[0]['line'];
  echo "<div class=dbg><strong $bStyle onclick=\"toggleDbg(document.getElementById('dbg$nb'));\">Show ( $trace )</strong><br /><pre $preStyle id='dbg$nb'><xmp>$str</xmp></pre></div>";
  if($halt){
    echo "<h5 style=color:red;text-align:center;>SCRIPT EXITED BY SHOW</h5>";
    exit();
  }
  return false; # just for convenience
}
