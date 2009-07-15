<?php
/**
* @class adminmodelsController
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2009-07-06 - now load config for each action
*            - 2009-07-02 - add LIST_FILTER configuration
*            - 2009-06-04 - add model and config file edition
*                         - all configuration methods are disabled when not in devel mode
*            - 2009-06-02 - add configuration for allowedActions
*            - 2009-05-28 - ncancel:loading of config file for ACTION allowed
*            - 2009-05-05 - better admin forms generation (grouping/ordering inputs fields)
*            - 2009-03-13 - made some change to list configuration to support ordering and formatStr
*                         - put configFile as protected instead of private to permitt extended class to access it
*            - 2009-03-12 - bug correction in getting modelFilePath from model with uppercase letter in modelName
*                         - better handling of editing langMessage from empty dictionnaries
*/
class adminmodelsController extends modelsController{
	protected $configFile = '';
	/** set one or multiple databases connection constants names to generate model from */
	protected $dbConnectionsDefined = array('DB_CONNECTION');


	/**
	* fieldsName on which the list can be filtered
	*/
	protected $_config = array();
	protected $_modelConfig = array();

	function init(){
		parent::init();
		self::appendAppMsg("Don't forget to edit the adminModelsController to check for user rights to access it or anyone could be editing your datas","error");
		$this->configFile = CONF_DIR.'/simpleMVCAdmin_'.FRONT_NAME.'_config.php';
		if(! is_file($this->configFile) && is_writable(CONF_DIR))
			touch($this->configFile);
		if(! is_writable($this->configFile) ){
			self::appendAppMsg("$this->configFile isn't writable.",'error');
		}
		$this->loadModelConfig();
		$this->pageTitle = langManager::msg($this->modelType);
	}

	function loadModelConfig(){
		if(! file_exists($this->configFile) )
			return false;
		$this->_config = parse_conf_file($this->configFile,true);
		foreach($this->_config as $k => $v){
			if( preg_match('!^(LIST(?:_FILTERS)?|ACTIONS|FORM(?:_ORDER)?)_'.$this->modelType.'$!',$k,$m)){
				if( $m[1] === 'ACTION' )
					$this->_allowedActions = 	json_decode($v,true);
				else
					$this->_modelConfig[$m[1]] = json_decode($v,true);
			}
		}
		$this->view->_config = $this->_config;
		$this->view->_modelConfig = $this->_modelConfig;
	}

	function listAction(){
		if( ! empty($this->_config) ){
			if( !empty($this->_modelConfig['LIST']) ){
				$listFields = $this->_modelConfig['LIST'];
				$listKeys = array_keys($listFields);
				$this->listFields = array_combine($listKeys,$listKeys);
				$this->listFormats=$listFields;
			}
			if( ( !empty($this->_modelConfig['LIST_FILTERS']) ) && ! empty($_GET['_filters']) ){
				$filters = match('!(?<=^|,)([^,]+?),([^,]+?)(?=,|$)!',$_GET['_filters'],array(1,2),true);
				if( ! empty($filters[0])){
					$datasDefs = array_keys(abstractModel::_getModelStaticProp($this->modelType,'datasDefs'));
					$_dynamicFilters = array();
					foreach($filters[0] as $k=>$fields ){
						if(! in_array($fields,$this->_modelConfig['LIST_FILTERS'],true) ){
							unset($filters[0][$k],$filters[1][$k]);
							continue;
						}
						if( !isset($datasDefs[$fields])){
							$_dynamicFilters[$fields] = $filters[1][$k];
							unset($filters[0][$k],$filters[1][$k]);
						}
					}
					if( count($filters[0])){
						$conds[0] = 'WHERE '.implode('=? AND ',$filters[0]).'=?';
						array_splice($conds,1,0,$filters[1]);
						$this->_models_ = abstractModel::getFilteredModelInstances($this->modelType,$conds);
						$this->fieldFilters = array_merge($_dynamicFilters,array_combine($filters[0],$filters[1]));
					}
					if( count($_dynamicFilters) ){
						$this->_models_ = abstractModel::getAllModelInstances($this->modelType);
						foreach($_dynamicFilters as $k=>$v)
							$this->_models_ = $this->_models_->filterBy($k,$v,'==');
						if( empty($this->fieldFilters))
							$this->fieldFilters = $_dynamicFilters;
					}

				}
			}
		}
		parent::listAction();
	}

