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
*/
class adminmodelsController extends modelsController{
	private $configFile = '';
	function init(){
		parent::init();
		self::appendAppMsg("Don't forget to edit the adminModelsController to check for user rights to access it or anyone could be editing your datas","error");
		$this->configFile = CONF_DIR.'/simpleMVCAdmin_'.FRONT_NAME.'_config.php';
		if(! is_writable($this->configFile) ){
			self::appendAppMsg("$this->configFile isn't writable.",'error');
		}
	}

	function listAction(){
		if( file_exists($this->configFile) ){
			$config = parse_conf_file($this->configFile,true);
			if( isset($config['LIST_'.$this->modelType]) ){
				$listFields = explode('|',$config['LIST_'.$this->modelType]);
				$this->listFields = array_combine($listFields,$listFields);
			}
		}
		parent::listAction();
	}

	function formAction(){
		parent::formAction();
		if( file_exists($this->configFile) ){
			$config = parse_conf_file($this->configFile,true);
			if( !empty($config['FORM_'.$this->modelType]) ){
				$inputOpts = json_decode($config['FORM_'.$this->modelType],true);
				if(! empty($inputOpts) )
					$this->inputOpts = $inputOpts;
			}
		}
	}
	/**
	* display model administration configuration form
	*/
	function configureAction(){
		$this->view->_js_loadPlugin('jquery');
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
		$this->config        = parse_conf_file($this->configFile,true);
		$this->primaryKey    = abstractModel::_getModelStaticProp($this->modelType,'primaryKey');
		$this->listedFields  = (!empty($this->config['LIST_'.$this->modelType]))?explode('|',$this->config['LIST_'.$this->modelType]):array();
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
		$this->view->listUrl = $this->view->url('list',$this->getName(),array('modelType'=>$this->modelType));
		#--- locale settings
		$this->langs = langManager::$acceptedLanguages;
		$_idMsgs = array_merge(array('save','back','Add new item'),$this->datasDefs,$hasOnes);
		foreach($this->langs as $l){
			$messages[$l] = parse_conf_file(langManager::lookUpDic('adminmodels_'.$this->modelType,$l),true);
			$idMsgs[$l] = array_unique(array_merge($_idMsgs,array_keys($messages[$l])));
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
		#- write config if needed
		$config['LIST_'.$this->modelType] = empty($_POST['fields'])?'--UNSET--':implode('|',$_POST['fields']);
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
		$g = new modelGenerator(DB_CONNECTION,LIB_DIR.'/models',1);
		$g->onExist = 'o';
		$g->doGeneration('DB_CONNECTION','');
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
		$modelType = preg_replace('![^a-z0-9_]!','',$modelType);
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
