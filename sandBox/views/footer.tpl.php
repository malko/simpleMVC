	
	<?php
		if(defined('DEVEL_MODE') && DEVEL_MODE)
			echo $this->simpleMVCdevelBar();
		echo $this->_js_getPending()
	?>
	</body>
</html>
