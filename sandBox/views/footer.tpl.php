	<?= (defined('DEVEL_MODE') && DEVEL_MODE && class_exists('dbProfiler',false))?dbProfiler::printReport():''; ?>
	<?= $this->_js_getPending(); ?>
	</body>
</html>
