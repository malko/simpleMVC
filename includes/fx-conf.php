<?php
/**
* @author Jonathan Gotti <nathan at the-ring dot homelinux dot net>
* @copyleft (l) 2003-2004  Jonathan Gotti
* @package config
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2008-10-10 - parse conf_file now ignore already defined CONSTANTS when $out is false
*            - 2008-02-25 - bug correction when values contain multiple % characters (like urlencoded values)
*                         - '%%' will be replaced by '%' in values
*            - 2007-04-30 - bug correction regarding single quote escaped in values string
*                         - bug correction when saving some multiple multiline values containing '=' at certain position on a line (not the first).
*            - 2007-04-26 - replace double quote strings with single quote string at eval time to avoid replacement of escaped values (ie: \[rnt....])
*            - 2007-03-27 - change regexp in parse_conf_file() to better support multilines values (line ended by \)
*            - 2005-09-30 - remove GUI_TYPE==GTK suppport
*                         - optimize all the code for better performance (parsing) and better multiline support (writing)
*            - 2005-06-11 - now write_conf_file() can unset or comment some vars using --COMMENT--,--UNSET-- in place of the value
*/

/**
* read a config file and define CONSTANT in it or return it as an array
* @param string $file_path
* @param bool $out default is FALSE mean define the value, return an array if set to TRUE
* @return array | bool depend on $out
*/
function parse_conf_file($file_path,$out = false){
	if(! file_exists($file_path))
		return FALSE;
	# get file content
	if(! is_array($conf = file($file_path))){
		return False;
	}

	$_search = array("/(?<!%)(%(?=[a-z_])([a-z0-9_]+)%)/ie",'!%%!',"!\\\\\s*\r?\n!");
	$_replce = array("isset(\$out_['\\2'])?\$out_['\\2']:(defined('\\2')?\\2:'\\0');",'%',"\n");

	# parse conf file
	$preserve = FALSE;
	foreach($conf as $line){
		if(preg_match("!^\s*#+!",$line))
			continue;
		if($preserve && preg_match("!^\s*(.*?)(\\\\?)\s*$!",$line,$match)){ # continue line
			$value .= "\n$match[1]";
			$preserve = ($match[2]!=='\\'?FALSE:TRUE);
		}elseif(preg_match("!^\s*([^#=]+)=(.*?)(\\\\?)\s*$!",$line,$match)){ # read line
			$var  = trim($match[1]);
			$value= $match[2];
			$preserve = ($match[3]!=='\\'?FALSE:TRUE);
		}else{
			continue; # considered as commentary
		}

		if($preserve) continue;
		$value = preg_replace($_search,$_replce,trim($value));

		if(! in_array(strtoupper($value),array('NULL','FALSE','TRUE')) )
			$value = "'".($out?preg_replace('!(\\\\|\')!','\\\\\1',$value):$value)."'";

		$var = trim($var);
		if(! $out){
			if( ! defined($var) )
				eval("define('$var',$value);");
		}else{
			eval('$out_[$var]='.$value.';');
		}
	}

	return $out?(empty($out)?array():$out_):TRUE;
}

/**
* prend un tableau associatif et ajoute les entrée dans un fichier de configuration
* en conservant les commentaires ainsi que les valeurs non renseigné
* @param string $file file to write configuration
* @param array $config the configuration to add to config file
* @param bool $force if true create the file if doesn't exist
* @return bool
* @changelog 2005-06-11 now can unset or comment some vars using --COMMENT--,--UNSET-- in place of the value
*/
function write_conf_file($file,$config,$force=FALSE){
	if(! is_array($config))
		return FALSE;
	$fileExist = file_exists($file);
	# check if file exist or not
	if( !( $fileExist || $force ) )
		return FALSE;
	$oldconf = $fileExist?file($file):null;
	# get the old config
	if( is_null($oldconf) && ! $force )
		return FALSE;
	# first rewrite old conf
	if(is_array($oldconf)){
		$follow = FALSE;
		foreach($oldconf as $linenb => $line){
			if( preg_match("!^\s*#!",$line)){# keep comment lines
				$newconf[$linenb]=$line;
			}elseif( (!$follow) && preg_match("!^\s*([^#=]+)=([^#\\\\]+)(\\\\?)!",$line,$match)){ # first line of config var
				$var = trim($match[1]); # get varname

				if(! isset($config[$var])) # not set so keep the line as is
					$newconf[$linenb] = $line;
				else # we have a new value we write it
					$newconf[$linenb] = _write_conf_line($var,$config[$var],$line);

				if(preg_match('!\\\\\s*\n$!',$line))
					$follow = TRUE;
			}elseif($follow){ # multiline values
				if(!isset($config[$var])){ # keep old multiline values
					$newconf[$linenb] = $line;
				}elseif(trim($config[$var])==='--COMMENT--' ){ # comment all multilines values
					$newconf[$linenb] = "#~ $line";
				}
				if(! preg_match('!\\\\\s*\n$!',$line))
					$follow = FALSE;
			}
			if( (! $follow) && @isset($config[$var]) )
				unset($config[$var]);

		}
		if(count($config)>0){ # write new config vars at the end
			foreach($config as $var=>$value)
				$newconf[] = _write_conf_line($var,$value);
		}
	}elseif($force){
		foreach($config as $var=>$value)
			$newconf[] = _write_conf_line($var,$value);
	}
	return array2file(@$newconf,$file);
}

/**
* take an array and write each value as a line
* @param array $arr array containings data to write
* @param string $file file to write in it
* @return bool
*/
function array2file($arr,$file){
	if(! is_array($arr) ) return FALSE;
	if(! $f = fopen($file,'w'))
		return FALSE;
	fputs($f,implode('',$arr));
	return fclose($f);
}

/**
* used by write_conf_file to prepare line for passed values
*/
function _write_conf_line($var,$value=null,$oldline=null){
	$commented = (substr_count($value,'--COMMENT--')?TRUE:FALSE);
	$value = $value?preg_replace("!([^\\\\])\r?\n!",($commented?"\\1\\\\\n# ":"\\1\\\\\n"),$value):$value;
	if($commented)
		$line = (($oldline && trim($value)==='--COMMENT--')?"#~ $oldline":"# $var = ".str_replace('--COMMENT--','',$value)."\n");
	elseif($value === '--UNSET--')
		$line = '';
	else
		$line = "$var = ".$value."\n";
	return $line;
}
