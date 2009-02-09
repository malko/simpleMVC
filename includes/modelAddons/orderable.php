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
* @todo add onBeforeSave check to make some space if required
* @todo add set{OrderableField} to overloaded methods and to manage orderable field
* @todo see if there's way to optimise SQL queries by directly working on the database field.
*/
class orderableModelAddon extends modelAddon{

	static $_orderableField = 'ordre';
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		#- set position at instanciation time only on newly created elements
		if( $PK!==null )
			return;
		#- highestPos must be equal to existing elements count
		$this->modelInstance->{$this->getOrderableField()} = abstractModel::getModelCount($this->modelName);
	}
	/**
	* return the data field used to manage ordering
	* @return string
	*/
	public function getOrderableField(){
		return isset($this->modelInstance->_orderableField)?$this->modelInstance->_orderableField:self::$_orderableField;
	}

	/**
	* return next instance (regarding orderableField) of same model
	* @return abstractModel or null
	*/
	public function orderableGetNext(){
		$oField = $this->getOrderableField();
		#- check for element directly upside current
		return abstractModel::getFilteredModelInstance($this->modelName,array("WHERE $oField=?",$this->modelInstance->{$oField}+1));
	}
	/**
	* return previous instance (regarding orderableField) of same model
	* @return abstractModel or null
	*/
	public function orderableGetPrev(){
		$oField = $this->getOrderableField();
		#- check for element directly upside current
		return abstractModel::getFilteredModelInstance($this->modelName,array("WHERE $oField=?",$this->modelInstance->{$oField}-1));
	}
	/**
	* swap two models of same modelName position
	* @param abstractModel $dest the model to exchange position with
	* @return int new position
	*/
	public function orderableSwap(abstractModel $dest){
		#- check dest is the same modelType than current
		if( ! $dest instanceof $this->modelName ){
			throw new Exception('orderableModelAddon::orderableSwap() error while trying to swap to different kind of models');
		}
		$oField = $this->getOrderableField();
		$newO = $dest->{$oField};
		$dest->{$oField} = $this->modelInstance->{$oField};
		$this->modelInstance->{$oField} = $newO;
		$dest->save();
		$this->modelInstance->save();
		return $newO;
	}

	/**
	* moveup the current model regarding orderableField
	* @return int new position
	*/
	public function moveUp(){
		$oField = $this->getOrderableField();
		#- check for previous element
		$prev = $this->orderableGetPrev();
		#- if no previous elements there's no reason to move current up so don't bother and return
		if(! $prev instanceof $this->modelName)
			return $this->modelInstance->{$oField};
		return $this->orderableSwap($prev);
	}
	public function moveDown(){
		$oField = $this->getOrderableField();
		#- check for next element
		$next = $this->orderableGetNext();
		#- if no upper elements there's no reason to move current up so don't bother and return
		if(! $next instanceof $this->modelName)
			return $this->modelInstance->{$oField};
		return $this->orderableSwap($next);
	}

	/**
	* update all elements of same modelName position to avoid empty space in the orderableField
	*/
	public function onBeforeDelete(){
		#- get all instances with higher value in orderableField
		$oField = $this->getOrderableField();
		$collection = abstractModel::getFilteredModelInstances($this->modelName,array('WHERE '.$oField.'>?',$this->modelInstance->{$oField}));
		$collection->decrement($oField)->save();
		return;
	}
}
