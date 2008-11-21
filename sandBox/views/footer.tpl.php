	<?= (defined('DEVEL_MODE') && DEVEL_MODE && class_exists('dbProfiler',false))?dbProfiler::printReport():''; ?>
	</body>
</html>