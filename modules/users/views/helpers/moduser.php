<?php
class moduser_viewHelper extends abstractViewHelper{
	function printLoginBox($infoBoxOnAlreadyLoggedIn=false){
		if( null !== users::getCurrent() && $infoBoxOnAlreadyLoggedIn ){
			if( $infoBoxOnAlreadyLoggedIn ){
				$this->modtpl('users','infobox');
			}
			return;
		}
		$this->modtpl('users','loginbox');
	}

	function printInfoBox(){
		if( users::getCurrent() ){
			$this->modtpl('users','infobox');
		}
	}

}
