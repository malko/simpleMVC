<?php
/**
* helper to easily incorporate jquery.filemanager
* @package simpleMVC
*/

class filemanager_viewHelper extends  jsPlugin_viewHelper{
	public $requiredFiles = array(
		'js/jqueryPlugins/jqueryFilemanager/ajaxupload.2.6.js',
		'js/jqueryPlugins/jqueryFilemanager/filemanagerLang/fr.js',
		'js/jqueryPlugins/jqueryFilemanager/jquery.filemanager.js',
		'js/jqueryPlugins/jqueryFilemanager/jquery.filemanager.css',
	);
	public $requiredPlugins = array(
		'jqueryui'
	);

	static public $defaultOptions = array(
		'rootDir'   => '/',
		'prefixValue' => USER_DATAS_URL,
		'connector' => null,
	);

	//-- init default options
	function init(){
		if(is_null(self::$defaultOptions['connector']))
			self::$defaultOptions['connector'] = $this->url('index','filemanager');
	}
	/**
	* return necessary code to render a filemanager widget
	* @param string $inputName    name of the input element
	* @param mixed  $options rootDir   -> chemin absolue de navigation dans les fichiers utilisateurs ('/' par défaut)
	*                        connector -> url du connecteur php à rappeler (par défaut APP_URL/filemanager)
	*                        prefixValue -> préfix pour les valeurs choisies. (default to USER_DATAS_URL)
	*                        isDialog -> boolean value if so will be created as a hidden dialog instead of a full wiget
	*                                   in this case u can access the dialog by fmDialog_$id in your scripts
	*                        style -> how to render the div (string inline style or array of key/value pair of style attibutes)
	*                        n'importe quel autre valeur de configuration de jquery.filemanager.js
	* @return string.
	*/
	function filemanager($id=null,array $options=null){
		if( null===$id)
			$id=self::uniqueId();
		//-- manage style options
		if(! isset($options['style']) ){
			$style='';
			if( ! empty($options['isDialog'])){
				$style=' style="width:250px;height:300px;overflow:auto;"';
			}
		}else{
			$style = is_array($options['style'])?json_encode($options['style']):$options['style'];
			if( is_array($options['style'])){
				$style = $options['style'];
				if(! empty($options['isDialog'])){
					if(!isset($style['width']))$style['width']='250px';
					if(!isset($style['height']))$style['height']='300px';
					if(!isset($style['overflow']))$style['overflow']='auto';
				}
				$this->_js_script("$('#id').css(".json_encode($style).");");
				$style='';
			}else{
				$style=$options['style'];
				if(! empty($options['isDialog'])){
					if( substr($style,-1)!==';') $style.=';';
					if(!preg_match('!(^|;|\s)width\s*:!i',$style))$style.='width:250px;';
					if(!preg_match('!(^|;|\s)height\s*:!i',$style))$style.='height:300px;';
					if(!preg_match('!(^|;|\s)overflow\s*:!i',$style))$style.='overflow:auto;';
				}
				$style=" style=\"$style\"";
			}
			unset($options['style']);
		}
		//-- manage isDialog option
		$divStr = "<div id=\"$id\"$style></div>";
		if(! empty($options['isDialog'])){
				$divStr = "<div id=\"fmDialog_$id\" title=\"File selection\" style=\"display:none;\">".
				"<div id=\"$id\"$style></div></div>";
				$this->_js_script("$('#fmDialog_$id').dialog({autoOpen:false,resizable:false,width:'275px',close:function(){fm.infos.hide()}});");
		}
		if( isset($options['isDialog']) ) unset($options['isDialog']);
		#- ~ <div id="'+fmb.dialogId+'" title="File selection" style="display:none;">\
			#- ~ <div id="'+fmb.fmId+'" style="width:250px;overflow:auto;height:300px;"></div></div>

		$options = json_encode(array_merge(self::$defaultOptions,(array)$options));
		$this->_js_script("$('#$id').filemanager($options)");
		return $divStr;
	}

	/**
	* return a button that display the filemanager
	* @param string $jsCallBack javascript function to call on fileClicked (receive path as first parameter)
	* @param mixed  $options rootDir   -> chemin absolue de navigation dans les fichiers utilisateurs ('/' par défaut)
	*                        connector -> url du connecteur php à rappeler (par défaut APP_URL/filemanager)
	*                        prefixValue -> préfix pour les valeurs choisies. (default to USER_DATAS_URL)
	*                        fileClicked -> required reference to callback function when file is clicked
	*                        n'importe quel autre valeur de configuration de jquery.filemanager.js
	* @return string.
	*/
	function button($jsCallBack='undefined',$id=null,array $options=null){
		if( null===$id)
			$id=self::uniqueId();
		$options = json_encode(array_merge(self::$defaultOptions,(array)$options));
		$this->_js_script("$('#$id').filemanagerButton($jsCallBack,$options)");
		return '<button id="'.$id.'" class="ui-state-default ui-corner-all" title="browse"><span class="ui-icon ui-icon-folder-collapsed" title="browse">browse</span></button>';
	}

	/**
	* return necessary code to render an input file form element.
	* @param string $inputName    name of the input element
	* @param mixed  $options rootDir   -> chemin absolue de navigation dans les fichiers utilisateurs ('/' par défaut)
	*                        connector -> url du connecteur php à rappeler (par défaut APP_URL/filemanager)
	*                        prefixValue -> préfix pour les valeurs choisies. (default to USER_DATAS_URL)
	*                        n'importe quel autre valeur de configuration de jquery.filemanager.js
	* @return string.
	*/
	function entry($inputName,$value=null,array $options=null){
		$id = preg_replace('![^a-z0-9_-]!i','',$inputName);
		$options = json_encode(array_merge(self::$defaultOptions,(array)$options));
		$this->_js_script("$('#$id').filemanagerEntry($options)");
		return '<input type="text" name="'.$inputName.'" id="'.$id.'" value="'.$value.'" class="filemanagerEntry"/>';
	}

}
