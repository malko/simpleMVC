<?php
	echo "<h1>$this->pageTitle</h1>";
	echo '
	<div style="text-align:right;">
		<a href="'.$this->url('add',null,array('modelType'=>$this->modelType)).'"><img src="'.ELEMENTS_URL.'/icones/admin/bouton_ajouter_icone.png" alt="ajouter un &eacute;lement"/> Ajouter un &eacute;lement.</a>
	</div>';
	echo $this->adminSortableList($this->listDatas,$this->listHeaders);
?>
