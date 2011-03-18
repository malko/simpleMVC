<?php
/**
* @package simpleMVC
* @subPackage helpers
* @class modelFormInput_viewHelper
* @changelog
* - 2010-10-05 - add possibility to display primaryKey fields
* - 2010-05-27 - forceEmptyChoice passed to false allow to remove the empty %s value for hasMany relations
* - 2010-05-17 - add forceEmptyChoice options for enum/sets
* - 2010-02-xx - related model field  supporting orderableModelAddon will be sort by orderableField if no sort options is given
* - 2010-02-09 - add sort options for hasOne relations too
* - 2009-10-12 - add support for controller define input types
* - 2009-09-04 - boolean values are now selectbuttonset by defaults
* - 2009-06-25 - add sort options for hasMany relations.
* - 2009-05-28 - now manage hasMany relations as well
* - 2009-05-11 - empty date/datetime/time values with no default are set to current date.
* - 2009-01-05 - add timepicker and datetimepicker detection
* - 2008-11-07 - add possibility to skip automated date type setting
* - 2008-10-30 - add svninfos and put it uptodate with local version
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
class modelFormInput_viewHelper extends abstractViewHelper{
	/**
	* @param mixed  $modelName  string modelName or modelInstance to retrieve value from
	* @param string $keyName    name of the property to get input for
	* @param array  $options    list of options to render the input here's some of possible options
	*                           - value  -> override the default/model value by the given one
	*                           - values -> list of choice for select/checkbox/radio...
	*                           - type   -> formInput type to use to render the input field
	*                           - formatStr -> string to describe the output format %input and %label will be replaced by their corresponding values.
	*                           - forceEmptyChoice allow to force an empty value in multiple choices on related hasOne
  *                           - @see formInput for other supported options depending the type of field to edit.
	*
	*/
	function modelFormInput($modelName, $keyName, array $options=array()){
		$relsDefs =  abstractModel::modelHasRelDefs($modelName,null,true);
		$datasDefs = abstractModel::_getModelStaticProp($modelName,'datasDefs');

		if(! isset($options['label'])){
			$options['label'] = ucFirst(langManager::msg($keyName,null,$this->view->_langManagerDicName));
		}

		if( (!empty($options['uneditable'])) && $modelName instanceof abstractModel ){
			$options['disabled'] = "disabled";
			unset($options['uneditable']);
			$options['class'] = (empty($options['class'])?'':$options['class'].' ').'tk-state-disabled';
		}
		# commence par checker les relations
		if( isset($relsDefs['hasOne'][$keyName]) ){
			if( $modelName instanceof abstractModel && is_object($modelName->{$keyName}) ){
				$value = $modelName->{$keyName}->PK;
			}elseif(!isset($options['default']) && isset($relsDefs['hasOne'][$keyName]['localField']) && isset($datasDefs[$relsDefs['hasOne'][$keyName]['localField']]['Default']) ){
				$value = $datasDefs[$relsDefs['hasOne'][$keyName]['localField']]['Default'];
			}else{
				$value = null;
			}
			if( empty($options['values']) ){
				$relModelName = $relsDefs['hasOne'][$keyName]['modelName'];
				$choices = abstractModel::getAllModelInstances($relModelName);
				if( !empty($options['sort'])){
					$choices->{$options['sort']}();
					unset($options['sort']);
				}elseif($choices->count() &&  abstractModel::_modelSupportsAddon($relModelName,'orderable') ){
					$choices->sort($choices->current()->_orderableField);
				}
				if( $relsDefs['hasOne'][$keyName]['relType'] !== 'dependOn' || !empty($options['forceEmptyChoice'])){
					$options['values'][''] = '--- '.langManager::msg((empty($options['forceEmptyChoice'])||true===$options['forceEmptyChoice'])?$options['label']:$options['forceEmptyChoice']).' ---';
				}
				foreach($choices as $ck=>$cv)
					$options['values'][$ck]=$cv->__toString();
			}

			if(! empty($options['value']) )
				$value = $options['value'];
			return $this->formInput($keyName,($value instanceof abstractModel?$value->PK:$value),empty($options['type'])?'select':$options['type'],$options);
		}elseif(isset($relsDefs['hasMany'][$keyName]) ){
			#- prepare les valeurs
			if( $modelName instanceof abstractModel && $modelName->{$keyName} instanceof modelCollection)
				$value = $modelName->{$keyName}->PK;
			else
				$value=null;
			if(! empty($options['value']) )
				$value = $options['value'];

			if( empty($options['values']) ){
				$relModelName = $relsDefs['hasMany'][$keyName]['modelName'];
				$choices = abstractModel::getAllModelInstances($relModelName);
				if( !empty($options['sort'])){
					$choices->{$options['sort']}();
					unset($options['sort']);
				}elseif( $choices->count() && abstractModel::_modelSupportsAddon($relModelName,'orderable') ){
					$choices->sort($choices->current()->_orderableField);
				}
				if( isset($options['forceEmptyChoice']) && $options['forceEmptyChoice']===false){
					unset($options['forceEmptyChoice']);
				}else{
					$options['values'][0] = langManager::msg('empty %s value',array(langManager::msg($keyName,null,$this->view->_langManagerDicName)),$this->view->_langManagerDicName);
				}
				if( isset($options['type']) && strpos($options['type'],'check') === 0){
					$this->view->_js_script('
					$("input[name^=\''.$keyName.'[\']").change(function(){
						if( $(this).val() !== "0"){
							$("#'.$keyName.'-0").attr("checked",false);
						}else if($(this).is(":checked")){
							$("input:checked[name^=\''.$keyName.'[\']").not(this).attr("checked",false);
						}
					})');
				}
				foreach($choices as $ck=>$cv){
					$options['values'][$ck]=$cv->__toString();
				}
			}
			if( ! isset($options['multiple']) )
				$options['multiple'] = 'multiple';
			return $this->formInput($keyName,$value,empty($options['type'])?'select':$options['type'],$options);
		}
		if( $keyName === 'PK' || $keyName === abstractModel::_getModelStaticProp($modelName,'primaryKey') ){
			if( ! $modelName instanceof abstractModel ){
				if( empty($options['type']) || $options['type']==='hidden' ){
				return '';
				}
				return $this->formInput($keyName,'',$options['type'],$options);//$options['type']);;
			}
			return $this->formInput($keyName,$modelName->PK,empty($options['type'])?'hidden':$options['type']);
		}
		#- try to get def from datas array
		if( empty($datasDefs[$keyName]) ){
			if( isset($options['value']))
				$value = $options['value'];
			elseif( $modelName instanceof abstractModel)
				$value = $modelName->{$keyName};
			else
				$value=null;
			return $this->formInput($keyName,$value,empty($options['type'])?'txt':$options['type'],$options);
		}else{
			if(isset($options['value']) )
				$value = $options['value'];
			else
				$value = isset($options['default'])?$options['default']:(( $modelName instanceof abstractModel )?$modelName->{$keyName}:$datasDefs[$keyName]['Default']);
			$datasDefs[$keyName]['Type'] = trim(strtolower($datasDefs[$keyName]['Type']));
			#- check for enum types
			if( preg_match('!^enum!',$datasDefs[$keyName]['Type'])){
				if(empty($options['values']) &&  method_exists($modelName,'get'.$keyName.'PossibleValues') ){
					if(!empty($options['forceEmptyChoice'])){
						'--- '.langManager::msg((empty($options['forceEmptyChoice'])||true===$options['forceEmptyChoice'])?$options['label']:$options['forceEmptyChoice']).' ---';
						if( in_array($options['forceEmptyChoice'],array(1,true,'1'),true)){
							$options['values'][''] = '--- '.langManager::msg((empty($options['forceEmptyChoice'])||true===$options['forceEmptyChoice'])?$options['label']:$options['forceEmptyChoice']).' ---';
						}else{
							$options['values'][''] = langManager::msg($options['forceEmptyChoice']);
						}
					}
					eval('$values = '.(is_string($modelName)?$modelName:$modelName->modelName).'::get'.$keyName.'PossibleValues();');
					foreach($values as $v)
						$options['values'][$v] = $v;
				}
				return $this->formInput($keyName,$value,empty($options['type'])?'select':$options['type'],$options);
			}
			if(empty($options['type']) ){
				if( $datasDefs[$keyName]['Type'] === 'date' ){
					if( empty($value) && empty($options['default']) )
						$default = date('Y-m-d');
					return $this->formInput($keyName,$value,'date',$options);
				}elseif( $datasDefs[$keyName]['Type'] === 'datetime' ){
					if( empty($value) && empty($options['default']) )
						$default = date('Y-m-d H:i:s');
					return $this->formInput($keyName,$value,'datetime',$options);
				}elseif( $datasDefs[$keyName]['Type'] === 'time' ){
					if( empty($value) && empty($options['default']) )
						$default = date('H-i:s');
					return $this->formInput($keyName,$value,'time',$options);
				}
			}
			if( preg_match('!\w+\s*\(\s*(\d+)\s*\)$!',$datasDefs[$keyName]['Type'],$match) ){
				if(! isset($options['maxlength']) )
					$options['maxlength'] = $match[1];
			}
			if( empty($options['values']) && in_array($datasDefs[$keyName]['Type'],array('tinyint(1)','tinyint(1) unsigned','bool'),'true') ){
				$options['values'] =  array(0=>langManager::msg('no'),1=>langManager::msg('yes'));
				if( empty($options['type']) ){
					$options['type']='selectbuttonset';
				}
				if( isset($options['maxlength']) && preg_match('!select|check|radio!i',$options['type']) )
					unset($options['maxlength']);
			}
			#- then textareas
			if( preg_match('!^\s*(text|blob)!',$datasDefs[$keyName]['Type'])){
				return $this->formInput($keyName,$value,empty($options['type'])?'textarea':$options['type'],$options);
			}
			#- then use a default text type (or userdefined type)
			return $this->formInput($keyName,$value,empty($options['type'])?'text':$options['type'],$options);
		}

	}
}
