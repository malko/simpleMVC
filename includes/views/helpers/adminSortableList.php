<?php
/**
* helper for administration lists
* @changelog
*            - 2009-05-28 - ncancel: added $editable and $deletable parameters. 
*/
class adminSortableList_viewHelper extends jsPlugin_viewHelper{

	public $requiredFiles = array('js/sortTable.js');
	public $requiredPlugins = array('jquery');

	static $lastTableId = 0;
	static $actionStr = '/:controller/:action/id/:id';


	function adminSortableList(array $datas=null, array $headers = null, $PK = 'id',$editable=true,$deletable=true){
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

		$tableId = 'sortTable'.($this->view->modelType?$this->view->modelType:(++self::$lastTableId));
		foreach($datas as $row){
			$pk = $row[$PK];
			unset($row[$PK]);$row['id'] = $pk;
			$jsData[] = "['".implode("', '",array_map(array($this,'escapeDatas'),$row))."']";
		}
		$datas   = "var $tableId = [\n        ".implode(",\n        ",$jsData)."\n      ];\n";
		$controller = $this->getController()->getName();
		#- $baseUrl = APP_URL.'/'.$controller->getName.'/';
		$msgEdit = htmlentities(langManager::msg('Edit'));
		$msgDel  = htmlentities(langManager::msg('Delete'));

		if($editable)
			$edit = "var btE = $('<img src=\"".GUI_IMG_URL."/icones/admin/document-properties.png\" title=\"$msgEdit\" alt=\"$msgEdit\" />');
				btE.click(function(){
					window.location = '".APP_URL.str_replace(array(':controller',':action',':id'),array($controller,"edit","'+itemId+'"),self::$actionStr)."';
				});";
		else
			$edit="";
		if($deletable)
			$delete = "var btD = $('<img src=\"".GUI_IMG_URL."/icones/admin/list-remove.png\" title=\"$msgDel\" alt=\"$msgDel\" />');
				btD.click(function(){
					if(! confirm('".str_replace("'","\'",langManager::msg("Are you sure you want to delete this item?"))."')){
						return false;
					}
					window.location = '".APP_URL.str_replace(array(':controller',':action',':id'),array("$controller","del","'+itemId+'"),self::$actionStr)."';
					return true;
				});";
		else
			$delete="";
		
		if($editable && $deletable)
			$bt  ="$(bcell).html('').append(btE,btD).find('img').css('cursor','pointer');";
		else if(!$editable && $deletable)
			$bt = "$(bcell).html('').append(btD).find('img').css('cursor','pointer');";
		else if($editable && !$deletable)
			$bt = "$(bcell).html('').append(btE).find('img').css('cursor','pointer');";
		else 
			$bt="$(bcell).html('');";
		$this->js("
			var options = { rowRendering: function(row,data){
				$(row).addClass(data.rowid%2?'row':'altrow');
				var bcell  = row.cells[row.cells.length-1];
				var itemId = data.data[data.data.length-1];
				$edit
				$delete
				$bt
			}};
			sortTable.init('$tableId',headers,options);
		");
		return "
		<table cellspacing=\"0\" cellpadding=\"2\" border=\"0\" class=\"adminList\" id=\"$tableId\"></table>
		<script type=\"text/javascript\">
			$datas
			$_headers
		</script>
		";
	}

	function escapeDatas($datas){
		return ($datas==='')?'&nbsp;':preg_replace(array("!'!","!\r?\n!"),array("\'","\\n"),"$datas");
	}
}
