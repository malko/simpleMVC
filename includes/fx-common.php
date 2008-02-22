<?php
/**
* definition des fonctions communes 
*/

#- definition des chemins communs
define('ROOT_DIR',dirname(dirname(__file__)));
define('LIB_DIR',ROOT_DIR.'/includes');
define('CONF_DIR',ROOT_DIR.'/config');

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
    # look for controller
    if( preg_match('!(?:_c|C)ontroller$!',$className,$m) ){ 
      $dirs[] = LIB_DIR.'/controllers';
      $dirs[] = ROOT_DIR.'/controllers';
      $className = str_replace($m[0],'',$className);
    }
    # look for views and helpers
    if( preg_match('!(?:_v|V)iew((?:_h|H)elper)?$!',$className,$m) ){
      $dirs[] = LIB_DIR.'/views';
      $dirs[] = ROOT_DIR.'/views';
      if(!empty($m[1])){
        $dirs[] = LIB_DIR.'/views/helpers';
        $dirs[] = ROOT_DIR.'/views/helpers';
      }
      $className = str_replace($m[0],'',$className);
    }
    if( FRONT_NAME !== 'default' ){ # add path corresponding to front controllers
      foreach($dirs as $d){
        if( is_dir($tmp = str_replace(ROOT_DIR,ROOT_DIR.'/'.FRONT_NAME,$d)) )
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
  show($_dbg);
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
      div.dbg pre { background:#F0F0F0;border-style:dashed;border-width:1px;max-height:250px;overflow:auto;margin-top:0; }
    </style>
    ";
  }
  $args  = func_get_args();
  $argc  = func_num_args();
  $param = $args[$argc-1];
  $halt  = false;
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
  $str = array();
  foreach($args as $arg){
    $str[] = print_r($arg,1);
  }
  $str = implode("\n".str_repeat('--',50)."\n",$str);
  $preStyle = 'style="color:'.$color.';border-color:'.$color.';"';
  $bStyle   = 'style="color:'.$color.';"';
  $trace    = debug_backtrace();
  $trace = str_replace(ROOT_DIR.'/','',$trace[0]['file']).':'.$trace[0]['line'];
  echo "<div class=dbg><strong $bStyle onclick=\"toggleDbg(document.getElementById('dbg$nb'));\">Show ( $trace )</strong><br /><pre $preStyle id='dbg$nb'><xmp>$str</xmp></pre></div>";
  if($halt){
    echo "<h5 style=color:red;text-align:center;>SCRIPT EXITED BY SHOW</h5>";
    exit();
  }
  return false; # just for convenience
}