	function formAction(){
		parent::formAction();
		if( !empty($this->_config) ){
			if( !empty($this->_modelConfig['FORM']) )
				$this->inputOpts = $this->_modelConfig['FORM'];
			if( !empty($this->_modelConfig['FORM_ORDER']))
				$this->fieldsOrder = $this->_modelConfig['FORM_ORDER'];
		}
	}
	/**
	* display model administration configuration form
	*/
	function configureAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		$this->view->_js_loadPlugin('jquery');
		$this->view->configFile = $this->configFile;
		$this->view->modelFile = $this->getModelFilePath($this->modelType);
		#--- to string configuration
		$this->_toStr = $this->readModel__ToStr($this->modelType);
		$this->datasDefs = array_keys(abstractModel::_getModelStaticProp($this->modelType,'datasDefs'));
		$hasOnes     = array_keys(abstractModel::_getModelStaticProp($this->modelType,'hasOne'));
		foreach($this->datasDefs as $v)
			$this->datasFields .= "<span class=\"sMVC_dataField\">%$v</span> &nbsp; ";
		foreach($hasOnes as $v)
			$this->hasOnes .= "<span class=\"sMVC_dataField\">%$v</span> &nbsp; ";
		#--- list fields configuration
		#- check for config file
		$this->primaryKey    = abstractModel::_getModelStaticProp($this->modelType,'primaryKey');
		$this->listedFields  = (isset($this->_modelConfig['LIST']))?$this->_modelConfig['LIST']:array();
		$datasDefs = $this->datasDefs;
		if( count($this->listedFields) ){ //-- restore selected order
			foreach(array_reverse($this->listedFields) as $k=>$v){
				unset($datasDefs[array_search($k,$datasDefs)]);
				array_unshift($datasDefs,$k);
			}
		}
		$this->listDatasDefs = $datasDefs;
		#--- forms config
		$formSettings  = (!empty($this->_modelConfig['FORM']))?$this->_modelConfig['FORM']:array();
		foreach($formSettings as $k=>$setting){
			if( !empty($setting['type']) ){
				$inputTypes[$k] = $setting['type'];
				unset($setting['type']);
			}
			$inputOptions[$k] = empty($setting)?'':htmlentities(json_encode($setting));
		}
		$this->inputTypes = empty($inputTypes)?array():$inputTypes;
		$this->inputOptions = empty($inputOptions)?array():$inputOptions;
		if( !empty($this->_modelConfig['FORM_ORDER']))
			$this->fieldOrder = $this->_modelConfig['FORM_ORDER'];

		$this->view->listUrl = $this->view->url('list',$this->getName(),array('modelType'=>$this->modelType));
		#--- locale settings
		$this->langs = langManager::$acceptedLanguages;
		$_idMsgs = array_merge(array('save','back','Add new item'),$this->datasDefs,$hasOnes);
		foreach($this->langs as $l){
			$messages[$l] = parse_conf_file(langManager::lookUpDic('adminmodels_'.$this->modelType,$l),true);
			$idMsgs[$l]   = array_unique(empty($messages[$l])?$_idMsgs:array_merge($_idMsgs,array_keys($messages[$l])));
		}

