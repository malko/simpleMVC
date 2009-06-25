<?php

class filemanagerController extends abstractController{

	static public $connectorConfig = array(
		'docRoot' => USER_DATAS_DIR,
		'urlRoot' => USER_DATAS_URL,
		'accessCallBack' => array('filemanagerController','checkAccess'),
		'allowedExt' => 'jpe?g|gif|png|mpe?g|doc|xls|csv|pdf|rtf|flv|txt|ppt|odt|swf', // allowed extensions
	);

	public $connector = null;

	function init(){
		self::appendAppMsg('PLEASE DON\'T FORGET TO PROTECT YOUR FILEMANAGER CONNECTOR FOR OBVIOUS SECURITY REASONS','error');
		echo '{"error":"PLEASE DON\'T FORGET TO PROTECT YOUR FILEMANAGER CONNECTOR FOR OBVIOUS SECURITY REASONS"}';
		exit();
		#- ~ parent::init();// <<-- on initialise pas de vue
		$this->connector = new fileManager(self::$connectorConfig['docRoot'],self::$connectorConfig['urlRoot'],self::$connectorConfig['accessCallBack']);
		$this->connector->listSetAllowedExtension(self::$connectorConfig['allowedExt']);
	}

	public function __call($m,$a=null){ // replace the call method to avoid pre/post actions
		if( method_exists($this,$m.'Action'))
			return call_user_func_array(array($this,$m.'Action'),$a);
	}

	function indexAction(){
		echo $this->connector->processRequest();
	}

	static function checkAccess($m){
		return true; // please configure your access restrictions rules here
	}
}
