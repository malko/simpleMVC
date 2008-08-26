<?php
/**
* helper to easily incorporate jquery Autocomplete
* @package simpleMVC
* pour l'instant ne fonctionne qu'avec une url de rappel qui doit retourner une liste de résultats visible associé à leur valeurs réelle (renvoyé par le form)
* pour cela la liste retourné doit etre construite comme suit: label|valeur
* http://docs.jquery.com/Plugins/Autocomplete
*/

class jqueryAutocomplete_viewHelper extends  jQueryPlugin{
	/** path relative to jQuery_viewHelper::$pluginPath/$pluginName */
	protected $requiredFiles = array(
		'lib/jquery.bgiframe.min.js',
		'lib/jquery.ajaxQueue.js',
		'lib/thickbox-compressed.js',
		'jquery.autocomplete.css',
		'jquery.autocomplete.js',
	);

	public $options = array(
		'minChars' => '2',
		'matchContains' => true,
		'mustMatch'=>false,
		'cacheLength' => 1,
		'displayVal'=>'', #- valeur affichée
		'val'=>'' #- valeur réelle renvoyé par le formulaire
	);

	function jqueryAutocomplete($idElement,$datasOrUrl=null,array $options=null){
		$val = $displayVal = '';
		#- get values
		if(isset($options['val'])){
			$val = $options['val'];
			unset($options['val']);
		}
		#- get values
		if(isset($options['displayVal'])){
			$displayVal = $options['displayVal'];
			unset($options['displayVal']);
		}


		#- manage options
		$options = ( null !== $options )?array_merge($this->options,$options):$this->options;
		foreach($options as $k=>$v){
			$_opts[] = "$k:".(is_bool($v)?($v?'true':'false'):"'$v'");
		}
		$_opts = '{'.implode(',',$_opts).'}';
		#- manage datas/url
		if( is_array($datasOrUrl) )
			$datasOrUrl = implode(' ',$datasOrUrl);


	jQuery::OnReadyDocument('
		$("#'.$idElement.'_autoComp").autocomplete("'.$datasOrUrl.'",'.$_opts.');
		$("#'.$idElement.'_autoComp").result(function(event, data, formatted) { if (data) $("#'.$idElement.'").val(data[1]);});
	');

		return 	'
		<input type="hidden" id="'.$idElement.'" name="'.$idElement.'" value="'.str_replace('"','\"',$val).'" />
		<input type="text" id="'.$idElement.'_autoComp" value="'.str_replace('"','\"',$displayVal).'" />
		';
	}

}
