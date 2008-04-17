<?php
/**
* helper pour l'ecriture d'url interne au site.
* Par défaut reconstitue les urls en se basant sur les rewriteRules.
* si vous ne pouvez pas vous servir des rewrites rules vous devez au choix
* ajouter une option dans le fichier de config USE_REWRITE_RULES = false
* ou parametrer dans le bootsrap ou l'action controller comme suit:
* url_viewHelper::useRewriteRules = false;
* ou url_viewHelper::setUseRewriteRules(false);
* 
* @package simpleMVC
* @since 2008-01-09
* @changelog - 2008-04-15 - add new param $appUrl to permit link creation for external apps
*            - 2008-02-01 - now suppress empty params from url
*/
class url_viewHelper implements viewHelperInterface{
	static public $useRewriteRules = null;
	
	public $view = null;
	
  public function __construct(viewInterface $view){
    $this->view = $view;
    if( self::$useRewriteRules===null ){
    	if( defined('USE_REWRITE_RULES') )
				self::$useRewriteRules = (bool) USE_REWRITE_RULES;
			else
				self::$useRewriteRules = true;
		}
  }
  
  public function getController(){
    return $this->view->getController();
  }
  
  static public function setUseRewriteRules($useRewriteRules){
		self::$useRewriteRules = (bool) $useRewriteRules;
	}
  
	/**
	* prepare une url interne au site
	* @param string $action     nom de l'action cible du lien
	* @param string $controller nom du controller cible du lien 
	*                           si null le controller en cours sera utilisé
	* @param mixed  $params     peut etre soit un tableau ou une chaine:
	*                           - tableau associatif de paires clés valeurs à passer dans l'url
	*                           - query string à ajouter à la fin de l'url.
	*                           si c'est un tableau la chaine généré sera automatiquement
	*                           urlencodé (seulement les valeurs pas les cles)
	*                           si c'est une chaine c'est à vous de le faire.
	*                           (les chaines doivent etre des querystring standard
	*                            l'appli la remettra en forme si utilisation des rewriteRules)
	* @param bool   $alreadyEncoded ne sert que quand $params est un tableau afin 
	*                           d'eviter l'urlencodage automatique.
	* @param string $appUrl     sert d'url de base à la place de APP_URL (permet de formater de liens pour d'autres applications)
	* @return str url cible.
	*/
	function url($action,$controller=null,$params=null,$alreadyEncoded=false,$appUrl=null){
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
						@list($k,$v) = explode('=',$p,2);
						$params[$k] = $v;
					}
					$alreadyEncoded = true; #- les chaines doivent déjà etre encodés!
				}
			}
			if(is_array($params)){
				$Qstr = array();
				if(! $alreadyEncoded )
					$params = array_map('urlencode',$params);
				$kv_sep = (self::$useRewriteRules)?'/':'=';
				foreach($params as $k=>$v){
					if($k==='ctrl' || $k==='action' || strlen($v)===0)
						continue;
					$Qstr[] = $k.$kv_sep.$v;
				}
				$Qstr = implode((self::$useRewriteRules?'/':'&amp;'),$Qstr);
			}
		}
		if( null === $appUrl)
			$appUrl = APP_URL;
		if(self::$useRewriteRules){
			$url = "$appUrl/$controller/$action".(empty($Qstr)?'':"/$Qstr");
		}else{
			$sep = isset($sep)?$sep:'&amp;';
			$url = "$appUrl/index.php?ctrl=$controller$sep"."action=$action".(empty($Qstr)?'':"$sep$Qstr");
		}
		return $url;
	}
}
