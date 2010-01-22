<?php
/**
* modelAddon to ease french / us date manipulation
* it add some methods to each date(time) fields in models::$datas
* for a field named mydate added methods will be:
* - setFrMydate('dd/mm/yyyy') to set a date so you can do $modelInstance->frMydate='dd/mm/yyyy' too
* - getFrMydate() return mydate in 'dd/mm/yyyy' format so you can use $modelInstance->frMydate to get it
* - strftimeMydate('strftime format string') same as using strftime on strtotimed value (won't try to change LC_TIME )
* - strftimeFrMydate('strftime format string') same as using strftime on strtotimed value (will try to change LC_TIME and restore it )
* - dateMydate('strftime format string') same as using date on strtotimed value
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
*            - 2010-01-22 - error correction in datefr2us when used with dateTime format
*            - 2009-04-09 - add strftime[dateField] and date[dateField] methods
*                         - import datefr2us and dateus2fr methods as internals to skip dependencies on fx-common
*            - 2009-04-09 - add strftimeFr[dateField] methods
class frDateModel extends BASE_frDateModel{
	static protected $modelAddons = array('frDate');
}
*/

class frDateModelAddon extends modelAddon{

	static protected $_internals=array();

	/**
	* constructor will set overloaded methods
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
					self::$_internals[$this->modelName][] = 'strftime'.ucFirst($k);
					self::$_internals[$this->modelName][] = 'strftimeFr'.ucFirst($k);
					self::$_internals[$this->modelName][] = 'date'.ucFirst($k);
					self::$_internals[$this->modelName][] = 'getFr'.ucFirst($k);
				}
			}
		}
		$this->overloadedModelMethods = self::$_internals[$this->modelName];
	}

	public function __call($m,$a){
		if(! preg_match('!^(getFr|setFr|strftime(?:Fr)?|date(?:Fr)?)(.*)$!i',$m,$match) )
			return false;
		if( false===($field = abstractModel::_cleanKey($this->modelName,'datas',$match[2])) )
			return false;
		$m = strtolower($match[1]);
		switch($m){
			case 'getfr':
				return self::dateus2fr($this->modelInstance->{$field});
			case 'setfr':
				return $this->modelInstance->{$field} = self::datefr2us($a[0]);
			case 'date':
				return date($a[0],strtotime($this->modelInstance->{$field}));
			case 'strftime':
			case 'strftimefr':
				$timeStamp = strtotime($this->modelInstance->{$field});
				if( $m === 'strftime')
					return strftime($a[0],$timeStamp);
				return self::_frLocalisedDateFuncCall('strftime',$a[0],$timeStamp);
		}
	}

	static public function _frLocalisedDateFuncCall($dateFunc,$param=null,$time=null){
		static $locales;
		if(! isset($locales) )
			$locales = array('fr_FR.utf8','fr_FR.UTF8','fr_FR.utf-8','fr_FR.UTF-8','fr_FR','fr');
		$loc = setlocale(LC_TIME,0);
		if(! in_array($loc,$locales,true) )
			$tmpLoc = setlocale(LC_TIME,$locales);
		$res = $dateFunc($param,$time);
		if( isset($tmpLoc) )
			setlocale(LC_TIME,$loc);
		return preg_match('!utf-?8!i',isset($tmpLoc)?$tmpLoc:$loc)?utf8_encode($res):$res;
	}
	static public function dateus2fr($date,$noTime=false){
		if( empty($date) )
			return '00/00/0000';
		if(! strpos($date,' '))
			return implode('/',array_reverse(preg_split('!/|-!',$date)));
		list($date,$time) = explode(' ',$date);
		return self::dateus2fr($date).($noTime?'':' '.$time);
	}
	static public function datefr2us($date,$noTime=false){
		if( empty($date) )
			return '0000-00-00';
		if(! strpos($date,' '))
			return implode('-',array_reverse(preg_split('!/|-!',$date)));
		list($date,$time) = explode(' ',$date);
		return self::datefr2us($date).($noTime?'':' '.$time);
	}

}
