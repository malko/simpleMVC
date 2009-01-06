<?php
/**
* @package simpleMVC
* @subPackage helpers
* @class modelFormInput_viewHelper
* @changelog
*            - 2009-01-05 - add timepicker and datetimepicker detection
*            - 2008-11-07 - add possibility to skip automated date type setting
*            - 2008-10-30 - add svninfos and put it uptodate with local version
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
  *                           - @see formInput for other supported options depending the type of field to edit.
	*
	*/
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
				if( $relsDefs['hasOne'][$keyName]['relType'] !== 'dependOn' ){
					$options['values'][0] = '--- '.$options['label'].' ---';
				}
				foreach($choices as $ck=>$cv)
					$options['values'][$ck]=$cv->__toString();
			}

			if(! empty($options['value']) )
				$value = $options['value'];
			return $this->formInput($keyName,$value,'select',$options);
		}elseif(isset($relsDefs['hasMany'][$keyName]) ){
			//-- @todo voir si on gere ou non les relations hasMany
		}

		if( $keyName === 'PK' || $keyName === abstractModel::_getModelStaticProp($modelName,'primaryKey') ){
			if( ! $modelName instanceof abstractModel )
				return '';
			return $this->formInput($keyName,$modelName->PK,'hidden');
		}
		#- try to get def from datas array
		if( !empty($datasDefs[$keyName]) ){
			if(isset($options['value']) )
				$value = $options['value'];
			else
				$value = ( $modelName instanceof abstractModel )?$modelName->{$keyName}:$datasDefs[$keyName]['Default'];
			$datasDefs[$keyName]['Type'] = trim(strtolower($datasDefs[$keyName]['Type']));
			#- check for enum types
			if( preg_match('!^enum!',$datasDefs[$keyName]['Type'])){
				if(empty($options['values']) &&  method_exists($modelName,'get'.$keyName.'PossibleValues') ){
					eval('$values = '.(is_string($modelName)?$modelName:$modelName->modelName).'::get'.$keyName.'PossibleValues();');
					foreach($values as $v)
						$options['values'][$v] = $v;
				}
				return $this->formInput($keyName,$value,empty($options['type'])?'select':$options['type'],$options);
			}
			if(empty($options['type']) ){
				if( $datasDefs[$keyName]['Type'] === 'date' )
					return $this->formInput($keyName,$value,'date',$options);
				if( $datasDefs[$keyName]['Type'] === 'datetime' )
					return $this->formInput($keyName,$value,'datetime',$options);
				if( $datasDefs[$keyName]['Type'] === 'time' )
					return $this->formInput($keyName,$value,'time',$options);
			}
			if( preg_match('!\w+\s*\(\s*(\d+)\s*\)$!',$datasDefs[$keyName]['Type'],$match) ){
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
