<?php
/**
* @todo gerer groupe d'utilisateur par défaut
*/
class formBuilderModule extends smvcModule{

	public $_configureDispatch='formbuilder:configure';

	public $dependOn = array(
		'adminmodules'
		,'users'
	);

	private $installQueries = array(
		"CREATE TABLE IF NOT EXISTS `formBuilderForms` (
			`formId` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`action` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`method` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`activityPeriodStart` date NOT NULL,
			`activityPeriodEnd` date NOT NULL,
			`active` tinyint(1) unsigned NOT NULL DEFAULT '0',
			`groupMethod` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`htmlIntro` text COLLATE utf8_unicode_ci NOT NULL,
			`htmlOutro` text COLLATE utf8_unicode_ci NOT NULL,
			`submitText` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`jsUri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`cssUri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`rawData` mediumblob NOT NULL,
			`html` mediumblob NOT NULL,
			PRIMARY KEY (`formId`),
			UNIQUE KEY `name` (`name`),
			KEY `activityPeriodStart` (`activityPeriodStart`,`activityPeriodEnd`,`active`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		",
		"CREATE TABLE IF NOT EXISTS `formBuilderWidgets` (
			`widgetId` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`rawProperties` text COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`widgetId`),
			UNIQUE KEY `name` (`name`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
		"
	);
	protected function install(){
		$controller = abstractController::getCurrent();
		$config = array();
		if(! defined('DB_CONNECTION') ){
			return $controller->msgRedirect('DB_CONNECTION is not defined','error','adminmodules:');
		}
		#- check for models dir if not
		if(! defined('MODELS_DIR') ){
			return $controller->msgRedirect('MODELS_DIR must be defined','error','adminmodules:');
		}
		//-- check models dir is writable
		if(! is_writable(MODELS_DIR) ){
			return $controller->msgRedirect(MODELS_DIR.' must be writable','error','adminmodules:');
		}
		//-- check database connection
		$db = db::getInstance(DB_CONNECTION,false);
		if( !$db->db ){
			return $controller->msgRedirect('Database connection error','error','adminmodules');
		}
		//-- play queries if no table formBuilder detected
		$tables = $db->list_tables();
		if( (!$tables) || !in_array('formBuilderForms',$tables,true) ){
			foreach($this->installQueries as $query){
				$db->query($query);
			}
		}else{
			abstractController::appendAppMsg('SQL queries wasn\'t played as it seems there\'s already required tables in given database.','info');
		}

		// then copy model files to the modelPath
		// $models = glob(dirname(dirname(__file__)).'/install/models/*');
		// foreach( $models as $mod ){
		// 	$name = basename($mod);
		// 	if( ! is_file(MODELS_DIR.'/'.$name) ){
		// 		copy($mod,MODELS_DIR.'/'.$name);
		// 	}
		// }

		smvcModule::getInstance('formBuilder')->setInstalled(true);
		return true;
	}

	protected function uninstall(){ }
	
}