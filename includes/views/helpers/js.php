<?php

class js_viewHelper extends abstractViewHelper{

	static $includedFiles = array();
	static $pendingScript = '';

	/** false or path to jquery library */
	static $useJquery = false;

	function js($datas=null){
		if( null === $datas)
			return self::getPending();
		if( is_array($datas) || preg_match('!\.(js|css)$!',$datas) )
			return self::includes($datas);
		return self::script($datas);
	}

	static function script($script){
		self::$pendingScript .= "\n$script\n";
	}

	static function getPending(){
		if( false!==self::$useJquery && !isset(self::$includedFiles[self::$useJquery]) )
			self::includes(self::$useJquery);

		if( ! strlen(self::$pendingScript) )
			return self::getIncludes();

		$script = ( false===self::$useJquery)? self::$pendingScript : "jQuery().ready(function(){\n$script\n});";
		self::$pendingScript = '';

		return self::getIncludes()."\n<script type=\"text/javascript\">\n/*<![CDATA[*/\n$script\n/*]]>*/\n</script>\n";
	}

	static function includes($file){
		if( is_array($file) ){
			$success = true;
			foreach($file as $f)
				$success &= self::includes($f);
			return $success;
		}
		if( ! is_file(ROOT_DIR.'/'.$file) )
			return false;
		if( isset(self::$includedFiles[$file]) )
			return true;
		self::$includedFiles[$file]=false;
		return true;
	}

	static function getIncludes(){
		$incStr = '';
		foreach(self::$includedFiles as $k=>$v){
			if( $v )#- avoid multiple time inclusion
				continue;
			if( preg_match('!\.js$!',$k) )
				$incStr.= "<script src=\"".ROOT_URL."/$k\" type=\"text/javascript\"></script>\n";
			if( preg_match('!\.css$!',$k) )
				$incStr.= "<link type=\"text/css\" rel=\"stylesheet\" href=\"".ROOT_URL."$k\" />\n";
			self::$includedFiles[$k]=true;
		}
		return $incStr;
	}
}

class js extends js_viewHelper{
}
