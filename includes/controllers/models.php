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
*            - 2009-06-02 - add support for confirmation fields and sprintFatas to langMsg methods
*            - 2009-05-28 - ncancel: add allowed action property $allowedActions
*            - 2009-04-06 - add support for activable models
*            - 2009-03-31 - autodetection of field that need to be loaded when loadDatas is empty
*            - 2009-03-19 - rewrite support for orderable models
*                         - set_layout now consider for adminmodelsModelType templates
*            - 2009-03-08 - little modif in setDictName to check dictionnaries from generated with adminmodelsController
*            - 2009-02-08 - add some automated support for orderable models
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

	/** config of (un)doable basic actions */
	protected $_allowedActions=array(
		'edit'=>true,
		'list'=>true,
		'del'=>true,
		'add'=>true
	);

	function init(){
		parent::init();
		if(! $this->modelType )
			$this->modelType = isset($_POST['modelType'])?$_POST['modelType']:(isset($_GET['modelType'])?$_GET['modelType']:false);
		if( ! $this->modelType){
			self::appendAppMsg('Vous devez specifier le type de model à administrer','error');
			$this->redirectAction('index','default');
		}
		$this->view->modelType = $this->modelType;
		$this->view->setLayout(array(
			'header.tpl.php',
			($this->getName()=='adminmodels'?strtolower($this->modelType).'_:action.tpl.php|':'').':controller_:action.tpl.php|models_:action.tpl.php|default_:action.tpl.php',
			'footer.tpl.php'
		));
	}

	function _isAllowedAction_($action,$dispatchRedirect=DEFAULT_DISPATCH){
		if( isset($this->_allowedActions[$action]) && empty($this->_allowedActions[$action]) ){
			self::appendAppMsg('unauthorized action!','error');
			return $this->redirectAction($dispatchRedirect,null,array('modelType'=>$this->modelType));
		}
		return true;
	}
	function indexAction(){
		return $this->forward('list');
	}

	function listAction(){
		$this->_isAllowedAction_('list',DEFAULT_DISPATCH);

		$this->view->assign('_smvcAllowedAction',$this->_allowedActions);
		$this->setDictName();
		$supportedAddons = abstractModel::_getModelStaticProp($this->modelType,'modelAddons');
		$orderable = in_array('orderable',$supportedAddons);
		$activable = in_array('activable',$supportedAddons);
		$models = abstractModel::getAllModelInstances($this->modelType);

		if(empty($this->loadDatas)){ //-- attempt to autodetect datas that may need to be loaded
			$relDefs = abstractModel::modelHasRelDefs($this->modelType,null,true);
			foreach(array_keys($this->listFields) as $fld){
				if( isset($relDefs['hasOne'][$fld]) || isset($relDefs['hasMany'][$fld]) )
					$this->loadDatas = empty($this->loadDatas)?$fld:"$this->loadDatas|$fld";
			}
		}
		if( ! empty($this->loadDatas) )
			$models->loadDatas($this->loadDatas);
		$datas = array();

		if( count($models) ){
			$this->view->_js_loadPlugin('jqueryui');
			//-- prepare common modelAddons management
			$orderableField = null;
			if( $orderable ){
				list($orderableField,$orderableGroupField) = $models->current()->_getOrderableFields();
				$models->sort($orderableField);
				$orderableLastPos = array();
				if(! $orderableGroupField ){
					$orderableLastPos[] = $models->current()->orderableGetLastPK();
				}else{
					$orderableLastPos = $models->filterBy($orderableField,0)->orderableGetLastPK();
					$models->sort($orderableGroupField);
				}
			}
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
				$nbZeroFill = ceil($models->count()/10);
				foreach($models as $m){
					$row = array();
					$row['id'] = $m->PK.'/modelType/'.$this->modelType;
					foreach($this->listFields as $k=>$v){
						if($this->listFormats[$k]){
							$row[$k] = $m->__toString($this->listFormats[$k]);
							continue;
						}
						switch($k){
							case $orderableField:
								$row[$k] = '<span style="display:none;">'.sprintf('%0'.$nbZeroFill.'d',$m->{$k}).'</span><div class="ui-buttonset-small"><a title="move up" href="'.($m->{$k}>0?$this->url('moveup').'/id/'.$row['id'].'" class="ui-button-i-arrow-1-n':'#" class="ui-button-i-arrow-1-n ui-state-disabled').'">move up</a>'
									.'<a href="'.(in_array($m->PK,$orderableLastPos)?'#" class="ui-button-i-arrow-1-s ui-state-disabled':$this->url('movedown').'/id/'.$row['id'].'" class="ui-button-i-arrow-1-s').'" title="move down">move down</a></div>';
								break;
							default:
								if(! ($activable && in_array($k,$m->_activableFields,'true')) ){
									$row[$k] = $m->{$k};
								}else{
									$active=$m->{$k}?true:false;
									$title = ($active?'de-':'').'activate';
									$row[$k] = '<a href="'.$this->url('setActive').'/state/'.($active?0:1).'/prop/'.$k.'/id/'.$row['id']
										.'" title="'.$title.'"><img src="'.GUI_IMG_URL.'/icones/admin/dialog-'.($active?'yes':'no').'.png" alt="'.$title.'" /></a>';
								}
								break;
						}
					}
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
		$this->_isAllowedAction_('edit',DEFAULT_DISPATCH);
		if(! isset($_GET['id']) ){
			self::appendAppMsg('Identifiant d\'enregistrement à modifié manquant','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$model = abstractModel::getModelInstance($this->modelType,$_GET['id']);
		if(! $model instanceof $this->modelType ){
			self::appendAppMsg('Enregistrement inexistant en base de données','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$this->view->_model_ = $model;
		$this->view->assign($model->datas);
		return $this->forward('form');
	}

	function formAction(){
		$this->_isAllowedAction_(null===$this->view->_model_?'add':'edit',$this->getName().':list');
		$this->setDictName();
		$this->view->datasDefs = abstractModel::_getModelStaticProp($this->modelType,'datasDefs');
		$this->view->relDefs   = abstractModel::modelHasRelDefs($this->modelType,null,true);
		$this->view->actionUrl = $this->view->url('save',$this->getName(),array('modelType'=>$this->modelType));
		$this->view->listUrl   = $this->view->url('list',$this->getName(),array('modelType'=>$this->modelType));
	}

	function setActiveAction(){
		if( ! isset($_GET['id'])){
			self::appendAppMsg('La page que vous avez demadée n\'exite pas.','error');
		}
		$m = abstractModel::getModelInstance($this->modelType,$_GET['id']);
		if( ! $m instanceof abstractModel)
			self::appendAppMsg('La page que vous avez demadée n\'exite pas.','error');
		$m->{$_GET['prop']} = $_GET['state'];
		$m->save();
		return $this->redirectAction('list',null,array('modelType'=>$this->modelType));
	}

	function saveAction(){
		if( empty($_POST) ){
			self::appendAppMsg('Aucune données à enregistrée.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType,'embed'=>(empty($_GET['embed'])?'':'on')));
		}

		if(isset($_POST['_smvc_confirm']) ){ # manage confirm fields
			foreach($_POST['_smvc_confirm'] as $k=>$v){
				if( ! (isset($_POST[$k]) && $_POST[$k] === $v) ){
					self::appendAppMsg($this->langMsg("field %s mismatch it's confirmation",array($k,$v)),'error');
					unset($_POST['_smvc_confirm']);
					$this->view->assign($_POST);
					return $this->forward('form');
				}
				unset($_POST['_smvc_confirm'][$k]);
			}
			unset($_POST['_smvc_confirm']);
		}

		#- get instance
		$modelPKName = abstractModel::_getModelStaticProp($this->modelType,'primaryKey');
		if(! isset($_POST[$modelPKName]) ){
			$this->_isAllowedAction_('add',DEFAULT_DISPATCH);
			$model = abstractModel::getModelInstanceFromDatas($this->modelType,$_POST);
		}else{
			$this->_isAllowedAction_('edit',DEFAULT_DISPATCH);
			$model = abstractModel::getModelInstance($this->modelType,$_POST[$modelPKName]);
			if(! $model instanceof $this->modelType ){
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
		$this->_isAllowedAction_('del',DEFAULT_DISPATCH);
		if(! isset($_GET['id']) ){
			self::appendAppMsg('Manque d\'information sur l\'action à effectuer.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$model =  abstractModel::getModelInstance($this->modelType,$_GET['id']);
		if(! $model instanceof $this->modelType ){
			self::appendAppMsg('Enregistrement introuvable en base de données.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$model->delete();
		self::appendAppMsg('Enregistrement supprimée.','success');
		return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
	}

	###--- methods for orderable models ---###
	function moveupAction(){
		if(! isset($_GET['id']) ){
			self::appendAppMsg('Manque d\'information sur l\'action à effectuer.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$m = abstractModel::getModelInstance($this->modelType,$_GET['id']);
		if(! $m instanceof $this->modelType ){
			self::appendAppMsg('Enregistrement introuvable en base de données.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$m->moveUp();
		return $this->redirectAction('list',null,array('modelType'=>$this->modelType));
	}
	function movedownAction(){
		if(! isset($_GET['id']) ){
			self::appendAppMsg('Manque d\'information sur l\'action à effectuer.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$m = abstractModel::getModelInstance($this->modelType,$_GET['id']);
		if(! $m instanceof $this->modelType ){
			self::appendAppMsg('Enregistrement introuvable en base de données.','error');
			return $this->redirectAction('list',$this->getName(),array('modelType'=>$this->modelType));
		}
		$m->moveDown();
		return $this->redirectAction('list',null,array('modelType'=>$this->modelType));
	}


	###--- lang management helpers ---###
	public function setDictName(){
		#- set dicName
		list($c,$a) = abstractController::getCurrentDispatch(true);
		$m = $this->modelType;
		if( $this instanceof adminmodelsController && $this->getName()!='adminmodels')
			$this->_langManagerDicName = $c."_$m"."$a|$c"."_$m|adminmodels_$m|$c"."_$a"."|$c|default";
		else
			$this->_langManagerDicName = $c."_$m"."$a|$c"."_$m|$c"."_$a"."|$c|default";
	}

	public function langMsg($msg,$datas=null){
		return langManager::msg($msg,$datas,$this->_langManagerDicName);
	}

}
