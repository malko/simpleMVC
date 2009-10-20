
	<?php
		if(defined('DEVEL_MODE') && DEVEL_MODE){
			echo $this->simpleMVCdevelBar();
			$this->button('.ui-button',array('checkButtonset'=>true));
		}
		echo $this->_js_getPending()
	?>
	</body>
</html>
