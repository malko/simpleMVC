<?php

class modelFormInput_viewHelper extends abstractViewHelper{


	function modelFormInput($modelName, $keyName, array $options=array()){

		$relsDefs =  abstractModel::modelHasRelDefs($modelName,null,true);
		$datasDefs = abstractModel::_getModelStaticProp($modelName,'datasDefs');

		if(! isset($options['label'])){
			$options['label'] = langManager::msg($keyName);
		}

		# commence par checker les relations
		if( isset($relsDefs['hasOne'][$keyName]) ){
			if( $modelName instanceof abstractModel){
				$value = $modelName->{$keyName}->PK;
			}elseif(isset($relsDefs['hasOne'][$keyName]['localField']) && isset($datasDefs[$relsDefs['hasOne'][$keyName]['localField']]['Default']) ){
				$value = $datasDefs[$relsDefs['hasOne'][$keyName]['localField']]['Default'];
			}
			if( empty($options['values']) ){
				eval ('$choices = '.$relsDefs['hasOne'][$keyName]['modelName'].'::getAllInstances();');
				$options['values'][0] = '--- '.$options['label'].' ---';
				foreach($choices as $ck=>$cv)
					$options['values'][$ck]=$cv->__toString();
			}

			return $this->formInput($keyName,$value,'select',$options);
		}elseif(isset($relsDefs['hasMany'][$keyName]) ){

		}

		if( $keyName === 'PK' || $keyName === abstractModel::_getModelStaticProp($modelName,'primaryKey') ){
			if( ! $modelName instanceof abstractModel )
				return '';
			return $this->formInput($keyName,$modelName->PK,'hidden');
		}
		#- try to get def from datas array
		if( !empty($datasDefs[$keyName]) ){
			$value = ( $modelName instanceof abstractModel )?$modelName->{$keyName}:$datasDefs[$keyName]['Default'];
			$datasDefs[$keyName]['Type'] = strtolower($datasDefs[$keyName]['Type']);
			#- check for enum types
			if( preg_match('!^\s*enum!',$datasDefs[$keyName]['Type'])){
				if(empty($options['values']) &&  method_exists($modelName,'get'.$keyName.'PossibleValues') ){
					eval('$values = '.(is_string($modelName)?$modelName:$modelName->modelName).'::get'.$keyName.'PossibleValues();');
					foreach($values as $v)
						$options['values'][$v] = $v;
				}
				return $this->formInput($keyName,$value,empty($options['type'])?'select':$options['type'],$options);
			}
			if( preg_match('!\s*\w+\s*\(\s*(\d+)\s*\)\s*$!',$datasDefs[$keyName]['Type'],$match) ){
				if(! isset($options['maxlength']) )
					$options['maxlength'] = $match[1];
			}
			if( empty($options['values']) && in_array($datasDefs[$keyName]['Type'],array('tinyint(1)','bool'),'true') ){
				$options['values'] =  array(0=>langManager::msg('no'),1=>langManager::msg('yes'));
				if( empty($options['type']) )
					$options['type']='radio';
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