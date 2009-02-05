<?php
/**
* @class modelsController
* @package simpleMVC
* @licence LGPL
* @author Jonathan Gotti < jgotti at jgotti dot net >
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2009-01-14 - new methods setDictName and langMsg to better handle langManager dictionnary lookup
*            - 2009-01-14 - new property $loadDatas to force loadDatas before rendering list.
*            - 2009-01-05 - now listAction do a lookup on list headers using langManager::msg
*            - 2008-09-11 - define dummy indexAction that forward to listAction
*                         - remove setLayout from formAction
*                         - list without listFields setted will ask value from model instead of taking it directly from model->datas
*/
abstract class modelsController extends abstractController{

	public $modelType = null;

	/**
	* list of fields to show in generated model list
	* key are property names and values will be used as headers.
	*/
	public $listFields=array();
	/**
	* optional list of related model properties to load to properly render the list
	* (kindof performance optimisation to avoid each models to be load one by one)
	*/
	public $loadDatas = null;

	function init(){
		parent::init();
		if(! $this->modelType )
			$this->modelType = isset($_POST['modelType'])?$_POST['modelType']:(isset($_GET['modelType'])?$_GET['modelType']:false);
		if( ! $this->modelType){
			self::appendAppMsg('Vous devez specifier le type de model à administrer','error');
			$this->redirectAction('index','default');
		}
		$this->view->setLayout(array(
			'header.tpl.php',
			':controller_:action.tpl.php|models_:action.tpl.php|default_:action.tpl.php',
			'footer.tpl.php'
		));
		$this->view->modelType = $this->modelType;
	}

	function indexAction(){
		return $this->forward('list');
	}

	function listAction(){
		$this->setDictName();
		$models = abstractModel::getAllModelInstances($this->modelType);
		if( ! empty($this->loadDatas) )
			$models->loadDatas($this->loadDatas);
		$datas = array();
		if( count($models) ){
			$PKname = abstractModel::_getModelStaticProp($this->modelType,'primaryKey');
			if( empty($this->listFields) ){
				$modelDatasDefs = abstractModel::_getModelStaticProp($this->modelType,'datasDefs');
				foreach($models as $m){
					$row = array();
					foreach(array_keys($m->datas) as $key){
						if( $key ===$PKname ){
							$row['id'] = $m->PK.'/modelType/'.$this->modelType;
							continue;
						}
						$row[$key] = $m->{$key};
					}
					$datas[] = $row;
				}
				$this->view->listHeaders = array_map(array($this,'langMsg'),array_keys($datas[0]));
			}else{
				foreach($models as $m){
					$row = array();
					$row['id'] = $m->PK.'/modelType/'.$this->modelType;
					foreach($this->listFields as $k=>$v)
						$row[$k] = $m->{$k};
					$datas[] = $row;
				}
				$this->view->listHeaders = array_map(array($this,'langMsg'),array_values($this->listFields));
			}
		}
		$this->view->listDatas = $datas;
	}

	function addAction(){
		return $this->forward('form');
	}

	function editAction(){
		if(! isset($_GET['id']) ){
			self::appendAppMsg('Identifiant d\'enregistrement à modifié manquant','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$model = abstractModel::getModelInstance($this->modelType,$_GET['id']);
		if($model === false){
			self::appendAppMsg('Enregistrement inexistant en base de données','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$this->view->_model_ = $model;
		$this->view->assign($model->datas);
		return $this->forward('form');
	}

	function formAction(){
		$this->setDictName();
		$this->view->datasDefs = abstractModel::_getModelStaticProp($this->modelType,'datasDefs');
		$this->view->relDefs   = abstractModel::modelHasRelDefs($this->modelType,null,true);
		$this->view->actionUrl = $this->view->url('save',$this->getName(),array('modelType'=>$this->modelType));
		$this->view->listUrl   = $this->view->url('list',$this->getName(),array('modelType'=>$this->modelType));
	}

	function saveAction(){
		if( empty($_POST) ){
			self::appendAppMsg('Aucune données à enregistrée.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType,'embed'=>(empty($_GET['embed'])?'':'on')));
		}
		#- get instance
		$modelPKName = abstractModel::_getModelStaticProp($this->modelType,'primaryKey');
		if(! isset($_POST[$modelPKName]) ){
			$model = abstractModel::getModelInstanceFromDatas($this->modelType,$_POST);
		}else{
			$model = abstractModel::getModelInstance($this->modelType,$_POST[$modelPKName]);
			if( $model === false){
				self::appendAppMsg('Mise à jour d\'un élément inexistant en base de données.','error');
				return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType,'embed'=>(empty($_GET['embed'])?'':'on')));
			}
			$model->_setDatas($_POST);
		}
		if( $model->hasFiltersMsgs() ){
			self::appendAppMsg($model->getFiltersMsgs(),'error');
			$this->view->assign($model->datas);
			return $this->forward('form',$this->getName());
		}
		if($model->isTemporary())
			$successMsg = "Nouvel enregistrement ajouté.";
		else
			$successMsg = "Enregistrement mis à jour.";
		$model->save();
		self::appendAppMsg($successMsg,'success');
		return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType,'embed'=>(empty($_GET['embed'])?'':'on')));
	}

	function delAction(){
		if(! isset($_GET['id']) ){
			self::appendAppMsg('Manque d\'information sur l\'action à effectuer.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$model =  abstractModel::getModelInstance($this->modelType,$_GET['id']);
		if($model === false){
			self::appendAppMsg('Enregistrement introuvable en base de données.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$model->delete();
		self::appendAppMsg('Enregistrement supprimée.','success');
		return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
	}

	###--- lang management helpers ---###
	public function setDictName(){
		#- set dicName
		list($c,$a) = abstractController::getCurrentDispatch(true);
		$m = $this->modelType;
		$this->_langManagerDicName = $c."_$m"."$a|$c"."_$m|$c"."_$a"."|$c|default";
	}

	public function langMsg($msg){
		return langManager::msg($msg,null,$this->_langManagerDicName);
	}

}
