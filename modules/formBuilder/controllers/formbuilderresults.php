<?php
class  formbuilderresultsController extends abstractController{

	static public $requiredRight = FORM_BUILDER_RIGHT;

	function preAction(){
		if(! checkUserRight(self::$requiredRight)){
			return $this->msgRedirect('Unauthorized access.','error',DEFAULT_DISPATCH);
		}
	}

	function indexAction(){
		$this->forms = formBuilderForms::getAllInstances(null, 'ORDER BY name ASC');
		$this->pageTitle = "Résultats";
	}

	function csvAction(){
		if( ! isset( $_POST['dateDeb'], $_POST['dateFin'], $_POST["formId"]) ){
			return $this->msgRedirect('Missing informations.','error');
		}
		$form =  formBuilderForms::getFilteredInstance(array('WHERE formId=?',$_POST["formId"]) );
		if(! $form instanceof formBuilderForms){
			$this->msgRedirect('Please select a valid form.','error');
		}
		// recupération des résultats
		$results =  formBuilderResults::getFilteredInstances(array(
			'WHERE formBuilderForm=? AND resultDate >= ? AND resultDate <= ?'
			, $_POST["formId"]
			, $_POST["dateDeb"]
			, $_POST["dateFin"].' 23:59:59'
		));
		
		if( $results->isEmpty() ){
			return $this->msgRedirect('No results to export for the given period','info');
		}
		$stdOut = fopen('php://output','a+');
		$formDatas = json_decode($form->rawData);
		if( null === $formDatas ){
			return $this->msgRedirect("Error while reading form values",'error');
		}

		// recupération des noms des champs
		$fields = array("Date du résultat", "Adresse IP");
		foreach ( $formDatas->hiddens as $hidden){
			$fields[] = $hidden->name;
		}
		foreach ( $formDatas->groups as $group){
			if( ! isset($group->rows) ){
				continue;
			}
			foreach($group->rows as $row){
				if( ! isset($row->widgets) ){
					continue;
				}
				foreach($row->widgets as $widget){
					if(isset($widget->name)){
						$fields[] = $widget->name;
					}
				}
			}
		}

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: '.date(DATE_RSS).' GMT');
		header('Content-Type: application/force-download;');
		header('Content-Disposition: attachment; filename="results_'.$form->name.'_'.preg_replace('/[^\d-]/','',$_POST["dateDeb"]).'_'.preg_replace('/[^\d-]/','',$_POST["dateFin"]).'.csv"');

		fputcsv( $stdOut, $fields, ';' ,'"');

		//-- ajout des résultats
		foreach($results as $result){
			$row = array();
			$resultDatas = json_decode($result->rawDatas);
			foreach( $fields as $fId=>$field ){
				switch( $fId ){
					case 0: $row[$field] = $result->resultDate;break;
					case 1: $row[$field] = $result->ip;break;
					default:
						$row[$field] = isset($resultDatas->$field)?$resultDatas->$field:'';
						if( is_array($row[$field])){
							$row[$field] = implode(',',$row[$field]);
						}
						$row[$field] = utf8_decode($row[$field]);
						break;
				}
			}
			fputcsv( $stdOut, $row, ';' ,'"');
		}

		smvcShutdownManager::shutdown(0,true);
	}

}