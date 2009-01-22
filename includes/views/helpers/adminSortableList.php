<?php
/**
* helper for administration lists
*/
class adminSortableList_viewHelper extends jsPlugin_viewHelper{

	public $requiredFiles = array('js/sortTable.js');
	public $requiredPlugins = array('jquery');

	static $lastTableId = 0;
	static $actionStr = '/:controller/:action/id/:id';


	function adminSortableList(array $datas=null, array $headers = null, $PK = 'id'){
		$this->view->helperLoad('js');
		if( empty($datas) ){
			return '<div style="font-weight:bold;color:silver;">'.langManager::msg('No item in database please, you must create one first.').'</div>';
		}

		if( is_null($headers) ){
			$headers = array_keys(current($datas));
		}
		foreach($headers as $h){
			if($h===$PK)
				continue;
			$_headers[] = "'".str_replace("'","\'",ucfirst($h))."'";
		}
		$_headers = 'var headers = ['.implode(', ',$_headers).",{label:'&nbsp;',unsortable:true,width:'55px'}];";

		$tableId = 'sortTable'.(++self::$lastTableId);
		foreach($datas as $row){
			$pk = $row[$PK];
			unset($row[$PK]);$row['id'] = $pk;
			$jsData[] = "['".implode("', '",array_map(array($this,'escapeDatas'),$row))."']";
		}
		$datas   = "var $tableId = [\n        ".implode(",\n        ",$jsData)."\n      ];\n";
		$controller = $this->getController()->getName();
		#- $baseUrl = APP_URL.'/'.$controller->getName.'/';
		$this->js("
			var options = { rowRendering: function(row,data){
				$(row).addClass(data.rowid%2?'row':'altrow');
				var bcell  = row.cells[row.cells.length-1];
				var itemId = data.data[data.data.length-1];
				bcell.innerHTML = '<img onclick=\"window.location=\'".
					APP_URL.str_replace(array(':controller',':action',':id'),array($controller,"edit","'+itemId+'"),self::$actionStr).
					"\';\" alt=\"".langManager::msg('Edit')."\" title=\"".langManager::msg('Edit')."\" src=\"".ELEMENTS_URL."/icones/admin/bouton_modifier_icone.png\"/>'+
					' <img onclick=\"return adminlistDelRow(\'$controller\',\''+itemId+
					'\');\" alt=\"".langManager::msg('Delete')."\" title=\"".langManager::msg('Delete')."\" src=\"".ELEMENTS_URL."/icones/admin/edittrash.png\"/>';
			}};
			sortTable.init('$tableId',headers,options);
		");
		return "
		<table cellspacing=\"0\" cellpadding=\"2\" border=\"0\" class=\"adminList\" id=\"$tableId\"></table>
		<script type=\"text/javascript\">
			function adminlistDelRow(ctrl,rowid){
				if(! confirm('".str_replace("'","\'",langManager::msg("Are you sure you want to delete this item?"))."')){
					return false;
				}
				window.location = '".APP_URL.str_replace(array(':controller',':action',':id'),array("'+ctrl+'","del","'+rowid+'"),self::$actionStr)."';
				return true;
			}
			$datas
			$_headers
		</script>
		";
	}

	function escapeDatas($datas){
		return ($datas==='')?'&nbsp;':preg_replace(array("!'!","!\r?\n!"),array("\'","\\n"),"$datas");
	}
}
