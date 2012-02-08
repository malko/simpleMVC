<?php

class modusersController extends abstractController{
	public $installQueries = array(
		//-- users
		'CREATE TABLE IF NOT EXISTS `users` (
			`userId` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`userRole` INT UNSIGNED NOT NULL ,
			`login` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
			`password` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
			`email` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
			`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT "0",
			UNIQUE KEY `login` (`login`)
		);'
		//-- user's roles
		,'CREATE TABLE IF NOT EXISTS `userRoles` (
			`roleId` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`name` VARCHAR( 255 ) NOT NULL,
			`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT "1"
		)'
		,'INSERT INTO userRoles VALUES (1,"administrator",1)'
		,'INSERT INTO userRoles VALUES (2,"user",1)'
		//-- right's Domains
		,'CREATE TABLE  IF NOT EXISTS `userRightDomains` (
			`domainId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`name` VARCHAR( 255 ) NOT NULL
		)'
		,'INSERT INTO userRightDomains VALUES (1,"moduser")'
		,'INSERT INTO userRightDomains VALUES (2,"modules")'
		//-- rights
		,'CREATE TABLE IF NOT EXISTS `userRights` (
			`rightId` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`userRightDomain` INT UNSIGNED NOT NULL ,
			`name` VARCHAR( 255 ) NOT NULL,
			INDEX (  `userRightDomain` )
		)'
		,'INSERT INTO userRights VALUES (1,1,"list")'
		,'INSERT INTO userRights VALUES (2,1,"add")'
		,'INSERT INTO userRights VALUES (3,1,"del")'
		,'INSERT INTO userRights VALUES (4,1,"edit")'
		,'INSERT INTO userRights VALUES (5,1,"export")'
		,'INSERT INTO userRights VALUES (6,2,"admin")'
		,'INSERT INTO userRights VALUES (7,1,"roleadmin")'
		,'INSERT INTO userRights VALUES (8,1,"rightadmin")'
		//-- role's rights
		,'CREATE TABLE  IF NOT EXISTS `userRoles_userRights` (
			`userRole` INT UNSIGNED NOT NULL ,
			`userRight` INT UNSIGNED NOT NULL
		)'
		,'INSERT INTO userRoles_userRights VALUES (1,1)'
		,'INSERT INTO userRoles_userRights VALUES (1,2)'
		,'INSERT INTO userRoles_userRights VALUES (1,3)'
		,'INSERT INTO userRoles_userRights VALUES (1,4)'
		,'INSERT INTO userRoles_userRights VALUES (1,5)'
		,'INSERT INTO userRoles_userRights VALUES (1,6)'
		,'INSERT INTO userRoles_userRights VALUES (1,7)'
		,'INSERT INTO userRoles_userRights VALUES (1,8)'
	);

	function installAction($token){
		#- if(! $this->_token_check(30,null,'GET') ){
			#- return $this->msgRedirect('Token authentication expired');
		#- }
		return false;
	}

	function install2Action(){
		#- if( ! $this->_token_check(300,'moduserInstall2') ){
			#- return $this->msgRedirect('Token authentication expired');
		#- }

		#- define models dir if not
		if(! defined('MODELS_DIR') ){
			$config['MODELS_DIR'] = LIB_DIR.'/models';
			define(MODELS_DIR,$config['MODELS_DIR']);
		}
		$error = false;
		$appConfFile = CONF_DIR.'/'.FRONT_NAME.'_config.php';

		//-- check app config file is writeable
		if( (! is_writable(CONF_DIR)) || ( is_file($appConfFile) && !is_writable($appConfFile) ) ){
			self::appendAppMsg($appConfFile.' must be writable','error');
			$error=true;
		}
		//-- check models dir is writable
		if(! is_writable(MODELS_DIR) ){
			self::appendAppMsg(MODELS_DIR.'must be writable','error');
			$error=true;
		}
		//-- check database connection
		$db = db::getInstance($_POST['dbConnection'],false);
		if( !$db->db ){
			self::appendAppMsg('Database connection error','error');
			$error=true;
		}
		if($error){
			smvcModule::moduleSetActive('users',false);
			return $this->redirectAction('adminmodules:index');
		}
		//-- play queries if no table users detected
		$tables = $db->list_tables();
		if( (!$tables) || !in_array('users',$tables,true) ){
			foreach($this->installQueries as $query){
				$db->query($query);
			}
		}else{
			self::appendAppMsg('SQL queries wasn\'t played as it seems there\'s already a users table in given database.','error');
		}

		//-- ajoute un grain de sel pour le hashage des mots de passe
		if(! defined('MOD_USERS_SALT') ){
			foreach($_POST as $k=>$v){
				if( preg_match('!^moduserInstall2-!',$k) ){
					$config['MOD_USERS_SALT'] = substr($k,(int) strpos($k,'-')+1);
					define('MOD_USERS_SALT',$config['MOD_USERS_SALT']);
				}
			}
		}
		if( !defined('DB_CONNECTION') ){
			$config['DB_CONNECTION'] = $_POST['dbConnection'];
			$config['MOD_USERS_DB_CONNECTION'] = '%DB_CONNECTION %';
		}elseif(DB_CONNECTION === $_POST['dbConnection']){
			$config['DB_CONNECTION'] = DB_CONNECTION;
			$config['MOD_USERS_DB_CONNECTION'] = '%DB_CONNECTION %';
		}else{
			$config['MOD_USERS_DB_CONNECTION'] = $_POST['dbConnection'];
		}

		//--  write configuration to the application
		write_conf_file(CONF_DIR.'/'.FRONT_NAME.'_config.php',$config,true);

		// then copy model files to the modelPath
		$models = glob(dirname(dirname(__file__)).'/install/models/*');
		foreach( $models as $mod){
			$name = basename($mod);
			if( ! is_file(MODELS_DIR.'/'.$name) ){
				copy($mod,MODELS_DIR.'/'.$name);
			}
		}

		#- creer l'utilsateur par défaut
		$user = users::getNew()->_setDatas($_POST);
		// set it as an actvive admin
		$user->_setDatas(array('role'=>1,'active'=>1));

		if( $user->hasFiltersMsgs() ){
			self::appendAppMsg($user->getFiltersMsgs(),'error');
			self::appendAppMsg('admin user saved but contains some error','error');
		}
		$user->save();
		smvcModule::getInstance('users')->setInstalled(true);


		return $this->redirectAction('adminmodules:index');

	}

	public function configureAction(){
		$u = users::getCurrent();
		if(! ($u instanceof users && $u->hasRight('modules.admin')) ){
			return $this->msgRedirect('Unauthorized action');
		}
	}

}