<?php
/**
* generic collection to ease work on array.
* this class is intended to work as a list of similar items like the results of a database select query for example.
* @changelog
* - 2012-10-11 - smvcCollection::getIndexedBy() correctly return a smvcCollection
* - 2012-09-14 - smvcCollection::getIndexedBy() method now handle $onlyDatas parameter
*              - new methods removeCol() , getProperties(),__toString(), isEmpty()
*              - keys correcly return an array as a collection mean nothing
* - 2011-11-02 - smvcCollection::filter() method can now take a value to compare as filter + add third parameter strict compare
*/

class smvcCollection extends arrayObject{

	/** internal properties used at sort time */
	private $_sortBy       = null;
	private $_sortType     = null;
	private $_sortReversed = false;

	static private $_arrayMethods = array(
		'change_key_case' => 'array_change_key_case'
		/** @method changeKeyCase */
		,'changekeycase'  => 'array_change_key_case'
		/** @method chunk */
		,'chunk'          => 'array_chunk'
		/** @method keys */
		,'keys'           => 'array_keys'
		/** @method values */
		,'values'         => 'array_values'
		/** @method merge */
		,'merge'          => 'array_merge'
		/** @method pop */
		,'pop'            => 'array_pop'
		/** @method push */
		,'push'           => 'array_push'
		/** @method shift */
		,'shift'          => 'array_shift'
		/** @method unshift */
		,'unshift'        => 'array_unshift'
		/** @method rand */
		,'rand'           => 'array_rand'
		/** @method reverse */
		,'reverse'        => 'array_reverse'
	);
	static private $_itemArrayMethods = array(
		/** @method flip */
		'flip' => 'array_flip'
	);
	#- static private $_keyArrayMethods = array(
		#- 'kmap' => 'array_map'
	#- );
		#- 'count_values' => 'array_count_values'


	/**
	* @param mixed $datas array or smvcCollection
	* @return smvcCollection
	*/
	static function init($datas=null){
		return $datas ? new smvcCollection($datas) : new smvcCollection();
	}

	public function keys(){
		return array_keys($this->getArrayCopy());
	}

	/**
	 * if called method start with an "_" then the result will be reassign to the current collection instead of returning a new one
	 * this method is not intended to be called directly
	 * @param string $m method to call
	 * @param array $a method parameters
	 * @return mixed  depending on the called method
	 */
	function __call($m,$a){
		$_m = strtolower($m);
		$_reassign = false;
		if( $_m[0] === '_' ){
			$_m = substr($_m,1);
			$_reassign = true;
		}

		foreach($a as &$av){
			if( $av instanceof self ){
				$av = (array) $av;
			}
		}
		//-- basic array methods
		if( isset(self::$_arrayMethods[$_m]) ){
			$_m = self::$_arrayMethods[$_m];
			if( $_m[0] !=='_' ){
				$pushArg = 'array_unshift';
			}else{
				$_m = substr($_m,1);
				$pushArg = 'array_push';
			}
			if(! is_array($_m) ){
				$pushArg($a,$this->getArrayCopy());
			}else{
				$methods = $_m;
				$_m = $pushArg($methods);
				$_a = $this;
				foreach($methods as $method){
					$_a = $_a->$method();
				}
				array_unshift($a,$_a->getArrayCopy());
			}
			if( PHP_VERSION >= 5.3 ){
				$a[0] = &$a[0];
			}
			$res = call_user_func_array($_m,$a);
		}else if( isset(self::$_itemArrayMethods[$_m]) ){ //-- aggregate array methods
			$_m = self::$_itemArrayMethods[$_m];
			$res = array();
			foreach($this as $k=>$v){
				$_a = $a;array_unshift($_a,$v);
				$res[$k] = call_user_func_array($_m,$_a);
			}
		}elseif( method_exists($this,$_m) ){
			$res = call_user_func_array(array($this,$_m),$a);
		}else{
			throw new BadMethodCallException(__class__."::$m method doesn't exist");
		}

		if( ! (is_array($res) || $res instanceof self)  )
			return $res;
		if( $_reassign ){
			$this->exchangeArray((array) $res);
			return $this;
		}
		return smvcCollection::init($res);

	}

	function __get($key){
		return $this->getCol($key);
	}

	/**
	* return an array of each values corresponding to given key in each items in collection
	* @param string $key key you want to get values for
	* @return array
	*/
	function getCol($key){
		$res = array();
		foreach($this as $k=>$v){
			$res[$k] = $v[$key];
		}
		return $res;
	}

	/**
	 * return a copy of the collection with given column removed
	 * @param string $key key you want to remove from items
	 * @return smvcCollection
	 */
	function removeCol($key){
		$res = array();
		foreach($this as $k=>$v){
			$res[$k] = $v;
			unset($res[$k][$key]);
		}
		return smvcCollection::init($res);
	}

