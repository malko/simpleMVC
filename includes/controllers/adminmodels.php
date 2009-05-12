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
	function init(){
		parent::init();
		self::appendAppMsg("Don't forget to edit the adminModelsController to check for user rights to access it or anyone could be editing your datas","error");
		$this->configFile = CONF_DIR.'/simpleMVCAdmin_'.FRONT_NAME.'_config.php';
		if(! is_file($this->configFile) && is_writable(CONF_DIR))
			touch($this->configFile);
		if(! is_writable($this->configFile) ){
			self::appendAppMsg("$this->configFile isn't writable.",'error');
		}
		$this->pageTitle = langManager::msg($this->modelType);
	}

	function listAction(){
		if( file_exists($this->configFile) ){
			$config = parse_conf_file($this->configFile,true);
			if( isset($config['LIST_'.$this->modelType]) ){
				$listFields = json_decode($config['LIST_'.$this->modelType],true);
				$listKeys = array_keys($listFields);
				$this->listFields = array_combine($listKeys,$listKeys);
				$this->listFormats=$listFields;
			}
		}
		parent::listAction();
	}

	function formAction(){
		parent::formAction();
		if( file_exists($this->configFile) ){
			$this->config = parse_conf_file($this->configFile,true);
			if( !empty($this->config['FORM_'.$this->modelType]) ){
				$inputOpts = json_decode($this->config['FORM_'.$this->modelType],true);
				if(! empty($inputOpts) )
					$this->inputOpts = $inputOpts;
			}
			if( !empty($this->config['FORM_ORDER_'.$this->modelType]))
				$this->fieldsOrder = json_decode($this->config['FORM_ORDER_'.$this->modelType]);
		}
	}
	/**
	* display model administration configuration form
	*/
	function configureAction(){
		$this->view->_js_loadPlugin('jquery');
		#--- to string configuration
		$this->_toStr = $this->readModel__ToStr($this->modelType);
		$datasDefs = array_keys(abstractModel::_getModelStaticProp($this->modelType,'datasDefs'));
		$hasOnes     = array_keys(abstractModel::_getModelStaticProp($this->modelType,'hasOne'));
		foreach($datasDefs as $v)
			$this->datasFields .= "<span class=\"sMVC_dataField\">%$v</span> &nbsp; ";
		foreach($hasOnes as $v)
			$this->hasOnes .= "<span class=\"sMVC_dataField\">%$v</span> &nbsp; ";
		#--- list fields configuration
		#- check for config file
		$this->config        = parse_conf_file($this->configFile,true);
		$this->primaryKey    = abstractModel::_getModelStaticProp($this->modelType,'primaryKey');
		$this->listedFields  = (isset($this->config['LIST_'.$this->modelType]))?json_decode($this->config['LIST_'.$this->modelType],true):array();
		if( count($this->listedFields) ){ //-- restore selected order
			foreach(array_reverse($this->listedFields) as $k=>$v){
				unset($datasDefs[array_search($k,$datasDefs)]);
				array_unshift($datasDefs,$k);
			}
		}
		$this->datasDefs = $datasDefs;
		#--- forms config
		$formSettings  = (!empty($this->config['FORM_'.$this->modelType]))?json_decode($this->config['FORM_'.$this->modelType],true):array();
		foreach($formSettings as $k=>$setting){
			if( !empty($setting['type']) ){
				$inputTypes[$k] = $setting['type'];
				unset($setting['type']);
		}
			$inputOptions[$k] = empty($setting)?'':htmlentities(json_encode($setting));
		}
		$this->inputTypes = empty($inputTypes)?array():$inputTypes;
		$this->inputOptions = empty($inputOptions)?array():$inputOptions;
		if( !empty($this->config['FORM_ORDER_'.$this->modelType]))
			$this->fieldOrder = json_decode($this->config['FORM_ORDER_'.$this->modelType]);

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
	}

	/**
	* set how to display the model as a string
	*/
	function setToStringAction(){
		if( isset($_POST['_toStr']) )
			$this->replaceModel__ToStr($_GET['modelType'],$_POST['_toStr']);
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType));
	}
	/**
	* set model fields to display in list
	*/
	function setListAction(){
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
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType));
	}
	/**
	* set how to render administrations forms for the given model
	*/
	function setFormInputsAction(){
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
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType));
	}
	/**
	* configure langmanager messages for the given model administration
	*/
	function setMessagesAction(){
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
		return $this->redirectAction('configure',null,array('modelType'=>$this->modelType));
	}
	/**
	* re-generate models from database
	*/
	function generationAction(){
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
}
