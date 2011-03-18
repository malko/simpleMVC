<?php

class swfobject_viewHelper extends jsPlugin_viewHelper{

	public $requiredFiles   = array('js/swfobject/swfobject.js');
	public $requiredPlugins = array();

	static $dfltOptions = array(
		'flashvars' => array(),
		'params' => array(
			'quality'=> 'high',
			'wmode'  => 'window',
			'bgcolor'=> '#fff',
			'allowFullscreen' => true
		),
		'attributes' => array()
	);

	/**
	*
	*/
	function swfobject($swfUrl,$name='myswf',$w='100%',$h='100%',$version='8',$defaultContent="",array $options=array()){
		$options = array_merge_recursive(self::$dfltOptions,$options);
		$_opts = new stdClass();
		foreach($options as $k=>$v){
			$_opts->$k = empty($v)?new stdClass():$v;
		}
		$this->_js_script("
		var ".$name."_opts = ".json_encode($_opts).";
		swfobject.embedSWF('$swfUrl', '$name', '$w', '$h', '$version','".ROOT_URL."/js/swfobject/expressInstall.swf', ".$name."_opts.flashvars, ".$name."_opts.params, ".$name."_opts.attributes);
		");
		return "<div id=\"$name\">$defaultContent</div>";
	}

}