	/**
	* return a collection of associative array with each model properties
	* @param mixed $propertiesNames list of propery to get from
	* @param string $concatSeparator if $concatSeparator is passed then will implode each model results using given string as separator
	* @param smvcCollection
	*/
	function getProperties($properties){
		if(! is_array($properties )){
			$properties = preg_split('![,|;]!',$properties);
		}
		$properties = array_flip($properties);
		$res = array();
		foreach($this as $k=>$v){
			$res[$k] = array_intersect_key($v,$properties);
		}
		return smvcCollection::init($res);
	}

	/**
	* self explanatory
	* @return bool
	*/
	function isEmpty(){
		return empty($this);
	}

	/**
	* return an smvcCollection indexed by the given item Key.
	* In case of multiple items matching the same value for the given property then the default is to return a
	* smvcCollection of thoose matching items, you can choose to keep only one of thoose matching items by passing
	* $forceSingleValue as true (arbitrary determined).
	* @param string $itemKey     name of the property we whant to use as index
	* @param bool   $forceSingleValue on multiple matching models for a given property value return only one of thoose.
	* @param bool   $onlyDatas   if true will remmove $itemKey property from items in the collection.
	*                            If resulting items are composed of a single property after removing the Key, then they will be replaced by thoose single property value.
	* @return smvcCollection
	*/
	public function getIndexedBy($itemKey,$forceSingleValue=false,$onlyDatas=false){
		if( $this->count() <1)
			return self::init();
		$res = array();
		foreach($this as $k=>$item){
			$key = $item[$itemKey];
			if( $onlyDatas ){
				unset($item[$itemKey]);
				if( count($item) === 1){
					$item = current($item);
				}
			}
			if( ! isset($res[$key]) ){ // key not already assigned put item in it
				$res[$key] = $item;
			}elseif( $forceSingleValue ){ // key already assigned ignore others similar key
				continue;
			}elseif( $res[$key] instanceof smvcCollection ){ // key already get multiple assigned values append the current one
				$res[$key][] = $item;
			}else{ // key already assigned to a single value make it a collection with old and new value
				$res[$key] = smvcCollection::init(array($res[$key],$item));
			}
		}
		return self::init($res);
	}

	/**
	* assign new keys to all items in collection
	* @param $keys
	* @return smvcCollection
	*/
	function combine($keys){
		$res = array();
		foreach( $this as $k=>$item){
			$res[$k] = array_combine((array) $keys,$item);
		}
		return  self::init($res);
	}

	/**
	* return number of occurences of each values grouped by item keys
	* @param string $countKey return count only for the specified item key
	* @return array
	*/
	function countValues($countKey=null){
		$res = array();
		if( $countKey ){
			return array_count_values($this->getCol($countKey));
		}
		foreach( $this as $item){
			foreach($item as $k=>$v){
				$res[$k][$v] = isset($res[$k][$v])?$res[$k][$v]+1:1;
			}
		}
		return $res;
	}

	/**
	* @param string $itemKey     on wich item key the filter has to apply
	* @param mixed  $filter      filter may be a callback function that will test values and has to return true/false
	*                            or a value to test against the given itemKey value
	*                            or leave it null to test for non empty values
	* @param bool $strictCompare only used when $filter is a test value or null and then will do a strict comparison
	*                            (in case of null it will return only items with null itemKey)
	* @return smvcCollection
	*/
	function filter($itemKey,$filter=null,$strictCompare=false){
		$res = array();
		$isCallable = is_callable($filter);
		foreach($this as $k=>$item){
			if( null===$filter && ! $strictCompare){
				if( !empty($item[$itemKey]) ){
					$res[$k] = $item;
				}
			}elseif( ! $isCallable ){
				if( $strictCompare ){
					if( $item[$itemKey] === $filter){
						$res[$k] = $item;
					}
				}else if( $item[$itemKey] == $filter ){
					$res[$k] = $item;
				}
			}elseif( call_user_func($filter,$item[$itemKey]) ){
				$res[$k] = $item;
			}
		}
		return self::init($res);
	}

	/**
	* like array_map but for smvcCollection.
	* you can also specify the item key you want to apply callBack on.
	* @param callable $callBack     any valid callable as define in call_user_func
	* @param string   $itemKey optionnal item key you want to apply callback on
	* @return
	*/
	public function map($callBack,$itemKey=null){
		$res = array();
		if( null === $itemKey ){
			foreach($this as $k=>$item)
				$res[$k] = call_user_func($callBack,$item);
		}else{
			foreach($this as $k=>$item){
				$res[$k] = $item ;
				$res[$k][$itemKey] = call_user_func($callBack,$item[$itemKey]);
			}
		}
		return $res;
	}

