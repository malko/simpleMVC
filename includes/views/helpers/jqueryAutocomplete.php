<?php
/**
* helper to easily incorporate jquery Datepicker
* @package simpleMVC
*/

class jqueryAutocomplete_viewHelper extends  jQueryPlugin{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	protected $requiredFiles = array(
		'jquery.autocomplete.css',
		'jquery.autocomplete.min.js',
	);

	public $options = array(
		'minChars' => '2',
		'matchContains' => true,
		'mustMatch'=>false,
	);

	function jqueryAutocomplete($idElement,$value=null,$datasOrUrl=null,array $options=null){
		#- manage options
		$options = ( null !== $options )?array_merge($this->options,$options):$this->options
		foreach($options){
			$_opts = "$k:".(is_bool($v)?($v?'true':'false'):"'$v'";
		}
		$_opts = '{'.implode(',',$_opts).'}';
		#- manage datas/url
		if( is_array($datas) )
			$datas = implode(' ',$datas);


	jQuery::OnReadyDocument('
	$("#'.$idElement.'").autocomplete("'.$datas.'",'.$_opts.');');

		return 	'<input type="text" id="'.$idElement.'" name="'.$idElement.'" value="'.$value.'" />';
	}


	/*function getOnReadyDocument(){
		if($this->initRequired){
			$this->initRequired = false;
			return "$('.jqueryRte').rte({imgPath:'".ROOT_URL.jQuery::$pluginPath."/jqueryRte/'});";
		}
		return '';
	}*/

}
