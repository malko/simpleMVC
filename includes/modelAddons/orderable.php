<?php
/**
* modelAddon to manage ease management of models with an orderable field.
* add moveUp, moveDown method to the managed model regarding its orderable field.
* @package class-db
* @subpackage modelAddons
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @author jonathan gotti <jgotti at jgotti dot org>
* @since 2009-02
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog 
*            - 2009-03-19 - major rewrite to support grouping of orderable models
* @todo add onBeforeSave check to make some space if required
* @todo reflexion on the need to add set{OrderableField} to overloaded methods
* @todo see if there's way to optimize SQL queries by directly working on the database field.
* @todo make some more methods: moveToPosition, getLast, getFirst ...
* @example sample orderableModelClass
class orderableModel extends BASE_orderableModel{
	//** must be a real dataField not a relName (neither hasOne or hasMany)
	public $_orderableField = 'ordre';
	//** may be a real dataField or a hasOne relName (only with a localField in relDef) but must not be an hasMany one
	//** this one is optional
  public $_orderableGroupField = false;
	static protected $modelAddons = array('orderable');
}
*/

class orderableModelAddon extends modelAddon{

	/** must be a real dataField not a relName (neither hasOne or hasMany) */
	public $_orderableField = 'ordre';
	/** may be a real dataField or a hasOne relName (only with a localField in relDef) but must not be an hasMany one */
  public $_orderableGroupField = false;

	/**
	* constructor will set new modelInstance to last position
	*/
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		list($oField,$gField) = $this->_getOrderableFields();

		if($gField!==false) #- overload group field setter
			$this->overloadedModelMethods[]='set'.$this->modelInstance->_orderableGroupField;