		$this->messages = $messages;
		$this->idMsgs = $idMsgs;
		$this->view->_allowedActions = $this->_allowedActions;
	}

	/**
	* set how to display the model as a string
	*/
	function setToStringAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		if( isset($_POST['_toStr']) )
			$this->replaceModel__ToStr($this->modelType,$_POST['_toStr']);
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType,'#'=>'string'));
	}
	/**
	* set model fields to display in list
	*/
	function setListAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		if( empty($_POST['fields'])){
			$config['LIST_'.$this->modelType] = '--UNSET--';
		}else{
			$flds = array();
			foreach($_POST['fields'] as $fld){
				$flds[$fld] =  isset($_POST['formatStr'][$fld])?$_POST['formatStr'][$fld]:false;
			}
			$config['LIST_'.$this->modelType] = json_encode($flds);
		}
		#- write config
		write_conf_file($this->configFile,$config,true);
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType,'#'=>'list'));
	}
	/**
	* set how to render administrations forms for the given model
	*/
	function setFormInputsAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		if(! empty($_POST) ){
			$c = array();
			foreach($_POST['inputTypes'] as $k=>$v){
				$options = array();
				if( $v !== '--- default ---')
					$options['type'] =  $v;
				if( ! empty($_POST['inputOptions'][$k] ) )
					$options = array_merge($options,json_decode($_POST['inputOptions'][$k],true));
				if( empty($options) )
					continue;
				$c[$k] = $options;
			}
			if(! isset($_POST['fieldSet']) ){ #- no grouping so just stock order of elements
				$config['FORM_ORDER_'.$this->modelType] = json_encode(array_keys($_POST['inputTypes']));
			}else{
				$order = array('fieldGroupMethod'=>empty($_POST['fieldGroupMethod'])?'fieldset':$_POST['fieldGroupMethod']);
				foreach($_POST['fieldsOrder'] as $k=>$v){
					$name = ($k==='primary'?'':(empty($_POST['fieldSet'][$k])?"group$k":$_POST['fieldSet'][$k]));
					$order[]=array('name'=>$name,'fields'=>$v?explode(',',$v):'');
				}
				$config['FORM_ORDER_'.$this->modelType] = json_encode($order);
			}
			$config['FORM_'.$this->modelType] = empty($c)?'--UNSET--':json_encode($c) ;
			write_conf_file($this->configFile,$config,true);
		}
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType,'#'=>'forms'));
	}

	function resetFieldsOrder(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		$config['FORM_ORDER_'.$this->modelType] = '--UNSET--';
		write_conf_file($this->configFile,$config,true);
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType,'#'=>'forms'));
	}
	/**
	* configure langmanager messages for the given model administration
	*/
	function setMessagesAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		#-- langs
		$langs = array_keys($_POST['msgs']);
		foreach($langs as $l){
			$dict = $this->checkDisctionnaries($l,true);
			if( false === $dict )
				return $this->forward('configure');
			foreach($_POST['msgs'][$l] as $id=>$msg)
				$dict[$id] = strlen($msg)?$msg:'--UNSET--'; // unset empty values
			write_conf_file(APP_DIR."/locales/$l/adminmodels_$this->modelType",$dict,true);
		}
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType,'#'=>'messages'));
	}

	function setActionsAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		foreach($_POST['actions'] as $k=>$v)
			$_POST['actions'][$k] =  (bool) $v;
		write_conf_file($this->configFile,array("ACTION_$this->modelType"=>json_encode($_POST['actions'])),true);
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType,'#'=>'actions'));
	}
	/**
	* re-generate models from database
	*/
	function generationAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		#- check for read/write rights
		if(! is_dir(LIB_DIR.'/models')){
			mkdir(LIB_DIR.'/models');
			chmod(LIB_DIR.'/models',0777);
		}
		if(! is_writable(LIB_DIR.'/models') ){
			self::appendAppMsg("Model directory must be writable.",'error');
			return $this->redirect($_SERVER['HTTP_REFERER']);
		}
		#- do generation for each setted databases
		foreach($this->dbConnectionsDefined as $dbConn){
			eval('$g = new modelgenerator('.$dbConn.',LIB_DIR."/models",1);');
			$g->onExist = 'o';
			$g->doGeneration($dbConn,'');
		}

		#- then ensure correct rights for files
		$modelFiles = glob(LIB_DIR.'/models/*.php');
		foreach($modelFiles as $f){
			chmod($f,0666);
		}
		return $this->redirect($_SERVER['HTTP_REFERER']);
	}

	private function readModel__ToStr($modelType){
		$modelStr = $this->getModelFile($modelType);
		if( false===$modelStr )
			return false;
		$modelToStr = match('!^(\s*static\s*public\s*\$__toString\s*=\s*(["\'])([^\2]*?)(\2)\s*;)\s*$!m',$modelStr,3);
		return $modelToStr;
	}
	private function replaceModel__ToStr($modelType,$newToStr){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		$modelStr = $this->getModelFile($modelType);
		if( false===$modelStr )
			return false;
		$newContent = preg_replace('!^(\s*)static\s*public\s*\$__toString\s*=\s*(["\'])([^\2]*?)(\2)\s*;\s*$!m','\1static public $__toString = "'.str_replace(array("\n",'"'),array('\n','\"'),$newToStr).'";',$modelStr);
		file_put_contents($this->getModelFilePath($modelType),$newContent);
		return $newContent;
	}
	private function getModelFilePath($modelType){
		$modelType = preg_replace('![^a-z0-9_]!i','',$modelType);
		$modelFile = LIB_DIR.'/models/'.$modelType.'.php';
		return file_exists($modelFile)?$modelFile:false;
	}
	private function getModelfile($modelType){
		$path = $this->getModelFilePath($modelType);
		return $path?file_get_contents($path):false;
	}

	private function checkDisctionnaries($lang,$returnDict=false){
		$dictDir = APP_DIR.'/locales/'.$lang;
		$dictFile = $dictDir.'/adminmodels_'.$this->modelType;
		if( ! is_dir($dictDir) ){ #- check directory exists
			$success = mkdir($dictDir,0777,true); #- create dir recursively
			if(! $success)
				return ! self::appendAppMsg(" can't create directory $dictDir",'error');
			chmod(dirname($dictDir),0777); #- chmod doesn't bother about umask settings
			chmod($dictDir,0777); #- chmod doesn't bother about umask settings
		}
		if(! is_file($dictFile) ){ # check file exists and try to create it
			$success = touch($dictFile);
			if(! $success)
				return ! self::appendAppMsg("can't create dictionnary $dictFile",'error');
			chmod($dictFile,0666);
		}elseif( ! is_writable($dictFile) ){
			return ! self::appendAppMsg($dictFile.' must be writable to set messages.','error');
		}
		return $returnDict?parse_conf_file($dictFile,true):true;
	}

	function saveEditModelAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		file_put_contents($this->getModelFilePath($this->modelType),preg_replace('/\r(?=\n)/','',$_POST['smvcModel']));
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType,'#'=>'model'));
	}
	function saveEditConfigAction(){
		if(! (defined('DEVEL_MODE') && DEVEL_MODE) )
			return $this->forward(ERROR_DISPATCH);
		file_put_contents($this->configFile,preg_replace('/\r(?=\n)/','',$_POST['smvcConfig']));
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType,'#'=>'config'));
	}
}
