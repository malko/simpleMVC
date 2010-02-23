<?php
class adminmodelsController extends abstractAdminmodelsController{
	/**
	* method that extended class should override to check if user is allow or not to access to extended controller or not.
	*/
	function check_authorized(){
		self::appendAppMsg("Don't forget to edit the ".get_class($this)."::check_authorized() method or anyone could be editing your datas","warning");
		return true;
	}
}