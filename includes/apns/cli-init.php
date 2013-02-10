<?php
/**
* Common settings/init for our apns command lines scripts
*/

#- first check we are in comand line or quit
if( PHP_SAPI !== 'cli'){
	echo "THIS PROGRAM IS RESERVED FOR COMMAND LINE USAGE";
	exit;
}

#- then if $uniqueProcessName is define check that we really have a unique process for this script
$processFileName = basename($_SERVER['PHP_SELF']);
if( ! empty($uniqueProcess) ){
	$ps = "ps ax | grep -v \"egrep\" | egrep \"$processFileName\\s\"";
	$ps = `$ps`;
	$pids = match('/^\s*(\d+)/m',$ps,1,true);
	if( count($pids)> 1){
		echo "$processFileName already running.\n";
		exit(); // avoid multiple instances
	}
}
date_default_timezone_set('Europe/Paris');
error_reporting( E_ALL );
#- ini_set ("error_log", "/tmp/".$processFileName.'.error.log');
ini_set ("error_log", '/tmp/phpapns.error.log');
//ini_set ("ignore_repeated_errors",true);
ini_set ("display_error",true);
ini_set ("report_memleaks",true);
chdir(dirname(__file__));

/**
command line application intended to be used as crontab for push notification system
*/
define('LIB_DIR',dirname(dirname(__file__)));
define('ROOT_DIR',dirname(LIB_DIR));
define('CONF_DIR',ROOT_DIR.'/config');

require LIB_DIR.'/fx-conf.php';
require LIB_DIR.'/smvc/Autoloader.php';

if(file_exists(ROOT_DIR.'/config/config.php')) #- general config
	parse_conf_file(ROOT_DIR.'/config/config.php');

smvcAutoloader::$basicInit=true;
smvcAutoloader::init(array(
	'db'=>LIB_DIR.'/db/class-db.php'
	,'mysqldb'=>LIB_DIR.'/db/adapters/class-mysqldb.php'
	,'mysqlidb'=>LIB_DIR.'/db/adapters/class-mysqlidb.php'
	,'sqlitedb'=>LIB_DIR.'/db/adapters/class-sqlitedb.php'
	,'sqlite3db'=>LIB_DIR.'/db/adapters/class-sqlite3db.php'
	,'jsonQueryClause'=>LIB_DIR.'/db/class-jsonQueryClause.php'
	,'console_app'=>LIB_DIR.'/db/scripts/libs/class-console_app.php'
));

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

function execBackground($cmd){
	$cmd .= ' > /dev/null 2>&1 &';
	echo "EXEC COMMAND $cmd\n";
	`$cmd`;
	echo "$cmd launched\n";
}

function logMsg($msg){
	static $myPID,$processFileName;
	if(! $myPID ){
		$myPID= getmypid();
		$processFileName = match("!(^[^\.]+)!",$GLOBALS['processFileName']);
	}
	$args = null;
	if( func_num_args() > 1){
		$args = func_get_args();
		array_shift($args);
	}

	$trace = formatted_backtrace(" [%location: %call(%_args)]",2,0);

	error_log("[$myPID $processFileName$trace] $msg".($args?"\n".@json_encode($args):''),E_USER_WARNING);
}


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
			$v['file'] = basename($v['file']);
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
								$a = get_class($va);break;
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
