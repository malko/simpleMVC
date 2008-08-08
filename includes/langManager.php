<?php
/**
* utility class to provide multilingual support to simpleMVC
* @package simpleMVC
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @author jonathan gotti <jgotti at jgotti dot org>
* @changelog
*            -2008-08-05 - add property onFailureCheckDfltLang to check for messages in default language when not found in current
*            -2008-05-08 - new method msg() with sprintf support
*/
class langManager{

	static public $localesDirs = array(
		'locales'
	);

	static public $_loadedDictionaries = array();

	/** list of accepted languages codes, case sensitive (lower case) first is default */
	static public $acceptedLanguages = array('fr','en');

	/** keep trace of currently setted language */
	static public $currentLang = false;

	/** set whether messages are checked in default languages when not found instead of just returning the given idMsg*/
	static public $onFailureCheckDfltLang = true;

	/**
	* parametre les languages acceptés, par défaut le premier sera retourné
	* @param array $langs liste des langues accepté avec la langue par défaut en premiere position.
	*/
	static public function setAcceptedLanguages(array $langs){
		self::$acceptedLanguages = array_values($langs);
	}

	/**
	* vérifie si le language donné est considéré comme accepté par l'application.
	* @param string $lang
	* @param bool   $returnCode si true alors retourne le code langue néttoyé en cas de succes
	* return bool|string depend de $returnCode
	*/
	static public function isAcceptedLang($lang,$returnCode=false){
		$code = substr(strtolower($lang),0,2);
		if(! in_array($code,self::$acceptedLanguages))
			return false;
		return $returnCode?$code:true;
	}

	/**
	* parametre la langue actuelle
	* @param string $lang code de la langue, si non accepté laisse la valeur courante ou met celle par défaut.
	* @return string new current language
	*/
	static public function setCurrentLang($lang=null){
		if( is_null($lang) ){
			$lang = ($tmp = self::getCurrentLang())? $tmp : self::getDefaultLang();
			return self::$currentLang = $lang;
		}
		$lang = self::isAcceptedLang($lang,true);
		if( $lang !== false )
			return self::$currentLang = $lang;
		return self::setCurrentLang();
	}

	/**
	* retourne le code de langue par défaut
	* @return string code langue
	*/
	static public function getDefaultLang(){
		return empty(self::$acceptedLanguages[0])?false:self::$acceptedLanguages[0];
	}

	/**
	* retourne le code de langue courant
	* @return string code langue
	*/
	static public function getCurrentLang(){
		return empty(self::$currentLang)?false:self::$currentLang;
	}


