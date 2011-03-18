
<?php
	if( ! (defined('JS_TO_HEAD') && JS_TO_HEAD) ){
		echo $this->_js_getPending();
	}
	if( DEVEL_MODE ){
		echo langManager::makeDicForm($this->url(':saveDicFormInputs'),str_replace(':','_',abstractController::getCurrentDispatch()),langManager::collectFailures());
	}
?>
</body>
</html>
