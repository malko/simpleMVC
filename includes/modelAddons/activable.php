<?php
/**
* modelAddon for activable items (manage boolean fields)
* add some methods for each activable fields (if not already exists)
* such as toggle[activableField], set[activableField], get[activableField]
* if only one field is given to activable also add methods:
* setActive, getActive, toggle if they don't already exists
* @package class-db
* @subpackage modelAddons
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @author jonathan gotti <jgotti at jgotti dot org>
* @since 2009-04
*	@changelog
*            - 2010-03-16 - make setter return modelInstance to conform with default setters methods behaviours
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision: 99 $
*            - $LastChangedBy: malko $
*            - $HeadURL$
class activableModel extends BASE_activableModel{
	//** list of activalbe fields must be real dataField not a relName (neither hasOne or hasMany)
	public $_activableFields = array('active');
	static protected $modelAddons = array('activable');
}
*/

class activableModelAddon extends modelAddon{

	static protected $_internals=array();

	/**
	* constructor will set new modelInstance to last position
	*/
	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		if(! isset(self::$_internals[$this->modelName]) ){
			self::$_internals[$this->modelName] = array();
			$testMethods = array('get','set','toggle');
			foreach($this->modelInstance->_activableFields as $f){
				$f = ucFirst($f);
				foreach($testMethods as $m){
					if( ! $this->modelInstance->_methodExists("$m$f") )
						self::$_internals[$this->modelName][] = "$m$f";
				}
			}
			if( count($this->modelInstance->_activableFields)===1 && ! isset($this->modelInstance->active)){
				$testMethods = array('toggle','setActive','getActive');
				foreach($testMethods as $m){
					if( ! $this->modelInstance->_methodExists($m) )
						self::$_internals[$this->modelName][] = $m;
				}
			}
		}
		$this->overloadedModelMethods = self::$_internals[$this->modelName];
	}

	public function __call($m,$a){
		if( preg_match('!^([sg]et|toggle)(.*)$!i',$m,$match)){
			$field = abstractModel::_cleanKey($this->modelName,'datas',$match[2]);
			if(false === $field && strtolower($match[2])==='active')
				$field = $this->modelInstance->_activableFields[0];
			switch($match[1]){
				case 'set':
					if( ! isset($a[0]))
						$a[0] = 1;
					$val = $a[0]?1:0;
					if( $val !== $this->modelInstance->datas[$field])
						$this->modelInstance->_setData($field,$val?1:0,true);
					return $this->modelInstance;
				case 'get':
					return $this->modelInstance->datas[$field]?1:0;
				case 'toggle':
					return $this->{"set$field"}($this->modelInstance->datas[$field]?0:1);
			}
		}
	}
}
