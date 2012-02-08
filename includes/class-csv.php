<?php

class csv extends SplFileObject{

	protected $firstLineIsHeader = false;
	protected $headers = array();
	protected $_flags = 0;
	protected $fp=null;
	protected $openMode = null;

	CONST DROP_NEW_LINE = 1; // this is to use with SKIP_EMPTY to skip empty lines
	CONST READ_AHEAD    = 2; // make next return the next row as value instead of this
	CONST SKIP_EMPTY    = 4; // this is to use with DROP_NEW_LINE to skip empty lines
	CONST READ_CSV      = 8; // you just can't remove this flag for a csv
	CONST AUTO_FILL     = 16;// make each row the attended size regarding attended fieldNames

	function __construct($fileName,$openMode='r',$delimiter=null,$enclosure=null,$useIncludePath=false,$resourceContext=null){
		if($resourceContext){
			parent::__construct($fileName,$openMode,$useIncludePath,$resourceContext);
		}else{
			parent::__construct($fileName,$openMode,$useIncludePath);
		}
		$this->openMode = $openMode;
		$this->setFlags( self::READ_AHEAD |  self::AUTO_FILL  | self::SKIP_EMPTY |  self::DROP_NEW_LINE );
		$this->setCsvControl(
			(string) (null!==$delimiter?$delimiter:';')
			,(string) (null!==$enclosure?$enclosure:'"')
		);
	}

	function setFlags($flags){
		$this->_flags = self::READ_CSV | $flags;
		parent::setFlags($this->_flags);
		return $this;
	}
	function getFlags(){
		return $this->_flags;
	}
	function checkFlag($flag){
		return $this->_flags & $flag;
	}

	function setFieldNames($fieldNames=null){
		if( false === $fieldNames ){
			$this->firstLineIsHeader = false;
			$this->headers = array();
		}else if( null === $fieldNames ){
			$oldPos = $this->key();
			$this->firstLineIsHeader = false;
			$this->rewind();
			$this->headers = $this->current();
			$this->firstLineIsHeader = true;
			$this->seek($oldPos);
		}else{
			$this->headers = is_string($fieldNames)?preg_split('/[\|;,]/',$fieldNames):(array) $fieldNames;
			$this->firstLineIsHeader = false;
			if(!( $this->ftell() || $this->eof()) ){
				if( preg_match('!^[wa]\+?!',$this->openMode) ){
					$this->append($this->headers);
				}
			}
		}
		return $this;
	}

	function getFieldNames(){
		return $this->headers;
	}

	/**
	* move cursor to first meaning line (not the headers) if self::READ_AHEAD is set return the line as array else return $this for method chaining
	*/
	function rewind(){
		parent::rewind();
		if( $this->firstLineIsHeader)
			return $this->next();
		return $this->checkFlag(self::READ_AHEAD)?$this->current():$this;
	}

	/**
	* return current datas of row
	* @return array
	*/
	function current(){
		$row = parent::current();
		if( ($fieldNb=count($this->headers)) && $fieldNb !== count($row) ){
			if( $this->checkFlag(self::AUTO_FILL) ){
				while(count($row)<$fieldNb){$row[] = null;}
				while(count($row)>$fieldNb){array_pop($row);}
			}else{
				return false;
			}
		}
		return empty($this->headers)?$row:array_combine($this->headers,$row);
	}

	/**
	* move cursor to next line, if self::READ_AHEAD is set return the line as array else return $this for method chaining
	*/
	function next(){
		parent::next();
		return ( $this->getFlags() & self::READ_AHEAD ) ? $this->current() : $this;
	}

	/**
	* shortcut to fpucsv method if exist
	* @param array $datas array representing the datas to put as a csv line
	* @return int
	*/
	public function append($datas){
		if( method_exists($this,'fputcsv') ){
			return $this->fputcsv($datas);
		}
		list($delimiter,$enclosure) = $this->getCsvControl();
		if(! empty($this->headers) ){
			$_datas = array();
			foreach($this->headers as $id=>$k){
				$_datas[$id] = isset($datas[$k])?$datas[$k]:(isset($datas[$id])?$datas[$id]:null);
			}
			$datas = $_datas;
		}
		if( strlen($enclosure) ){
			array_walk($datas,array(__class__,'encloseValue'),array($delimiter,$enclosure));
		}
		$datas = preg_replace('![\r\n]!','\\$0',implode($delimiter,$datas));
		return $this->fwrite("$datas\n");
	}
	static function encloseValue(&$v,$k,$params){
		list($d,$e)=$params;
		$v = $e . str_replace($replace=strlen($e)?$e:$d, "\\\\$replace", $v) . $e;
	}

	/**
	* read a csv file and return an indexed array.
	* @param string $cvsfile path to csv file
	* @param array $fldnames array of fields names. Leave this to null to use the first row values as fields names.
	* @param string $sep string used as a field separator (default ';')
	* @return array
	*/
	static function toArray($file,$fldNames=null,$delimiter=null,$enclosure=null){
		$csv = new csv($file,'r',$delimiter,$enclosure);
		$csv->setFieldNames($fldNames);
		$datas = array();
		foreach($csv->setFieldNames() as $row){
			$datas[] = $row;
		}
		return $datas;
	}

}
/** /
$t = new csv('/tmp/refPrices.csv');
$t->setFieldNames();
$t2 = new csv('/tmp/refPrices2.csv','a+');
$t2->setFieldNames($t->getFieldNames());
foreach($t->setFieldNames() as $row){
	print_r($row);
	$t2->append($row);
}
//*/


