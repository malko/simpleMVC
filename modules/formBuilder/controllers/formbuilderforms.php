<?php
class formbuilderformsController extends modulesadminmodelsController{
	public $modelType = 'formBuilderForms';
	public  $requiredRight = FORM_BUILDER_RIGHT;
	protected $_modelConfig = array(
		'LIST' => array(
			'creationDate'=>''
			,'user'=>''
			#- ,'name'=>'<a href="%{ baseView::getInstance()->url(":preview",array(%name),false,null,false) }%" target="_blank" class="ui-button ui-button-tiny-i-search">preview</a> %name'
			,'name'=>'%name'
			,'activityPeriodStart'=>''
			,'activityPeriodEnd'=>''
			,'active'=>''
      ,'duplicate'=>'<a href="%{ baseView::getInstance()->url(":duplicate",array(%name),false,null,false) }%" class="ui-button ui-button-tiny-i-clipboard duplicateButton">duplicate</a>'
		)
	);

	function previewAction($formName=null){
		$this->view->setLayout(array('forms_form.tpl.php'));
		$form =  formBuilderForms::getFilteredInstance( array('where name=?',$formName) );
		if( (!$form instanceof formBuilderForms) || $form->isTemporary() ){
			self::appendAppMsg('Unknown page','error');
			return $this->forward('error');
		}
		$this->formName = $formName;
		$this->view->requiredRight = $this->requiredRight;
	}

	function duplicateAction($formName, $newFormName=null){
		$form = formBuilderForms::getFilteredInstance(array("WHERE name = ?",$formName));
		if(! $form instanceof formBuilderForms ){
			return $this->msgRedirect("Form doesn't exists",'error',":list");
		}
		$newForm = formBuilderForms::getNew();
		$newFormDatas  = $form->datas;
		unset($newFormDatas['creationDate']);
		$newForm->_setDatas($newFormDatas,true);
		if( empty( $newFormName) ){
			$newFormName = $form->name;
		}
		$newForm->name = $newFormName;
		$i=1;
		while(  ! ($newForm->name && $newForm->isUniqueName()) ){
			$newForm->name = "$newFormName$i";
			if( $i++ > 50 ){
				return $this->msgRedirect('Please give a valid and unique form name.');
			}
		}
		$newForm->save();
		return $this->msgRedirect("Form duplicated",'success',":list");
	}
	function listAction(){
		$this->view->helperLoad('button');
		return parent::listAction();
	}
	function saveAction(){
		if( !empty($_POST) ){
			$_POST['name'] = $_POST['formName'];
			$_POST['action'] = $_POST['formAction'];
			$_POST['method'] = $_POST['formMethod'];
			$_POST['activityPeriodStart'] = empty($_POST['activityPeriodStart'])?'0000-00-00':$_POST['activityPeriodStart'];
			$_POST['activityPeriodEnd'] = empty($_POST['activityPeriodEnd'])?'0000-00-00':$_POST['activityPeriodEnd'];
			$_POST['active'] = empty($_POST['active'])?0:1;
			$_POST['groupMethod'] = $_POST['formGrouping'];
			$_POST['user'] = $_SESSION['moduser']['userId'];
		}
		return parent::saveAction();
	}

	function saveCustomWidget(){
		if( empty($_POST['customName'] )){
			echo "error: Missing required customName";
			smvcShutdownManager::shutdown(0,true);
		}
		$widget = formBuilderWidgets::checkNameExists($_POST['customName'],true);
		if(! $widget instanceof formBuilderWidgets ){
			$widget = formBuilderWidgets::getNew();
		}
		$widget->_setDatas(array(
			'name'=>$_POST['customName']
			,'rawProperties'=>json_encode($_POST)
		));
		if( $widget->hasFiltersMsgs() ){
			echo "error: ".implode('<br />',$widget->getFiltersMsgs());
			smvcShutdownManager::shutdown(0,true);
		}
		$widget->save();
		echo "success: widget saved";
		smvcShutdownManager::shutdown(0,true);
	}

	function getCustomWidgets(){
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		echo '{'.formBuilderWidgets::getAllInstances(null,'ORDER BY name ASC')->_toString('"%name":%rawProperties',',').'}';
		smvcShutdownManager::shutdown(0,true);
	}
}