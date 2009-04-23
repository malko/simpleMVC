<?php
/**
* modelAddon to ease french / us date manipulation
* it add some methods to each date(time) fields in models::$datas
* for a field named mydate added methods will be:
* - setFrMydate('dd/mm/yyyy') to set a date so you can do $modelInstance->frMydate='dd/mm/yyyy' too
* - getFrMydate() return mydate in 'dd/mm/yyyy' format so you can use $modelInstance->frMydate to get it
* - strftimeFrMydate('strftime format string') same as using strftime (will try to change LC_TIME and restore it )
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
* @changelog
*            - 2009-04-09 - add strftimeFr[dateField] methods
class frDateModel extends BASE_frDateModel{
	static protected $modelAddons = array('frDate');
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
					self::$_internals[$this->modelName][] = 'strftimeFr'.ucFirst($k);
					self::$_internals[$this->modelName][] = 'getFr'.ucFirst($k);
				}
			}
		}
		$this->overloadedModelMethods = self::$_internals[$this->modelName];
	}

	public function __call($m,$a){
		if( preg_match('!^getFr(.*)$!i',$m,$match)){
			$field = abstractModel::_cleanKey($this->modelName,'datasDefs',$match[1]);
			return dateus2fr($this->modelInstance->{$field});
		}
		if( preg_match('!^setFr(.*)$!i',$m,$match)){
			$field = abstractModel::_cleanKey($this->modelName,'datas',$match[1]);
			return $this->modelInstance->{$field} = datefr2us($a[0]);
		}
		if( preg_match('!^strftimeFr(.*)$!i',$m,$match)){
			$field = abstractModel::_cleanKey($this->modelName,'datas',$match[1]);
			$loc = setlocale(LC_TIME,0);
		 	$tmpLoc = setlocale(LC_TIME,array('fr_FR.utf8','fr_FR.UTF8','fr_FR.utf-8','fr_FR.UTF-8','fr_FR','fr'));
			$str = strftime($a[0],strtotime($this->modelInstance->{$field}));
			if(preg_match('!utf-?8!i',$tmpLoc))
				$str =  utf8_encode($str);
			setlocale(LC_TIME,$loc);
      return $str;
		}
	}
}
