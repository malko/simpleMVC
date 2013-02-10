
<?php
	if( DEVEL_MODE_ACTIVE() ){
		echo langManager::makeDicForm($this->url(':saveDicFormInputs'),str_replace(':','_',abstractController::getCurrentDispatch()),langManager::collectFailures());
	}
	if( ! (defined('JS_TO_HEAD') && JS_TO_HEAD) ){
		echo $this->_js_getPending();
	}
?>
</body>
</html>