	/**
	* return max value of given property for all items in collection
	* @param string $itemKey name of the field we want max value
	* @return mixed
	*/
	public function max($itemKey,$comparisonType='std'){
		if( $this->count() < 1)
			return 0;
		$lastItem = smvcCollection::init($this)->sort($itemKey,$comparisonType)->pop();
		return $lastItem[$itemKey];
	}
	/**
	* return min value of given property for all items in collection
	* @param string $itemKey name of the field we want min value
	* @return mixed
	*/
	public function min($itemKey,$comparisonType='std'){
		if( $this->count() < 1)
			return 0;
		$lastItem = smvcCollection::init($this)->rsort($itemKey,$comparisonType)->pop();
		return $lastItem[$itemKey];
	}

	/**
	* sort collection by given datas property name
	* @param str $sortBy   property to use to sort the collection
	* @param str $sortType type of comparison to use can be one of
	*                      - std (default) use standard > or < comparison
	*                      - nat  use natural order comparison case sensitive (strnatcmp)
	*                      - natc use natural order comparison case insensitive (strnatcasecmp)
	*                      - bin  use binary string comparison case sensitive(strcmp)
	*                      - binc use binary string comparison case insensitive (strcasecmp)
	*                      - shuffle use to randomize the collection. (used internally by method shuffle)
	*                      - user defined callback function (any callable comparison function (see php::usort() for more info)
	* @return $this for method chaining
	*/
	public function sort($sortBy,$sortType='std'){
		if( ! $this->count() )
			return $this;
		$this->_sortBy   = $sortBy;
		#- setting sorttype
		if( (! in_array($sortType,array('std','nat','natc','bin','binc'),true)) && ! is_callable($sortType)){
			throw new UnexpectedValueException('modelCollection::sort() call with invalid sortType parameter');
		}
		$this->_sortType = $sortType;
		if( method_exists($this,'uasort')){
			$this->uasort(array($this,'sortCompare'));
		}else{
			uasort($this,array($this,'sortCompare'));
		}
		return $this;
	}
	/**
	* same as sort but in reverse order
	* @see modelCollection::sort()
	* @param str $sortBy   property to use to sort the collection
	* @param str $sortType type of comparison to use can be one of see modelCollection::sort() method for more info
	* @return $this for method chaining
	*/
	public function rsort($sortBy,$sortType='std'){
		$this->_sortReversed = true;
		$this->sort($sortBy,$sortType);
		$this->_sortReversed = false;
		return $this;
	}
	public function shuffle(){
		$tmp=$this->getArrayCopy();
		shuffle($tmp);
		return smvcCollection::init($tmp);
	}
	/**
	* internal method to sort collection
	* @private
	* @see modelCollection::sort(), modelCollection::rsort()
	* @note MUST BE PUBLIC TO GET THIS WORK WITH PHP5.3.2
	*/
	public function sortCompare($_a,$_b){
		if( 'shuffle' === $this->_sortType ) #- shuffle don't need to know real values
			return rand(-1,1);
		$a = $_a[$this->_sortBy];
		$b = $_b[$this->_sortBy];
		if($a === $b){
			return 0;
		}
		switch($this->_sortType){
			case 'nat':  $res = strnatcmp($a,$b); break;
			case 'natc': $res = strnatcasecmp($a,$b); break;
			case 'bin':  $res = strcmp($a,$b); break;
			case 'binc': $res = strcasecmp($a,$b); break;
			case 'std':  $res = ($a < $b)?-1:1; break;
			default:
				$res = call_user_func($this->_sortType,$a,$b);
		}
		if( $this->_sortReversed)
			return $res>0?-1:1;
		return $res;
	}



	/**
	* return collection as a string. Without parameter return json_encoded collection
	* @param mixed $format callable function that will receive one item as parameter and return it as a formatted string or a string used as a formatString.
	*                      strings can ask for item property values by prefixing them with '%' like this "formatted item with %property"
	*                      to print a single % chars just escape it with a \ or by doubling it
	* @return string
	*/
	function __toString(){
		if(! func_num_args() ){
			return json_encode($this);
		}
		$args = func_get_args();
		$format = $args[0];
		$concat = isset($args[1])?$args[1]:'';
		$res = array();
		$callableFormat = false;
		if($format === null){
			$callableFormat = true;
			$format = 'json_encode';
		}else if( is_callable($format) ){
			$callableFormat = true;
		}

		foreach( $this as $v){
			$res[] = $callableFormat?call_user_func($format,$v):preg_replace(
				array('/(?<!%|\\\\)%(?!%)([A-Za-z_][A-Za-z0-9_]*)/e','![\\\\%]%!')
				,array('$v[\'\\1\']','%')
				,$format
			);
		}
		return implode($concat,$res);
	}

}