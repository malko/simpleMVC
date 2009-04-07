<?php
/**
* modelAddon to ease french / us date manipulation
* @package class-db
* @subpackage modelAddons
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @author jonathan gotti <jgotti at jgotti dot org>
* @since 2009-04
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision: 99 $
*            - $LastChangedBy: malko $
*            - $HeadURL$
class orderableModel extends BASE_orderableModel{
	//** must be a real dataField not a relName (neither hasOne or hasMany)
	public $_orderableField = 'ordre';
	//** may be a real dataField or a hasOne relName (only with a localField in relDef) but must not be an hasMany one
	//** this one is optional
  public $_orderableGroupField = false;
	static protected $modelAddons = array('orderable');
}
*/

class frDateModelAddon extends modelAddon{

	static protected $_internals=array();

	/**
	* constructor will set new modelInstance to last position
	*/
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		#- get all sql date fields
		if(! isset(self::$_internals[$this->modelName]) ){
			$datasDefs = abstractModel::_getModelStaticProp($this->modelName,'datasDefs');
			self::$_internals[$this->modelName] = array();
			foreach( $datasDefs as $k=>$v){
				if( preg_match('!^date(time)?$!',$v['Type'])){
					#- ~ check that there's no fr key already set
					if( isset($datasDefs["fr$k"]) || $this->modelInstance->_methodExists("setFr$k") || $this->modelInstance->_methodExists("getFr$k") )
						continue;
					self::$_internals[$this->modelName][] = 'setFr'.ucFirst($k);
					self::$_internals[$this->modelName][] = 'getFr'.ucFirst($k);
				}
			}
		}
		$this->overloadedModelMethods = self::$_internals[$this->modelName];
	}

	public function __call($m,$a){
		if( preg_match('!^getFr(.*)!i',$m,$match)){
			$field = abstractModel::_cleanKey($this->modelName,'datasDefs',$match[1]);
			return dateus2fr($this->modelInstance->{$field});
		}
		if( preg_match('!^setFr(.*)!i',$m,$match)){
			$field = abstractModel::_cleanKey($this->modelName,'datas',$match[1]);
			return $this->modelInstance->{$field} = datefr2us($a[0]);
		}
	}
}
