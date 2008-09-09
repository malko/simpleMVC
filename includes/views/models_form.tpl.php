<h1><?= $this->pageTitle ?></h1>
<?php
	if( !empty($this->datasDefs) ){
		foreach($this->datasDefs as $k=>$d){
			echo $this->modelFormInput(isset($this->_model_)?$this->_model_:$this->modelType,$k);
		}
	}
?>
<input type="reset" onclick="window.location = '<?= $this->listUrl ?>';"; value="<?= langManager::msg('back'); ?>"  class="noSize"/>
<input type="submit" value="<?= langManager::msg('save'); ?>" class="noSize" />