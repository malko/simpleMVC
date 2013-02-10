<?php
/**
* this model addon permit to automaticly set an IP field for the extended models.
* you can specify the ip field in given models by adding them a $ipField public property
* @code
* // here's a sample to show how to add withCreateIpModelAddon to a model 'mymodel'
* // with field 'createAt' as creation time field in mysql datetime format.
*	class mymodel extends BASE_mymodel{
* 	static protected $filters = array();
* 	static protected $modelName = 'mymodel';
* 	static protected $modelAddons = array('withCreateIp');
* 	static public $_createIpField = 'IP';
*	}
* @endcode
* @class withCreateIpModelAddon
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
class withCreateIpModelAddon extends modelAddon{
	static public $_createIpField = 'ip';
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		#- set creation date on newly created elements
		if( $PK!==null )
			return;
		$fieldName = abstractModel::_getModelStaticProp($this->modelName,'_createIpField');
		if( null === $fieldName ){
			$fieldName = self::$_createIpField;
		}
		$this->modelInstance->_setData($fieldName,$_SERVER['REMOTE_ADDR'],true,true);
	}
}
