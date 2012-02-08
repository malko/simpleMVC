<?php

abstract class moduseradminController extends abstractAdminmodelsController{

	function init(){
		parent::init();
		$u = users::getCurrent();
		foreach( array('add','edit','del','list','export') as $rName){
			$this->_modelConfig['users']['ACTION'][$rName] = $u->hasRight("moduser.$rName");
		}
	}
	function check_authorized(){
		$u = users::getCurrent();
		if( ! $u instanceof users ){
			return false;
		}
		if(! $u->hasRight(empty($this->authorized_right)?'moduser':$this->authorized_right)){
			return false;
		}
		return true;
	}


	public function setDictName(){
		$this->_langManagerDicName = "moduser_".$this->getName();
	}

	public function langMsg($msg,$datas=null){
		return langManager::msg($msg,$datas,$this->_langManagerDicName);
	}



}