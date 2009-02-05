<?php
/**
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
*/
?>
<h1><?= $this->pageTitle ?></h1>
<form action="<?= $this->actionUrl ?>" method="post">
<table border="0" cellspacing="0" cellpadding="2">
<?php
	if( !empty($this->datasDefs) ){
		$inputOpts = array(
			'formatStr'=>'<tr class="formInput"><td>%label</td><td>%input</td></tr>'
		);
		foreach($this->datasDefs as $k=>$d){
			$opts = $inputOpts;
			if( isset($this->inputOpts[$k] ) )
			$opts = array_merge($inputOpts,$this->inputOpts[$k]);
			if((! isset($this->_model_)) && !empty($this->{$k})){
				$opts['value'] = $this->{$k};
			}
			echo $this->modelFormInput(isset($this->_model_)?$this->_model_:$this->modelType,$k,$opts);
		}
	}
?>
</table>
<input type="reset" onclick="window.location = '<?= $this->listUrl ?>';"; value="<?= langManager::msg('back',null,$this->_langManagerDicName); ?>"  class="noSize"/>
<input type="submit" value="<?= langManager::msg('save',null,$this->_langManagerDicName); ?>" class="noSize" />
</form>
