<?php
/**
* this model addon permit to automaticly set a createDate field for the extended models.
* you can specify the createDate field in given models by adding them a $createDateField public property
* you may also specify the string to use in the date function to fill the field.
* @code
* // here's a sample to show how to add withCreateDateModelAddon to a model 'mymodel'
* // with field 'createAt' as creation time field in mysql datetime format.
*	class mymodel extends BASE_mymodel{
* 	static protected $filters = array();
* 	static protected $modelName = 'mymodel';
* 	static protected $modelAddons = array('withUpdateDate');
* 	static public $_updateDateFields = array('dateUpdate'=>'Y-m-d H:i:s');
*	}
* @endcode
* @class withUpdateDateModelAddon
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
class withUpdateDateModelAddon extends modelAddon{
	static public $_updateDateFields = array('dateUpdate'=>'Y-m-d H:i:s');
	static private $_internals = array();

	function _initModelType(){
		$tmp = abstractModel::_getModelStaticProp($this->modelName,'_updateDateFields');
		self::$_internals[$this->modelName] = $tmp?$tmp:self::$_updateDateFields;
	}
	/** don't forget to call this method if you set an onBeforeSave method on the model class	*/
	function onBeforeSave(){
		foreach( self::$_internals[$this->modelName] as $fld => $dateStr){
			$this->modelInstance->{$fld} = date($dateStr);
		}
	}

}
