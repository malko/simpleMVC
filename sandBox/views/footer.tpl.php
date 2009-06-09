	
	<?php
		if(defined('DEVEL_MODE') && DEVEL_MODE){
			echo $this->simpleMVCdevelBar();
			$this->_jqueryui_button('[class*=ui-button]',array('checkButtonset'=>true));
		}
		echo $this->_js_getPending()
	?>
	</body>
</html>
