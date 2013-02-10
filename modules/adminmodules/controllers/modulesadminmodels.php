<?php

abstract class modulesadminmodelsController extends abstractAdminmodelsController{
	public $requiredRight = "modules.admin";

	function check_authorized(){
		$u = users::getCurrent();
		if( ! $u instanceof users ){
			return false;
		}
		return $u->hasRight($this->requiredRight);
	}
	public function setDictName(){
		$this->_langManagerDicName = "adminmodels_".$this->modelType."|modules_".$this->getName().'|'.$this->getName();
	}

	public function langMsg($msg,$datas=null){
		return langManager::msg($msg,$datas,$this->_langManagerDicName);
	}
}
