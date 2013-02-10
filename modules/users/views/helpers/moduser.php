<?php
class moduser_viewHelper extends abstractViewHelper{
	function printLoginBox($infoBoxOnAlreadyLoggedIn=false,$redirectDispatch=null){
		if( null !== users::getCurrent() ){
			if( $infoBoxOnAlreadyLoggedIn ){
				$this->modtpl('users','infobox');
			}
			return '';
		}
		$this->view->userInfoBox_redirectDisaptch = $redirectDispatch;
		$this->modtpl('users','loginbox');
	}

	function printInfoBox(){
		if( users::getCurrent() ){
			$this->modtpl('users','infobox');
		}
	}

}