		#- if this isn't a newly created instance we don't set anything more
		if( null !==$PK)
			return;
		#- we set position for newly created elements
		if(false===$gField) {
			$this->modelInstance->{$oField} = abstractModel::getModelCount($this->modelName);
		}else{
			/*We don't know the group now so we set the position based on the default group value */
			$this->modelInstance->{$oField} = abstractModel::getModelCount(
				$this->modelName,
				array('WHERE '.$gField.'=?', $this->modelInstance->datas[$gField])
			);
		}
	}

	###--- INTERNAL METHODS ---###
	/**
	* dynamic overload of set[$_orderableGroupField]() method
	*/
	public function __call($m,$a=null) {
		$gField = $this->_getOrderableGroupField();
		if(false===$gField)
			throw new Exception(__class__."::$m() unknow method.");
		if(preg_match('/^set(.*)$/',$m,$match)) {
			if(abstractModel::_cleanKey($this->modelName,'hasOne|datas',$match[1])===$gField){
				return call_user_func(array($this,'setOrderableGroupFieldValue'),$gField,$a[0]);
			}
		}
		throw new Exception(__class__."::$m() unknow method.");
	}
	/**
	* return the data field used to manage ordering
	* @return string
	*/
	public function _getOrderableField(){
		return empty($this->modelInstance->_orderableField)?$this->_orderableField:$this->modelInstance->_orderableField;
	}
  /**
	* return the optional data field  used to group ordering
	* @return string or false if group  no set
	*/
	public function _getOrderableGroupField(){
		if(! isset($this->modelInstance->_orderableGroupField) )
			return false; //false
		$gField = $this->modelInstance->_orderableGroupField;
		#- first check in datasDefs
		$datasDefs = abstractModel::_getModelStaticProp($this->modelName,'datasDefs');
		if( isset($datasDefs[$gField]) )
			return $gField;
		#- then check for hasOne relation with localField defined
		$hasOnes = abstractModel::_getModelStaticProp($this->modelName,'hasOne');
		if( isset($hasOnes[$gField]) && isset($hasOnes[$gField]['localField']) )
			return $hasOnes[$gField]['localField'];
		throw new Exception(__class__.'::'.__method__.'() unable to find a valid groupField to rely on, please check '.$this->modelName.'::$_orderableGroupField setting.' );

		#- ~ return empty($this->modelInstance->_orderableGroupField)?$this->_orderableGroupField:$this->modelInstance->_orderableGroupField;
	}
	/**
	* @return array(_orderableField,_orderableGroupField)
	*/
	public function _getOrderableFields(){
		return array($this->_getOrderableField(),$this->_getOrderableGroupField());
	}
	/**
	* internally called when setting the value for group field in modelInstance
	*@param $gField, $groupValue
	*@return int $groupValue (the primaryKey of $groupValue if a related hasOne model)
	*/
	public function setOrderableGroupFieldValue($gField,$groupValue=null) {
		$currentGroupValue = $this->modelInstance->{$gField};
		if($currentGroupValue instanceof abstractModel)
			$currentGroupValue = $currentGroupValue->PK;
		if($groupValue instanceof abstractModel)
			$groupValue = $groupValue->PK;
		if($currentGroupValue===$groupValue) //equal so no need to do anything
			return $groupValue;
		//we have to do something on it
		//check if there no element upper to this one
		list($oField,$gField) = $this->_getOrderableFields();
		abstractModel::getFilteredModelInstances(
			$this->modelName,
			array("WHERE $gField = ? AND $oField > ?",$currentGroupValue,$this->modelInstance->{$oField})
		)->decrement($oField)->save();

		$this->modelInstance->{$oField} = abstractModel::getModelCount($this->modelName,array("WHERE $gField=?",$groupValue));
		$ret = $this->modelInstance->_setData($gField,$groupValue,true);
		$this->modelInstance->save();
		return $ret;
	}
	/**
	*	@private
	* @param $returnType one of instance|PK|position
	*/
	private function _getElementByPos($pos=0,$returnType='instance'){
		list($oField,$gField) = $this->_getOrderableFields();
		switch($returnType){
			case 'instance':
			case 'PK':
				$field = $this->modelInstance->primaryKey;
				break;
			case 'position':
				$field = $oField;
				break;
		}
		if( false===$gField){
			$filter = array("WHERE $oField=?",$pos);
		}else{
			$groupValue = $this->modelInstance->datas[$gField];
			$filter = array("WHERE $gField=? AND $oField=?",$groupValue,$pos);
		}
		$res = $this->dbAdapter->select_value($this->modelInstance->tableName,$field,$filter);
		return ($returnType!=='instance')?$res:abstractModel::getModelInstance($this->modelName,$res);
	}

	###--- NAVIGATING THRUE ELEMENTS ---###
	public function orderableGetPos(){
		return $this->modelInstance->{$this->_getOrderableField()};
	}
	/**
	* return next instance (regarding orderableField) of same model
	* @return abstractModel/null  or primaryKey/false depending on $returnPK value
	*/
	public function orderableGetNext(){
		return $this->_getElementByPos($this->orderableGetPos()+1);
	}
	/**
	* return next instance primaryKey or false if this is the last elements
	* @return mixed or false
	*/
	public function orderableGetNextPos(){
		return $this->_getElementByPos($this->orderableGetPos()+1,'PK');
	}
	/**
	* return next instance position or false if this is the last elements
	* @return int or false
	*/
	public function orderableGetNextPK(){
		return $this->_getElementByPos($this->orderableGetPos()+1,'position');
	}
	/**
	* return previous  instance (regarding orderableField) of same model
	* @return abstractModel/null  or primaryKey/false depending on $returnPK value
	*/
	public function orderableGetPrev(){
		$pos = $this->orderableGetPos();
		return ($pos===0)?null:$this->_getElementByPos($pos-1);
	}
	/**
	* return previous instance primaryKey or false if this is the last elements
	* @return mixed or false
	*/
	public function orderableGetPrevPK(){
		$pos = $this->orderableGetPos();
		return ($pos===0)?false:$this->_getElementByPos($pos+1,'PK');
	}
	/**
	* return prev instance position or false if this is the last elements
	* @return int or false
	*/
	public function orderableGetPrevPos(){
		$pos = $this->orderableGetPos();
		return ($pos===0)?false:$this->_getElementByPos($pos+1,'position');
	}

	public function orderableGetFirst(){
		$pos = $this->orderableGetPos();
		return ($pos===0)?$this->modelInstance:$this->_getElementByPos(0);
	}
	public function orderableGetFirstPK(){
		$pos = $this->orderableGetPos();
		return ($pos===0)?$this->modelInstance:$this->_getElementByPos(0,'PK');
	}
	public function orderableGetFirstPos(){
		$pos = $this->orderableGetPos();
		return ($pos===0)?$this->modelInstance:$this->_getElementByPos(0,'position');
	}

	public function orderableGetLast(){
		$PK = $this->orderableGetLastPK();
		return (false===$PK)?null:abstractModel::getModelInstance($this->modelName,$PK);
	}
	public function orderableGetLastPK(){
		list($oField,$gField) = $this->_getOrderableFields();
		$tableName = $this->modelInstance->tableName;
		if( false===$gField){
			$filter = "WHERE $oField=(SELECT max($oField) FROM $tableName)";
		}else{
			$groupValue = $this->modelInstance->datas[$gField];
			$filter = array("WHERE $gField=1? AND $oField=(SELECT max($oField) FROM $tableName WHERE $gField=1?)",$groupValue);
		}
		return $this->dbAdapter->select_value($tableName,$this->modelInstance->primaryKey,$filter);
	}
	public function orderableGetLastPos(){
		list($oField,$gField) = $this->_getOrderableFields();
		$tableName = $this->modelInstance->tableName;
		if( false===$gField){
			$filter = "WHERE $oField=(SELECT max($oField) FROM $tableName)";
		}else{
			$groupValue = $this->modelInstance->datas[$gField];
			$filter = array("WHERE $gField=1? AND $oField=(SELECT max($oField) FROM $tableName WHERE $gField=1?)",$groupValue );
		}
		return $this->dbAdapter->select_value($tableName,$oField,$filter);
	}


	###--- MOVING ELEMENTS ---###
	/**
	* swap two models of same modelName position
	* @note: this cause a save() to be both current and $dest modelInstances
	* @param abstractModel $dest the model to exchange position with
	* @return this->modelInstance for method chaining
	*/
	public function orderableSwap(abstractModel $dest){
		#- check dest is the same modelType than current
		if( ! $dest instanceof $this->modelName ){
			throw new Exception('orderableModelAddon::orderableSwap() error while trying to swap to different kind of models');
		}
		list($oField,$gField) = $this->_getOrderableFields();
		$curPos = $this->modelInstance->{$oField};
		$newPos = $dest->{$oField};
		$cur = $this->modelInstance;
		if( false !== $gField){
			$curGrp = $cur->datas[$gField];
			$newGrp = $dest->datas[$gField];
			if( $curGrp !== $newGrp){
				$dest->_setData($cur->_orderableGroupField,$curGrp,true);
				$cur->_setData($cur->_orderableGroupField,$newGrp,true);
			}
		}
		$dest->{$oField} = $curPos;
		$cur->{$oField}  = $newPos;
		$dest->save();
		return $cur->save();
	}
	/**
	* moveup the current model regarding orderableField
	* @note: this cause a save() to be called on current and previous modelInstance
	* @return $this->modelInstance for method chaining
	*/
	public function moveUp(){
		$prev = $this->orderableGetPrev();
		#- if no previous elements there's no reason to move current up so don't bother and return
		if(! $prev instanceof $this->modelName)
			return $this->modelInstance;
		return $this->orderableSwap($prev);
	}
	/**
	* moveup the current model regarding orderableField
	* @note: this cause a save() to be called on current and next modelInstance
	* @return $this->modelInstance for method chaining
	*/
	public function moveDown(){
		#- check for next element
		$next = $this->orderableGetNext();
		#- if no upper elements there's no reason to move current up so don't bother and return
		if(! $next instanceof $this->modelName)
			return $this->modelInstance;
		return $this->orderableSwap($next);
	}

	###--- SOME CHECKS ON DELETE ELEMENTS ---###
	/**
	* update all elements of same modelName position to avoid empty space in the orderableField
	*/
	public function onBeforeDelete(){
		list($oField,$gField) = $this->_getOrderableFields();
		#- get all instances with higher value in orderableField
		$pos = $this->modelInstance->{$oField};
		if(false === $gField){
			$collection = abstractModel::getFilteredModelInstances($this->modelName,array('WHERE '.$oField.'>?',$pos));
		}else{
			$gValue = $this->modelInstance->datas[$gField];
			$collection = abstractModel::getFilteredModelInstances($this->modelName,array('WHERE '.$oField.'>? AND '.$gField.'=?',$pos,$gValue));
		}
		$collection->decrement($oField)->save();
		return;
	}
}