	/**
	* detection de la langue demandé par l'utilisateur
	* @param bool $setCurrent si true alors appelle la methode setCurrentLang()
	* @return string lang code
	*/
	static public function langDetect($setCurrent=false){
		if( empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ){
			$lang = self::$acceptedLanguages[0];
		}else{
			$accepted = explode(',',strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
			foreach($accepted as $l){
				$lang = self::isAcceptedLang($l,true);
				if( $lang !== false)
					break;
			}
			if(empty($lang))
				$lang = self::$acceptedLanguages[0];
		}
		return $setCurrent? self::setCurrentLang($lang) : $lang ;
	}

	###--- DICTIONARIES MANAGEMENT ---###
	/**
  * check path for given dicFile regarding the localesDirs setted (last to first)
  * @param str dictFile dictionry filename
  * @return str dictionary path or false
  */
  static public function lookUpDic($dicFile,$dicLang=null){
		$dicLang = self::isAcceptedLang($dicLang===null?self::$currentLang:$dicLang,true);
		return self::_lookUpDic($dicFile,$dicLang);
  }
  /**
  * same as lookUpDic but without langCode clean up
  * used internally to avoid doing twice the same thing
  * @private
  */
  static protected function _lookUpDic($dicFile,$dicLang){
    foreach(array_reverse(self::$localesDirs) as $d){
      if(is_file("$d/$dicLang/$dicFile"))
        return "$d/$dicLang/$dicFile";
    }
    return is_file($dicFile)?$dicFile:false;
	}


	/** charge un fichier de langue en cherchant dans les repertoires self::$localesDirs */
	static public function loadDic($dicName,$dicLang=null,$force=false){
		$dicLang = self::isAcceptedLang($dicLang===null?self::$currentLang:$dicLang,true);
		$dicFile = self::_lookUpDic($dicName,$dicLang);
		#- already loaded nothing to do
		if( isset(self::$_loadedDictionaries[$dicLang][$dicName]) && ! $force ){
			return true;
		}
		$dic = empty($dicFile)?false:parse_conf_file($dicFile,true);

		if(! is_array($dic) )
			return self::$_loadedDictionaries[$dicLang][$dicName] = false;
		return self::$_loadedDictionaries[$dicLang][$dicName] = $dic;
	}

  /**
  * recherche le message dans le dictionnaire choisis et la langue donné et tente de charger les dictionnaires automatiquement.
  * If no messages is found in given dictionnaries and lang the idMsg will be return as result.
  * If onFailureCheckDfltLang is true then will also lookup msg in default language before returning the idMsg.
  * @param str $idMsg     la chaine du message original ou son id tout dépend de votre facon de gérer les fichiers de langues
  * @param str $dicName   nom du ou des dictionnaires dans lesquels faire la recherche du message séparés par des '|'
  *                       par défaut cherchera dans les dictionnaires suivants: controller_action controller et default
  * @param str $langCode  will use current langCode if null
  * @return string
  */
  static public function lookUpMsg($idMsg,$dicName=null,$langCode=null){
  	if( is_null($dicName) ){
			@list($controller,$action) = explode(':',abstractController::getCurrentDispatch(),2);
			$dicName = $controller.'_'.$action."|$controller|default";
		}
		if( is_null($langCode) ){
			$langCode = self::getCurrentLang();
			if( false === $langCode )
				$langCode = self::setCurrentLang();
		}
		$langCodes = explode('|',$langCode);
		$dicNames = explode('|',$dicName);
		foreach($langCodes as $l){
			$l = self::isAcceptedLang($l,true);
			foreach($dicNames as $dn){
				#- autoload dicts as required
				if(! isset(self::$_loadedDictionaries[$l][$dn]) ){
					if(! self::loadDic($dn,$l) )
						continue;
				}
				if( isset(self::$_loadedDictionaries[$l][$dn][$idMsg]) )
					return self::$_loadedDictionaries[$l][$dn][$idMsg];
			}
		}
		#- check default lang on failure if required
		if( self::$onFailureCheckDfltLang && strpos($langCode,$dfltLang=self::getDefaultLang())===false  && $dfltLang!==false )
			return self::lookUpMsg($idMsg,$dicName,$dfltLang);
		return $idMsg;
	}
	/**
	* same as lookUpMsg but with additional sprintf step to permit you to embed variables strings in messages
	* at run time. This method won't use sprintf if no sprintfDatas are given.
  * @param str   $idMsg     la chaine du message original ou son id tout dépend de votre facon de gérer les fichiers de langues
	* @param array $sprintfDatas
  * @param str   $dicName   nom du ou des dictionnaires dans lesquels faire la recherche du message séparés par des '|'
  *                       par défaut cherchera dans les dictionnaires suivants: controller_action controller et default
	* @see lookUpMsg for more info
  * @param str   $langCode  will use current langCode if null
  * @return string
	*/
	static public function msg($idMsg,array $sprintfDatas=null,$dicName=null,$langCode=null){
		if( empty($sprintfDatas) )
			return self::lookUpMsg($idMsg,$dicName,$langCode);
		array_unshift($sprintfDatas,self::lookUpMsg($idMsg,$dicName,$langCode));
		return call_user_func_array('sprintf',$sprintfDatas);
	}

}
