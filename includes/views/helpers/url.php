<?php
/**
* helper pour l'ecriture d'url interne au site.
* Par défaut reconstitue les urls en se basant sur les rewriteRules.
* si vous ne pouvez pas vous servir des rewrites rules vous devez au choix
* ajouter une option dans le fichier de config USE_REWRITE_RULES = false
* ou parametrer dans le bootsrap ou l'action controller comme suit:
* url_viewHelper::useRewriteRules = false;
* ou url_viewHelper::setUseRewriteRules(false);
* @class url_viewHelper
* @package simpleMVC
* @since 2008-01-09
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2011-08-09 - add methods key and nokey as shorthand for url methods with last parameters forced
*            - 2011-02-08 - add $rewriteUseKeys property and $forcedRewriteUseKeys parameter
*            - 2010-04-09 - make wordCleaner a static method
*            - 2010-02-08 - drop support for second $controllerName parameter now only support dipatchString as first parameter
*            - 2009-06-23 - wordCleaner() add html_entity_decode charset parameter and remove utf8_decode
*            - 2009-06-12 - wordCleaner() add utf8_decode after html_entity_decode and now keep traces of already cleaned words (more memory but better performance)
*            - 2009-06-04 - new static property $wordCleanerSkippedChars to allow chars to pass over wordCleaner
*            - 2009-04-28 - now url() $action parameter can be a full dispatch string (like controllerName:actionName)
*            - 2009-04-22 - new static property $keepEmptyVars (false as default)
*                         - new special empty string key in $params url parmeter will be passed to wordCleaner and added at the end of generated url
*            - 2009-04-03 - add method wordCleaner
*            - 2008-11-20 - add new static property self::$argSeparator
*            - 2008-04-15 - add new param $appUrl to permit link creation for external apps
*            - 2008-02-01 - now suppress empty params from url
*/
class url_viewHelper extends abstractViewHelper{
	static public $useRewriteRules = null;
	/**
	* which string to use as queryString argumentSeparator when not using RewriteRules.
	* if null at __construct time will be set to php.ini value of arg_separator.output value
	*/
	static public $argSeparator = null;
	/** if true then empty vars are removed from generated url */
	static public $keepEmptyVars = false;
	static public $wordCleanerSkippedChars = '-/';//'\\+';
	static public $rewriteUseKeys = true;
	public $view = null;

	public function __construct(viewInterface $view){
		parent::__construct($view);
		if( self::$useRewriteRules===null ){
			if( defined('USE_REWRITE_RULES') )
				self::$useRewriteRules = (bool) USE_REWRITE_RULES;
			else
				self::$useRewriteRules = true;
		}
		if( self::$argSeparator === null)
			self::$argSeparator = ini_get('arg_separator.output');
	}

	static public function setUseRewriteRules($useRewriteRules){
		self::$useRewriteRules = (bool) $useRewriteRules;
	}

	/**
	* compile an internal application url
	* @param string $dispatchString dispatch string like 'controllerName:actionName','controllerName:','action'
	* @param mixed  $params     can be a string or an array :
	*                           - sting: is used as a queryString to add at the end of generated URL. string must be already urlencoded
	*                             this must be a "standard" URL queryString it will be correctly rewrited if rewriteRules are in use.
	*                           - array: is list of key=>values to put at the end of generated URL (will be transformed to queryString)
	*                             values will be urlencoded and if a "special" empty string key ('') is given the value will be passed to wordCleaner() and then added to the very end of generated URL without any key
	* @param bool $alreadyEncoded only make sense with $params as an array to avoid automatic urlEncoding of values
	* @param string $appUrl     allow you to set the base url to use in place of default APP_URL it's usefull to prepare links for other apps
	* @return string target URL.
	*/
	function url($dispatchString,$params=null,$alreadyEncoded=false,$appUrl=null,$forcedRewriteUseKeys=null){
		if( strpos($dispatchString,':')!==false){ # manage $action as a full dispatch string
			list($controller,$action)=explode(':',$dispatchString,2);
		}else{
			$controller = null;
			$action = $dispatchString;
		}
		# gestion du controller
		if( empty($controller) )
			$controller = $this->getController()->getName();
		# preparation de la queryString
		if(! empty($params) ){
			if( is_string($params) ){
				$params = preg_replace('!^(&(amp;)?|\?)!','',$params);
				if(! self::$useRewriteRules ){
					$Qstr = $params;
				}else{
					$sep = strpos($params,'&amp;')!==false?'&amp;':'&';
					$_params = explode($sep,$params);
					$params = array();
					foreach($_params as $p){
						list($k,$v) = array_pad(explode('=',$p,2),2,null);
						$params[$k] = $v;
					}
					$alreadyEncoded = true; #- les chaines doivent déjà etre encodés!
				}
			}
			if(is_array($params)){
				$Qstr = array();
				if( isset($params['']))
					$params[''] = self::wordCleaner($params['']);
				if(! $alreadyEncoded )
					$params = array_map('urlencode',$params);

				$kv_sep = (self::$useRewriteRules)?'/':'=';
				$useKeys = self::$useRewriteRules?($forcedRewriteUseKeys!==null?$forcedRewriteUseKeys:self::$rewriteUseKeys):true;
				$ignoredKey = array('ctrl','action','');
				foreach($params as $k=>$v){
					if( in_array($k,$ignoredKey,true) || (empty($v) && ! self::$keepEmptyVars) )
						continue;
					$Qstr[] = ($useKeys?"$k$kv_sep":'').$v;
				}
				if( isset($params['']))
					$Qstr[] = self::wordCleaner($params['']);

				$Qstr = implode((self::$useRewriteRules?'/':self::$argSeparator),$Qstr);
			}
		}
		if( null === $appUrl)
			$appUrl = APP_URL;
		if(self::$useRewriteRules){
			$url = "$appUrl/$controller/$action".(empty($Qstr)?'':"/$Qstr");
		}else{
			$sep = self::$argSeparator;
			$url = "$appUrl/index.php?ctrl=$controller$sep"."action=$action".(empty($Qstr)?'':"$sep$Qstr");
		}
		return $url;
	}

	function nokey($dispatchString,$params=null,$alreadyEncoded=false,$appUrl=null){
		return $this->url($dispatchString,$params,$alreadyEncoded,$appUrl,false);
	}
	function key($dispatchString,$params=null,$alreadyEncoded=false,$appUrl=null){
		return $this->url($dispatchString,$params,$alreadyEncoded,$appUrl,true);
	}

	static function wordCleaner($word){
		static $words=array(),$charset;
		if( isset($words[$word]))
			return $words[$word];
		if(! isset($charset)){
			$charset = ini_get('default_charset');
		}
		$exp = '![^a-zA-Z0-9'.(empty(self::$wordCleanerSkippedChars)?'':self::$wordCleanerSkippedChars).']+!';
		return $words[$word] = preg_replace(array($exp,'!(^_+|_+$)!'),array('_',''),removeMoreAccents(html_entity_decode($word,ENT_COMPAT,$charset)));
	}
}
