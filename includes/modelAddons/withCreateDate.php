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
* 	static protected $modelAddons = array('withCreateDate');
* 	public $createDateField = 'createAt';
* 	public $createDateStr   = 'Y-m-d- H:i:s';
*	}
* @endcode
* @class withCreateDateModelAddon
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
class withCreateDateModelAddon extends modelAddon{
	public $createDateField = 'createDate';
	public $createDateStr   = 'Y-m-d H:i:s';
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		#- set creation date on newly created elements
		if( $PK!==null )
			return;
		if( isset($this->modelInstance->createDateField) )
			$this->createDateField = $this->modelInstance->createDateField;
		if( isset($this->modelInstance->createDateStr) )
			$this->createDateStr = $this->modelInstance->createDateStr;
		#- use _setData() method to avoid needSave modification and to bypass datas filtering
		$this->modelInstance->_setData($this->createDateField,date($this->createDateStr),true,true);
	}
}
