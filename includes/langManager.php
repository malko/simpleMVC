<?php
/**
* utility class to provide multilingual support to simpleMVC
* @package simpleMVC
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @author jonathan gotti <jgotti at jgotti dot org>
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            -2011-01-30 - add LANGMANAGER_SHORTMSG global shortcut
*                        - add some methods to assist in dictionnaries management
*            -2011-01-14 - add $onFailureHighlight, $onFailureFeedDictionnaries, and $higlightFormat static properties to ease finding untranslated strings
*            -2011-01-10 - add support for plural forms using \\n+= after the single form value
*            -2008-08-05 - add property onFailureCheckDfltLang to check for messages in default language when not found in current
*            -2008-05-08 - new method msg() with sprintf support
*/

/** Not so nice coded trick but will be handy to allow a global shortcut name for the langManager::msg() method defined by user*/
if( defined('LANGMANAGER_SHORTMSG') && ! function_exists(LANGMANAGER_SHORTMSG) ){
	eval('function '.constant('LANGMANAGER_SHORTMSG').'(){$args=func_get_args();return call_user_func_array(array("langManager","msg"),$args);}');
}

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

	static public $onFailureHighlight = false;
	#- static public $onFailureFeedDictionnaries = true;
	static public $highlightFormat = '<strong class="langManagerNotFound">[ %s ]</strong>';

	static public $onFailureCb = null;

	/**
	* parametre les languages acceptés
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
			return self::$_loadedDictionaries[$dicLang][$dicName];
		}
		$dic = empty($dicFile)?false:parse_conf_file($dicFile,true);

		if(! is_array($dic) )
			return self::$_loadedDictionaries[$dicLang][$dicName] = false;
		foreach($dic as &$v){
			if( preg_match('/(.+)(?:\r?\n|\r)\s*\+=(.*)/s',$v,$m) ){
				$v = array($m[1],$m[2]);
			}
		}
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
				$currentDispatch = abstractController::getCurrentDispatch(true);
				if( empty($currentDispatch)){
					$dicName='default';
				}else{
					list($controller,$action) = $currentDispatch;
					$dicName = $controller.'_'.$action."|$controller|default";
				}
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
					if(false===self::loadDic($dn,$l) )
						continue;
				}
				if( isset(self::$_loadedDictionaries[$l][$dn][$idMsg]) )
					return self::$_loadedDictionaries[$l][$dn][$idMsg];
			}
		}
		#- check default lang on failure if required (highlight has to be turn off)
		if((! self::$onFailureHighlight) && self::$onFailureCheckDfltLang && strpos($langCode,$dfltLang=self::getDefaultLang())===false  && $dfltLang!==false )
			return self::lookUpMsg($idMsg,$dicName,$dfltLang);

		$out = $idMsg;
		#- check if highlight is required
		if( self::$onFailureHighlight ){
			$out = sprintf(self::$highlightFormat,$idMsg);
		}
		/*if( self::$onFailureFeedDictionnaries ){
			$umask = umask(0);
			write_conf_file($dicFile = (end(self::$localesDirs)."/$langCode/".strtok($dicName,'|')),array(
				'##--UNTRANSLATED '.formatted_backtrace("%location",2,0)."\n$idMsg"=>$out
			),true);
			chmod($dicFile,0666);
			umask($umask);
		}*/
		if( self::$onFailureCb !== null){
			$res = call_user_func(self::$onFailureCb,$idMsg);
			if( is_string($res) )
				return $res;
		}
		return $out;
	}
	/**
	* same as lookUpMsg but with additional sprintf step to permit you to embed variables strings in messages
	* at run time. This method won't use sprintf if no sprintfDatas are given.
	* @param str   $idMsg     la chaine du message original ou son id tout dépend de votre facon de gérer les fichiers de langues
	* @param array $sprintfDatas (single values may be passed directly they'll be automaticly cast as array)
	* @param str   $dicName   nom du ou des dictionnaires dans lesquels faire la recherche du message séparés par des '|'
	*                       par défaut cherchera dans les dictionnaires suivants: controller_action controller et default
	* @see lookUpMsg for more info
	* @param str   $langCode  will use current langCode if null
	* @return string
	*/
	static public function msg($idMsg,$sprintfDatas=null,$dicName=null,$langCode=null){
		if( empty($sprintfDatas) ){
			$msg = self::lookUpMsg($idMsg,$dicName,$langCode);
			return is_array($msg)?$msg[0]:$msg;
		}elseif(! is_array($sprintfDatas)){
			$sprintfDatas = array($sprintfDatas);
		}
		array_unshift($sprintfDatas,self::lookUpMsg($idMsg,$dicName,$langCode));
		if( is_array($sprintfDatas[0])){
			$plural=0;
			for($i=1;$i<count($sprintfDatas);$i++){
				if( is_numeric($sprintfDatas[$i]) ){
					if( $sprintfDatas[$i] > 1)
						$plural = 1;
					break;
				}
			}
			$sprintfDatas[0] = $sprintfDatas[0][$plural];
		}
		return call_user_func_array('sprintf',$sprintfDatas);
	}


	/*--- HELPERS METHODS TO EASE DICTIONARIES MANAGEMENT ---*/

	/**
	* Multi purpose method that register/unregister itself as a $onFailureCb if the value passed as $idMsg is boolean value
	* to get the collected idMsgs just pass no parameter to the method call it will return and remove the collected idMsgs
	* finally the method will handle the callback and collect all failed idMsgs (unless you register another callback)
	* @param mixed $idMsg true  -> start collecting
	*                     false -> end collecting
	*                     null -> return collected idMsgs and remove them from buffer
	*                     other values will be collected even if not setted as a callback.
	* @return mixed dependign on $idMsg value
	*/
	static function collectFailures($idMsg=null){
		static $failures = array();
		//-- bool set/unset method as a failure callback
		if( is_bool($idMsg) ){
			$callable = array(__class__,__method__);
			if( $idMsg ){
				self::$onFailureCb = $callable;
			}elseif(self::$onFailureCb === $callable){
				self::$onFailureCb = null;
			}
			return self::$onFailureCb;
		}
		// null return the collected failures
		if( null===$idMsg){
			$res = $failures;
			$failures = array();
			return $res;
		}
		// normal call collecting idMsgs
		$failures[] = $idMsg;
}

	/**
	* return form inputs for the given dictionnary
	* @param string $dicname
	* @param array $additionalmsgIds list of additional msg ids you want to be present in the form
	* @return string
	*/
	static function makeDicFormInputs($dicname,array $additionalmsgIds=null){
		static $i=0;
		$dicname = strtok($dicname,'|');
		#- get all msg ids
		$ids = null===$additionalmsgIds?array():$additionalmsgIds;
		foreach( self::$acceptedLanguages as $l){
			#- load dictionary and check msgIds
			if(! isset(self::$_loadedDictionaries[$l][$dicname])){
				self::loadDic($dicname,$l);
			}
			if( is_array(langManager::$_loadedDictionaries[$l][$dicname]) ){
				$ids = array_merge($ids,array_keys(langManager::$_loadedDictionaries[$l][$dicname]));
			}
			#- show(langManager::$_loadedDictionaries[$l][$dicname],'color:green');
		}
		$ids = array_unique($ids);
		#- now that we have all ids we can manage the form
		foreach( self::$acceptedLanguages as $l){
			$langFields[] = '<fieldset><legend class="'.$l.'">'.$l.' / '.$dicname.'</legend>';
			foreach($ids as $id){
				$inputId = 'langManagerFormInput_'.(++$i);
				$inputName = "[$l][$dicname][$i]";
				if( isset(self::$_loadedDictionaries[$l][$dicname][$id]) )
					$value = self::$_loadedDictionaries[$l][$dicname][$id];
				else
					$value = '';
				$langFields[] = '<div class="langManagerFormInput">'
					.'<input type="hidden" name="idmsgs'.$inputName.'" value="'.htmlentities($id,ENT_COMPAT,'UTF-8').'" />'
					.'<label for="'.$inputId.'">'.$id.'</label>'
					.'<textarea id="'.$inputId.'" name="msgs'.$inputName.'">'.(is_array($value)?implode("\n+=",$value):$value).'</textarea>'
					.'</div>';
			}
			$langFields[] = '</fieldset>';
		}
		return implode('',$langFields);
	}

	static function makeDicForm($target,$dicname,array $additionalmsgIds=null){
		static $i=0;
		if( class_exists('js_viewHelper',false) ){
			abstractController::getCurrentViewInstance(true)->_js_scriptOnce('
			function langManagerFormEnhance(){
				// make a lang selector
				var form = $(this)
					, selector = $("<select></select>").prependTo(form);
					;
				form.find("fieldset legend").each(function(){
					selector.append("<option>"+$(this).attr("class")+"</option>");
				});
				selector.change(function(){
					form.find("fieldset").hide().has("legend."+$(this).val()).show();
				}).val("'.self::getCurrentLang().'").selectbuttonset().change();
				// add a + button
				$(\'<button type="button">+</button>\').insertBefore(form.find("button:contains(Save)")).click(function(){
					//retrieve last id and prepare inputs
					var newMsg = form.find("div:last").clone()
						, id = $("fieldset div",form).length
						, idMsg = prompt("Please enter the new idMsg")
						;
					if(! idMsg)
						return false;
					idMsg = idMsg.replace(/"/g,\'&quot;\');
					selector.find("option").each(function(){
						++id;
						var l = $(this).val();
						$("input",newMsg).val("");
						$("textarea",newMsg).html("");
						newMsg.clone().html(
							newMsg.html().replace(/([_\[])\d+([\]"])/g,\'\$1\'+id+\'\$2\').replace(/(name="(?:id)?msgs\[)[a-z]{2}\]/g,"\$1"+l+"]")
						)
						.appendTo(form.find("fieldset:has(legend."+l+")"))
						.find("label").html(idMsg)
						.end().find("input").val(idMsg);
					})
				});
				$("button",form).button();
			}
			$("form.langManagerForm").each(langManagerFormEnhance);
			','langManagerMakeDicForm');
		}
		return '<form action="'.$target.'" method="post" class="langManagerForm" id="langManagerForm_'.($i++).'"><div>'.self::makeDicFormInputs($dicname,$additionalmsgIds).'<button type="submit">Save</button></div></form>';
	}

	/**
	* save returned values from makeDicForm[Inputs]
	*/
	static function saveDicFormInputs(array $datas){
		$path = end(self::$localesDirs);
		foreach($datas['idmsgs'] as $l=>$dic){
			if( !is_array($dic) )
				continue;
			foreach($dic as $dicname=>$idMsgs){
				if(! is_array($idMsgs))
					continue;
				$msgs = array();

				foreach($idMsgs as $k=>$idMsg){
					if( (!isset($datas['msgs'][$l][$dicname][$k])) || empty($datas['msgs'][$l][$dicname][$k]) ){
						$msgs[$idMsg] = '--UNSET--';
					}else{
						$msgs[$idMsg] = $datas['msgs'][$l][$dicname][$k];
					}
				}
				write_conf_file("$path/$l/$dicname",$msgs,true);
			}
		}
	}
}
