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
	* @param string $action     name of the target mvc action
	*                           (may also be a full dispatch string like 'controllerName:actionName' in wich case $controller parameter will be ignored)
	* @param string $controller name of the target controller if null then the current controller setted in attached view will be used.
	*                           @note will be ignored if $action parameter is a full dispatch string ('controllerName:actionName')
	* @param mixed  $params     can be a string or an array :
	*                           - sting: is used as a queryString to add at the end of generated URL. string must be already urlencoded
	*                             this must be a "standard" URL queryString it will be correctly rewrited if rewriteRules are in use.
	*                           - array: is list of key=>values to put at the end of generated URL (will be transformed to queryString)
	*                             values will be urlencoded and if a "special" empty string key ('') is given the value will be passed to wordCleaner() and then added to the very end of generated URL without any key
	* @param bool $alreadyEncoded only make sense with $params as an array to avoid automatic urlEncoding of values
	* @param string $appUrl     allow you to set the base url to use in place of default APP_URL it's usefull to prepare links for other apps
	* @return string target URL.
	*/
	function url($action,$controller=null,$params=null,$alreadyEncoded=false,$appUrl=null){
		if( strpos($action,':')!==false) # manage $action as a full dispatch string
			list($controller,$action)=explode(':',$action,2);
		# gestion du controller
		if( is_null($controller) )
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
					$params[''] = $this->wordCleaner($params['']);
				if(! $alreadyEncoded )
					$params = array_map('urlencode',$params);

				$kv_sep = (self::$useRewriteRules)?'/':'=';
				$ignoredKey = array('ctrl','action','');
				foreach($params as $k=>$v){
					if( in_array($k,$ignoredKey,true) || (empty($v) && ! self::$keepEmptyVars) )
						continue;
					$Qstr[] = $k.$kv_sep.$v;
				}
				if( isset($params['']))
					$Qstr[] = $this->wordCleaner($params['']);

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
	function wordCleaner($word){
		$exp = '![^a-zA-Z0-9'.(empty(self::$wordCleanerSkippedChars)?'':self::$wordCleanerSkippedChars).']+!';
		return preg_replace(array($exp,'!(^_+|_+$)!'),array('_',''),removeMoreAccents(html_entity_decode($word)));
	}
}
